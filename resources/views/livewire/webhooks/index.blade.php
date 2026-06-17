<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Webhooks</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                HikBridge pushes real-time events to your registered endpoints.
            </flux:text>
        </div>
    </div>

    @if ($error)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ $error }}</flux:callout.text>
        </flux:callout>
    @endif
    @if ($success)
        <flux:callout variant="success" icon="check-circle">
            <flux:callout.text>{{ $success }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- One-time secret banner --}}
    @if ($newWebhookSecret)
        <flux:callout variant="warning" icon="key">
            <flux:callout.heading>Save your webhook secret now</flux:callout.heading>
            <flux:callout.text>
                This is shown <strong>once only</strong>. Copy it and add it to your
                <code class="rounded bg-amber-100 px-1 dark:bg-amber-900">HIKBRIDGE_WEBHOOK_SECRET</code>
                in <code class="rounded bg-amber-100 px-1 dark:bg-amber-900">.env</code>.
            </flux:callout.text>
            <div class="mt-3 flex items-center gap-2">
                <code class="flex-1 rounded-lg border border-amber-200 bg-white px-3 py-2 font-mono text-sm break-all dark:border-amber-800 dark:bg-zinc-900">{{ $newWebhookSecret }}</code>
                <button
                    onclick="navigator.clipboard.writeText('{{ $newWebhookSecret }}'); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000)"
                    class="shrink-0 rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm font-medium text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:bg-zinc-900 dark:text-amber-400"
                >Copy</button>
            </div>
            <div class="mt-3">
                <flux:button size="sm" wire:click="dismissNewSecret">Dismiss</flux:button>
            </div>
        </flux:callout>
    @elseif ($lastCreateResponse !== null && !$newWebhookSecret)
        {{-- Webhook was created but no secret found — show raw response to locate the key --}}
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.heading>No secret found in the API response</flux:callout.heading>
            <flux:callout.text>
                The webhook was created but the response did not contain a recognisable secret field.
                Check the raw response below to find the correct key, then set it manually in your
                <code class="rounded bg-red-100 px-1 dark:bg-red-900">.env</code>.
            </flux:callout.text>
            <details class="mt-3">
                <summary class="cursor-pointer text-sm font-medium">Show raw API response</summary>
                <pre class="mt-2 overflow-x-auto rounded-lg bg-zinc-900 p-3 text-xs text-green-400">{{ json_encode($lastCreateResponse, JSON_PRETTY_PRINT) }}</pre>
            </details>
            <div class="mt-3">
                <flux:button size="sm" wire:click="$set('lastCreateResponse', null)">Dismiss</flux:button>
            </div>
        </flux:callout>
    @endif

    {{-- How it works + receiver URL --}}
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <flux:heading>How webhooks work here</flux:heading>
        </div>
        <div class="grid gap-0 divide-y divide-zinc-200 dark:divide-zinc-700 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="flex gap-3 px-5 py-4">
                <div class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900 dark:text-blue-400">1</div>
                <div>
                    <flux:text class="font-medium">Register your URL</flux:text>
                    <flux:text class="mt-1 text-sm text-zinc-500">
                        Tell HikBridge where to send events by clicking
                        <strong>"Register this app"</strong> below.
                    </flux:text>
                </div>
            </div>
            <div class="flex gap-3 px-5 py-4">
                <div class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900 dark:text-blue-400">2</div>
                <div>
                    <flux:text class="font-medium">Save the secret</flux:text>
                    <flux:text class="mt-1 text-sm text-zinc-500">
                        Copy the <code class="rounded bg-zinc-200 px-1 dark:bg-zinc-700">whsec_…</code>
                        secret into <code class="rounded bg-zinc-200 px-1 dark:bg-zinc-700">HIKBRIDGE_WEBHOOK_SECRET</code> in your .env.
                    </flux:text>
                </div>
            </div>
            <div class="flex gap-3 px-5 py-4">
                <div class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900 dark:text-blue-400">3</div>
                <div>
                    <flux:text class="font-medium">Events flow automatically</flux:text>
                    <flux:text class="mt-1 text-sm text-zinc-500">
                        Every door scan fires an <code class="rounded bg-zinc-200 px-1 dark:bg-zinc-700">access.event</code>
                        to this app in real-time — no sync button needed.
                    </flux:text>
                </div>
            </div>
        </div>

        {{-- Receiver URL row --}}
        <div class="flex flex-wrap items-center gap-3 border-t border-zinc-200 bg-white px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800/50">
            <div class="flex flex-1 items-center gap-2 min-w-0">
                <flux:icon.arrow-right class="size-4 shrink-0 text-zinc-400" />
                <flux:text class="shrink-0 text-sm font-medium text-zinc-600 dark:text-zinc-400">Your receiver URL:</flux:text>
                <code class="truncate rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 font-mono text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                    {{ url('/webhooks/hikbridge') }}
                </code>
                <button
                    onclick="navigator.clipboard.writeText('{{ url('/webhooks/hikbridge') }}'); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000)"
                    class="shrink-0 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400"
                >Copy</button>
            </div>
            <flux:button variant="primary" icon="plus" wire:click="prefillReceiverUrl">
                Register this app
            </flux:button>
        </div>
    </div>

    {{-- Registered webhooks --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <flux:heading>Registered Endpoints</flux:heading>
            <flux:button size="sm" variant="ghost" icon="plus" wire:click="$set('showCreateModal', true)">
                Add custom
            </flux:button>
        </div>

        @if (count($webhooks) === 0)
            <div class="p-10 text-center">
                <flux:icon.globe-alt class="mx-auto mb-3 size-12 text-zinc-300" />
                <flux:text class="text-zinc-500">No webhooks registered yet.</flux:text>
                <div class="mt-3">
                    <flux:button variant="primary" wire:click="prefillReceiverUrl">
                        Register this app with HikBridge
                    </flux:button>
                </div>
            </div>
        @else
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($webhooks as $webhook)
                    @php
                        $isThisApp = str_contains($webhook['url'] ?? '', request()->getHost());
                    @endphp
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    @if ($isThisApp)
                                        <flux:badge color="green" size="sm">This app</flux:badge>
                                    @endif
                                    <flux:text class="font-mono text-sm font-medium break-all">
                                        {{ $webhook['url'] ?? '—' }}
                                    </flux:text>
                                </div>
                                @if ($webhook['description'] ?? null)
                                    <flux:text class="mt-1 text-sm text-zinc-500">{{ $webhook['description'] }}</flux:text>
                                @endif
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($webhook['event_types'] ?? [] as $type)
                                        <flux:badge size="sm" color="blue">{{ $type }}</flux:badge>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="paper-airplane"
                                    wire:click="sendTestPing({{ $webhook['id'] }})"
                                >
                                    Ping
                                </flux:button>
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="list-bullet"
                                    wire:click="viewDeliveries({{ $webhook['id'] }})"
                                >
                                    Deliveries
                                </flux:button>
                                <flux:button
                                    size="sm"
                                    variant="danger"
                                    icon="trash"
                                    wire:click="deleteWebhook({{ $webhook['id'] }})"
                                    wire:confirm="Delete this webhook? HikBridge will stop sending events to it."
                                >
                                    Delete
                                </flux:button>
                            </div>
                        </div>

                        {{-- Deliveries panel --}}
                        @if ($viewDeliveriesFor === $webhook['id'])
                            <div class="mt-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-2 dark:border-zinc-700">
                                    <flux:text class="font-medium text-sm">Recent Deliveries</flux:text>
                                    <div class="flex items-center gap-2">
                                        <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="refreshDeliveries">
                                            Refresh
                                        </flux:button>
                                        <flux:button size="sm" variant="ghost" wire:click="$set('viewDeliveriesFor', null)">Close</flux:button>
                                    </div>
                                </div>
                                @forelse ($deliveries as $delivery)
                                    @php
                                        $status    = $delivery['status'] ?? $delivery['delivery_status'] ?? null;
                                        $eventType = $delivery['event_type'] ?? $delivery['type'] ?? '—';
                                        $timestamp = $delivery['delivered_at'] ?? $delivery['attempted_at'] ?? $delivery['created_at'] ?? null;
                                        $httpStatus = $delivery['response_status'] ?? $delivery['http_status'] ?? null;
                                    @endphp
                                    <div class="flex items-center justify-between px-4 py-2.5 text-sm border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                                        <div class="flex items-center gap-2">
                                            <flux:badge
                                                :color="in_array($status, ['delivered', 'success', 'succeeded']) ? 'green' : ($status === 'pending' ? 'amber' : 'red')"
                                                size="sm"
                                            >
                                                {{ $status ?? '—' }}
                                            </flux:badge>
                                            <flux:text class="text-zinc-600 dark:text-zinc-400">{{ $eventType }}</flux:text>
                                            @if ($httpStatus)
                                                <span class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-xs text-zinc-500 dark:bg-zinc-800">
                                                    HTTP {{ $httpStatus }}
                                                </span>
                                            @endif
                                        </div>
                                        <flux:text class="text-zinc-400 text-xs">
                                            {{ $timestamp ? \Carbon\Carbon::parse($timestamp)->diffForHumans() : '' }}
                                        </flux:text>
                                    </div>
                                @empty
                                    <div class="px-4 py-6 text-center">
                                        <flux:text class="text-zinc-500 text-sm">
                                            No deliveries recorded yet.
                                        </flux:text>
                                        <flux:text class="mt-1 text-xs text-zinc-400">
                                            Deliveries may take a few seconds to appear after a ping.
                                            Hit Refresh to check again.
                                        </flux:text>
                                        @if (config('app.debug') && $rawDeliveriesResponse !== null)
                                            <details class="mt-3 text-left">
                                                <summary class="cursor-pointer text-xs text-zinc-400">Raw API response</summary>
                                                <pre class="mt-2 overflow-x-auto rounded bg-zinc-100 p-2 text-xs dark:bg-zinc-800">{{ json_encode($rawDeliveriesResponse, JSON_PRETTY_PRINT) }}</pre>
                                            </details>
                                        @endif
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Create / custom URL modal --}}
    <flux:modal wire:model="showCreateModal" class="w-full max-w-md">
        <div class="p-6">
            <flux:heading size="lg">Register Webhook</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                HikBridge will POST events to this URL when they happen.
            </flux:text>
        </div>

        <div class="flex flex-col gap-4 px-6 pb-2">
            <flux:field>
                <flux:label>Endpoint URL</flux:label>
                <flux:input wire:model="url" type="url" placeholder="https://your-app.com/webhooks/hikbridge" />
                <flux:error name="url" />
            </flux:field>

            <flux:field>
                <flux:label>Description <span class="text-zinc-400">(optional)</span></flux:label>
                <flux:input wire:model="description" placeholder="HikBridge HR — production" />
            </flux:field>

            <flux:field>
                <flux:label>Events to receive</flux:label>
                <div class="mt-1 flex flex-col gap-2">
                    @foreach (['access.event' => 'Door scans (live access events)', 'person.synced' => 'Person sync completions', '*' => 'All events'] as $type => $label)
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input
                                type="checkbox"
                                wire:click="toggleEventType('{{ $type }}')"
                                @checked(in_array($type, $event_types))
                                class="rounded border-zinc-300"
                            />
                            <span class="text-sm">
                                <code class="rounded bg-zinc-100 px-1 text-xs dark:bg-zinc-800">{{ $type }}</code>
                                <span class="ml-1 text-zinc-500">{{ $label }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <flux:error name="event_types" />
            </flux:field>
        </div>

        <div class="flex justify-end gap-3 border-t border-zinc-200 p-6 dark:border-zinc-700">
            <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="createWebhook" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="createWebhook">Register Webhook</span>
                <span wire:loading wire:target="createWebhook">Registering…</span>
            </flux:button>
        </div>
    </flux:modal>
</div>
