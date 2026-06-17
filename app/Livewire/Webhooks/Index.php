<?php

namespace App\Livewire\Webhooks;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Nugsoft\HikBridge\Facades\HikBridge;
use Nugsoft\HikBridge\Exceptions\HikBridgeException;
use Nugsoft\HikBridge\Exceptions\ValidationException;

#[Layout('layouts.app', ['title' => 'Webhooks'])]
class Index extends Component
{
    public array $webhooks = [];
    public ?string $error = null;
    public ?string $success = null;

    public bool $showCreateModal = false;
    public ?int $viewDeliveriesFor = null;
    public array $deliveries = [];
    public ?string $newWebhookSecret = null;
    public ?array $rawDeliveriesResponse = null;
    public ?array $lastCreateResponse = null;

    // Create form
    public string $url = '';
    public string $description = '';
    public array $event_types = ['*'];

    public function mount(): void
    {
        $this->loadWebhooks();
    }

    public function loadWebhooks(): void
    {
        try {
            $response = HikBridge::webhooks()->list();
            $this->webhooks = $response['data'] ?? [];
            $this->error = null;
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function createWebhook(): void
    {
        $this->validate([
            'url'         => 'required|url|max:500',
            'description' => 'nullable|string|max:200',
            'event_types' => 'required|array|min:1',
        ]);

        try {
            $response = HikBridge::webhooks()->create([
                'url'         => $this->url,
                'description' => $this->description ?: null,
                'event_types' => $this->event_types,
            ]);

            // The secret can live at different paths depending on API version
            $data = $response['data'] ?? $response;
            $this->newWebhookSecret = $data['secret']
                ?? $data['signing_secret']
                ?? $data['webhook_secret']
                ?? $response['secret']
                ?? null;

            // Store the full response in debug mode so we can find the right key
            $this->lastCreateResponse = $response;

            $this->success = $this->newWebhookSecret
                ? 'Webhook created. Copy the secret below — it will not be shown again.'
                : 'Webhook created. No secret was returned by the API — signature verification will be skipped.';

            $this->reset(['url', 'description', 'event_types', 'showCreateModal']);
            $this->event_types = ['*'];
            $this->loadWebhooks();
        } catch (ValidationException $e) {
            $this->addError('url', collect($e->errors())->flatten()->first() ?? 'Validation failed.');
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function deleteWebhook(int $webhookId): void
    {
        try {
            HikBridge::webhooks()->delete($webhookId);
            $this->success = 'Webhook deleted.';
            $this->loadWebhooks();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function sendTestPing(int $webhookId): void
    {
        try {
            HikBridge::webhooks()->sendTestPing($webhookId);
            $this->success = 'Test ping sent. Delivery records may take a few seconds to appear.';
            // Auto-open deliveries panel so user can refresh in place
            $this->viewDeliveries($webhookId);
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function viewDeliveries(int $webhookId): void
    {
        try {
            $response = HikBridge::webhooks()->deliveries($webhookId);

            // API may return { data: [...] }, { deliveries: [...] }, or a bare array
            $this->deliveries = match (true) {
                isset($response['data']) && is_array($response['data'])        => $response['data'],
                isset($response['deliveries']) && is_array($response['deliveries']) => $response['deliveries'],
                array_is_list($response)                                       => $response,
                default                                                        => [],
            };

            $this->viewDeliveriesFor = $webhookId;
            $this->rawDeliveriesResponse = $response; // stored for debugging
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function refreshDeliveries(): void
    {
        if ($this->viewDeliveriesFor) {
            $this->viewDeliveries($this->viewDeliveriesFor);
        }
    }

    public function dismissNewSecret(): void
    {
        $this->newWebhookSecret = null;
        $this->lastCreateResponse = null;
    }

    public function toggleEventType(string $type): void
    {
        if (in_array($type, $this->event_types)) {
            $this->event_types = array_values(array_filter($this->event_types, fn ($t) => $t !== $type));
        } else {
            $this->event_types[] = $type;
        }
    }

    public function prefillReceiverUrl(): void
    {
        $this->url         = url('/webhooks/hikbridge');
        $this->description = 'HikBridge HR — live access events';
        $this->event_types = ['access.event', 'person.synced'];
        $this->showCreateModal = true;
    }

    public function render()
    {
        return view('livewire.webhooks.index');
    }
}
