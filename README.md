# HikBridge Laravel SDK — Demo App

A reference implementation of the [`nugsoft/hikbridge-laravel-sdk`](https://packagist.org/packages/nugsoft/hikbridge-laravel-sdk). Every feature of the SDK is exercised here so you can copy patterns directly into your own project.

---

## What this demo covers

| Section | SDK calls demonstrated |
|---|---|
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
- A HikBridge API key (`hbk_...`)

---

## Quick start

```bash
# 1. Clone and install
git clone https://github.com/nugsoft/hikbridge-demo.git hikbridge-demo
cd hikbridge-demo
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure HikBridge (see below)
# edit .env

# 4. Database
touch database/database.sqlite   # default is SQLite
php artisan migrate

# 5. Assets
npm install && npm run build

# 6. Create a user account, then log in
php artisan tinker
>>> App\Models\User::factory()->create(['email' => 'you@example.com', 'password' => bcrypt('password')])
```

Or run everything in one step with the Composer script:

```bash
composer setup
```

---

## Environment variables

```dotenv
# Required
HIKBRIDGE_API_KEY=hbk_your_key_here

# Optional — defaults are shown
HIKBRIDGE_BASE_URL=https://devicebridge.blendsnpearls.com/api
HIKBRIDGE_TIMEOUT=30

# Set this after creating a webhook (the API returns it once on creation)
HIKBRIDGE_WEBHOOK_SECRET=whsec_...
```

The full config is published at `config/hikbridge.php`. Retries are on by default (3 attempts, 100 ms back-off).

---

## Development server

```bash
composer dev
```

This runs four concurrent processes: Laravel server, queue worker, Pail log viewer, and Vite. Visit `http://localhost:8000`.

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

### Organization

```php
$org = HikBridge::organization()->get();
```

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
  Http/Controllers/WebhookController.php  — webhook receiver
  Livewire/
    Devices/Index.php                     — device listing
    Employees/Index.php                   — employee CRUD + sync
    Employees/Show.php                    — biometrics management
    Events/Index.php                      — access event log
    Webhooks/Index.php                    — webhook management
    Attendance/Report.php                 — date-range attendance grid
  Models/HikEvent.php                     — local event store
config/hikbridge.php                      — SDK configuration
database/migrations/*_create_hik_events_table.php
routes/web.php                            — all routes including webhook receiver
```

---

## Running tests

```bash
composer test
```

This runs Pint (code style), PHPStan (static analysis), and PHPUnit in sequence.
