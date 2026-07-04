# HikBridge Laravel SDK — Demo App

A reference implementation of the [`nugsoft/hikbridge-laravel-sdk`](https://packagist.org/packages/nugsoft/hikbridge-laravel-sdk). Every feature of the SDK is exercised here so you can copy patterns directly into your own project.

The app is **multi-tenant**: each _business_ stores its own HikBridge API key, and every logged-in user's SDK calls are automatically scoped to their business's key. Because a HikBridge key is tied to a single business server-side, users of one business can never see or authenticate against another business's devices. See [Multi-tenancy](#multi-tenancy).

## Live demo

Want to see it running before you dive into the code? A hosted version is available at:

**[hikbridge-demo.nugsoftstagging.com](https://hikbridge-demo.nugsoftstagging.com/)**

Log in, poke around the employees, devices, webhooks, and attendance pages, and get a feel for what the SDK can do before you write a single line.

---

## What this demo covers

| Section | SDK calls demonstrated |
|---|---|
| **Multi-tenancy** | Per-business API keys, per-request SDK client binding (`AppServiceProvider`) |
| **Business** | `HikBridge::business()->get()` |
| **Devices** | `HikBridge::devices()->list()` |
| **Employees** | `persons()->list/create/update/delete`, `PendingOperation::waitUntilDone()` |
| **Employee detail** | `biometrics($id)->summary/uploadFace/captureFace/deleteFace/captureFingerprint/deleteFingerprint/addAccessCard/deleteAccessCard` |
| **Access events** | `events()->list()` (cursor pagination, filters), `events()->triggerSync()` |
| **Webhooks** | `webhooks()->list/create/delete/sendTestPing/deliveries` |
| **Attendance report** | Full cursor-pagination loop over `events()->list()` |
| **Webhook receiver** | HMAC-SHA256 signature verification + `HikEvent` model |

---

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+ / npm
- At least one HikBridge API key (`hbk_...`) — one per business you want to demo

> **SDK source.** This demo tracks the SDK's `v2` line (which renamed `organization()` → `business()`). Until v2 is published to Packagist, `composer.json` resolves the package from a **local path repository** at `../hikbridge-laravel-sdk`. Check the SDK out as a sibling directory before installing:
>
> ```bash
> git clone https://github.com/nugsoft/hikbridge-laravel-sdk.git ../hikbridge-laravel-sdk
> ```
>
> Once v2 is tagged on Packagist, drop the `repositories` block from `composer.json` and require the tagged version instead.

---

## Quick start

```bash
# 1. Clone the SDK (sibling dir) and this demo, then install
git clone https://github.com/nugsoft/hikbridge-laravel-sdk.git hikbridge-laravel-sdk
git clone https://github.com/nugsoft/hikbridge-demo.git hikbridge-demo
cd hikbridge-demo
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure HikBridge (see below) — edit .env

# 4. Database + seed two demo tenants
touch database/database.sqlite   # default is SQLite
php artisan migrate --seed

# 5. Assets
npm install && npm run build
```

Or run everything in one step with the Composer script (then seed):

```bash
composer setup
php artisan db:seed
```

### Demo login accounts

`php artisan migrate --seed` provisions two businesses, each with a user:

| Business | Login email | Password | HikBridge key |
|---|---|---|---|
| **Acme Inc.** | `test@example.com` | `password` | your real `.env` `HIKBRIDGE_API_KEY` (works out of the box) |
| **Globex Corp.** | `globex@example.com` | `password` | random placeholder — swap a second real key into `DatabaseSeeder`, or set it from **Settings → Business**, to see two live tenants |

Log in as each to confirm the isolation: they never share devices, persons, or events.

Or register a brand-new business from the sign-up screen, and manage its key later under **Settings → Business** (see [Multi-tenancy](#multi-tenancy)).

---

## Environment variables

```dotenv
# Seed/fallback key. In this multi-tenant app each business supplies its own
# key (stored on the businesses table). This value only seeds the first demo
# tenant (Acme) and is used for any unauthenticated/global call.
HIKBRIDGE_API_KEY=hbk_your_key_here

# Optional — defaults are shown
HIKBRIDGE_BASE_URL=https://devicebridge.blendsnpearls.com/api
HIKBRIDGE_TIMEOUT=30

# Optional — used to verify inbound webhook signatures (the API returns it once on creation)
HIKBRIDGE_WEBHOOK_SECRET=whsec_...
```

The full config is published at `config/hikbridge.php`. Retries run only on connection-level failures (3 attempts, 100 ms back-off); HTTP error responses map straight to typed exceptions.

> Because keys live on the `businesses` table, `HIKBRIDGE_API_KEY` is **not** how a logged-in user authenticates — their business's key is injected per request. See [Multi-tenancy](#multi-tenancy).

---

## Development server

```bash
composer dev
```

This runs four concurrent processes: Laravel server, queue worker, Pail log viewer, and Vite. Visit `http://localhost:8000`.

---

## Multi-tenancy

Each **business** is a tenant that owns its own HikBridge API key. Every user belongs to one business, and the SDK is bound so that all calls made during a request use that user's business key. Since HikBridge scopes a key to a single business server-side, this is what enforces isolation — a user in Business A physically cannot reach Business B's devices.

### How it works

The `businesses` table stores each tenant's credentials; `hik_api_key` (and `hik_webhook_secret`) are **encrypted at rest** via Eloquent's `encrypted` cast:

```php
// app/Models/Business.php
protected function casts(): array
{
    return [
        'hik_api_key'        => 'encrypted',
        'hik_webhook_secret' => 'encrypted',
    ];
}
```

The SDK package binds its client as a singleton from global config. We override that with a **scoped (per-request)** binding that swaps in the authenticated user's business key:

```php
// app/Providers/AppServiceProvider.php
$this->app->scoped(HikBridgeClient::class, function ($app) {
    $config = $app['config']['hikbridge'];

    if ($business = auth()->user()?->business) {
        $config['api_key'] = $business->hik_api_key;

        if (! empty($business->hik_base_url)) {
            $config['base_url'] = $business->hik_base_url;
        }
    }

    return new HikBridgeClient($config);
});
```

Every Livewire page (`devices()`, `persons()`, `events()`, `webhooks()`, `business()`) then transparently talks to the right tenant — no per-call plumbing. With no authenticated user, it falls back to the `HIKBRIDGE_API_KEY` config value.

### Onboarding

Registration provisions a new tenant: the sign-up form collects a **business name** and a **HikBridge API key** (`hbk_...`) alongside the usual fields, and `App\Actions\Fortify\CreateNewUser` creates the `Business` + `User` in a single transaction.

### Managing the key later

Logged-in users can view and rotate their business's key at **Settings → Business** (`/settings/business`, `resources/views/pages/settings/⚡business.blade.php`). The form updates the business name, API key, and an optional base-URL override. The current key is only ever shown **masked** (`hbk_xxxx••••••xxxx`); leaving the key field blank keeps the existing value, and a new key must start with `hbk_`.

### Verifying isolation

`tests/Feature/TenancyTest.php` proves it: acting as user A, the outgoing request carries A's key and never B's; acting as user B, it carries B's key. To see it by hand, log in as the two [seeded accounts](#demo-login-accounts) and compare what each can list.

---

## SDK installation (for your own project)

```bash
composer require nugsoft/hikbridge-laravel-sdk
php artisan vendor:publish --tag=hikbridge-config
```

Add the env vars above to your `.env`, then use the `HikBridge` facade anywhere in your app.

---

## SDK usage patterns

### Facade import

```php
use Nugsoft\HikBridge\Facades\HikBridge;
use Nugsoft\HikBridge\Exceptions\HikBridgeException;
use Nugsoft\HikBridge\Exceptions\ValidationException;
use Nugsoft\HikBridge\PendingOperation;
```

### Listing resources

```php
$response = HikBridge::devices()->list(['per_page' => 100]);
$devices  = $response['data'] ?? [];

$response = HikBridge::persons()->list(['per_page' => 100]);
$persons  = $response['data'] ?? [];
```

### Creating a person

Person creation may be asynchronous (the device needs to sync). The SDK returns a `PendingOperation` in that case.

```php
$result = HikBridge::persons()->create([
    'person_code' => 'EMP-001',
    'first_name'  => 'Jane',
    'last_name'   => 'Doe',
    'status'      => 'active',
]);

if ($result instanceof PendingOperation) {
    $result->waitUntilDone(timeout: 30); // poll until the device confirms
}
```

To sync a person to a specific device, pass `device_id` in the create payload.

### Updating and deleting

```php
HikBridge::persons()->update($personId, ['status' => 'inactive']);

$op = HikBridge::persons()->delete($personId);
$op->waitUntilDone(timeout: 30);
```

### Biometrics

```php
$bio = HikBridge::biometrics($personId);

// Summary of what's enrolled on a device
$summary = $bio->summary($deviceId)['data'];

// Face — upload a base64 image or trigger on-device capture
$bio->uploadFace($deviceId, base64_encode(file_get_contents($path)));
$bio->captureFace($deviceId);
$bio->deleteFace($deviceId);

// Fingerprint (fingerIndex 0–9; 0 = right thumb, 5 = left thumb)
$bio->captureFingerprint($deviceId, $fingerIndex);
$bio->deleteFingerprint($deviceId, $fingerIndex);

// Access card
$bio->addAccessCard($deviceId, $cardNo, $cardType); // cardType 1 = normal
$bio->deleteAccessCard($deviceId, $cardNo);
```

### Access events with cursor pagination

```php
$cursor = null;
do {
    $params = ['from' => $from, 'to' => $to, 'per_page' => 100];
    if ($cursor) {
        $params['cursor'] = $cursor;
    }
    $response = HikBridge::events()->list($params);
    $events   = array_merge($events, $response['data'] ?? []);
    $cursor   = $response['meta']['next_cursor'] ?? null;
} while ($cursor);
```

Available filter params: `person_code`, `event_type`, `from` (ISO 8601), `to` (ISO 8601).

### Triggering a sync

```php
HikBridge::events()->triggerSync();
```

### Webhooks

```php
// Register
$response = HikBridge::webhooks()->create([
    'url'         => 'https://yourapp.com/webhooks/hikbridge',
    'description' => 'Production receiver',
    'event_types' => ['access.event', 'person.synced'], // or ['*'] for all
]);
// Save $response['data']['secret'] — it is shown only once

// List / delete
HikBridge::webhooks()->list();
HikBridge::webhooks()->delete($webhookId);

// Test
HikBridge::webhooks()->sendTestPing($webhookId);

// Delivery log
$deliveries = HikBridge::webhooks()->deliveries($webhookId);
```

### Business

```php
$business = HikBridge::business()->get();
// $business['data']['id'], $business['data']['name'], ...
```

> This resolves against the **current user's** business key (see [Multi-tenancy](#multi-tenancy)), so it always returns the tenant the logged-in user belongs to.

---

## Receiving webhooks

HikBridge signs every request with `X-HikBridge-Signature: sha256=<hmac>`.

### Route (no CSRF)

```php
// routes/web.php
Route::post('/webhooks/hikbridge', [WebhookController::class, 'receive'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
```

### Controller

```php
// app/Http/Controllers/WebhookController.php
public function receive(Request $request): Response
{
    $secret = config('hikbridge.webhook_secret');

    if ($secret) {
        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);
        if (! hash_equals($expected, (string) $request->header('X-HikBridge-Signature'))) {
            return response('Unauthorized', 401);
        }
    }

    $payload   = $request->json()->all();
    $eventType = $payload['event_type'] ?? $payload['type'] ?? 'unknown';

    if (in_array($eventType, ['access.event', 'person.synced'])) {
        $data = $payload['data'] ?? $payload;
        HikEvent::create([
            'event_type'  => $eventType,
            'person_code' => $data['person_code'] ?? null,
            'device_id'   => $data['device_id'] ?? null,
            'device_name' => $data['device_name'] ?? null,
            'occurred_at' => isset($data['occurred_at'])
                ? \Carbon\Carbon::parse($data['occurred_at'])
                : now(),
            'payload'     => $payload,
        ]);
    }

    return response('OK', 200);
}
```

### hik_events migration

```php
Schema::create('hik_events', function (Blueprint $table) {
    $table->id();
    $table->string('event_type')->index();
    $table->string('person_code')->nullable()->index();
    $table->unsignedInteger('device_id')->nullable()->index();
    $table->string('device_name')->nullable();
    $table->timestamp('occurred_at')->nullable()->index();
    $table->json('payload');
    $table->timestamps();
});
```

---

## Error handling

All SDK methods throw `HikBridgeException` on API errors. Validation errors throw the narrower `ValidationException` which exposes field-level messages.

```php
try {
    HikBridge::persons()->create([...]);
} catch (ValidationException $e) {
    $errors = $e->errors(); // ['person_code' => ['already exists']]
} catch (HikBridgeException $e) {
    $message = $e->getMessage();
}
```

---

## Project structure (relevant files)

```
app/
  Actions/Fortify/CreateNewUser.php       — registration: creates Business + User
  Http/Controllers/WebhookController.php  — webhook receiver
  Livewire/
    Devices/Index.php                     — device listing
    Employees/Index.php                   — employee CRUD + sync
    Employees/Show.php                    — biometrics management
    Events/Index.php                      — access event log
    Webhooks/Index.php                    — webhook management
    Attendance/Report.php                 — date-range attendance grid
  Models/
    Business.php                          — tenant + encrypted HikBridge key
    HikEvent.php                          — local event store
  Providers/AppServiceProvider.php        — per-request tenant SDK binding
resources/views/pages/settings/⚡business.blade.php — Settings → Business (edit key)
config/hikbridge.php                      — SDK configuration
database/
  migrations/*_create_businesses_table.php
  migrations/*_add_business_id_to_users_table.php
  migrations/*_create_hik_events_table.php
  seeders/DatabaseSeeder.php              — two demo tenants (Acme, Globex)
routes/web.php                            — all routes including webhook receiver
```

---

## Running tests

```bash
composer test
```

This runs Pint (code style), PHPStan (static analysis), and PHPUnit in sequence.
