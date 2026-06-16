<?php

namespace App\Http\Controllers;

use App\Models\HikEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function receive(Request $request): Response
    {
        // Verify signature if secret is configured
        $secret = config('hikbridge.webhook_secret');

        if ($secret) {
            $signature = $request->header('X-HikBridge-Signature');
            $expected  = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

            if (! hash_equals($expected, (string) $signature)) {
                Log::warning('HikBridge webhook: invalid signature');
                return response('Unauthorized', 401);
            }
        }

        $payload   = $request->json()->all();
        $eventType = $payload['event_type'] ?? $payload['type'] ?? 'unknown';

        // Only store access and person sync events
        if (in_array($eventType, ['access.event', 'person.synced']) || str_starts_with($eventType, 'access.')) {
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
}
