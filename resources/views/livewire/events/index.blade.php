<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Access Events</flux:heading>
        <flux:button icon="arrow-path" variant="ghost" wire:click="triggerSync">
            Trigger Sync
        </flux:button>
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

    {{-- Filters --}}
    <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-3 sm:grid-cols-4">
            <flux:field>
                <flux:label>Person Code</flux:label>
                <flux:input wire:model="filterPersonCode" placeholder="EMP001" />
            </flux:field>
            <flux:field>
                <flux:label>Event Type</flux:label>
                <flux:input wire:model="filterEventType" placeholder="access.granted" />
            </flux:field>
            <flux:field>
                <flux:label>From</flux:label>
                <flux:input type="datetime-local" wire:model="filterFrom" />
            </flux:field>
            <flux:field>
                <flux:label>To</flux:label>
                <flux:input type="datetime-local" wire:model="filterTo" />
            </flux:field>
        </div>
        <div class="mt-3 flex gap-2">
            <flux:button size="sm" variant="primary" wire:click="applyFilters">Apply</flux:button>
            <flux:button size="sm" variant="ghost" wire:click="resetFilters">Reset</flux:button>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        @if (count($events) === 0)
            <div class="p-10 text-center">
                <flux:icon.clock class="mx-auto mb-3 size-12 text-zinc-400" />
                <flux:text class="text-zinc-500">No events found.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="px-4 py-3 font-medium text-zinc-500">Time</th>
                        <th class="px-4 py-3 font-medium text-zinc-500">Event</th>
                        <th class="px-4 py-3 font-medium text-zinc-500">Person</th>
                        <th class="px-4 py-3 font-medium text-zinc-500">Device</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($events as $event)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 font-mono text-zinc-500 text-xs">
                                {{ isset($event['occurred_at']) ? \Carbon\Carbon::parse($event['occurred_at'])->format('Y-m-d H:i:s') : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge
                                    :color="str_contains($event['event_type'] ?? '', 'granted') ? 'green' : (str_contains($event['event_type'] ?? '', 'denied') ? 'red' : 'zinc')"
                                    size="sm"
                                >
                                    {{ $event['event_type'] ?? 'unknown' }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 font-mono text-zinc-500">
                                {{ $event['person_code'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-500">
                                {{ $event['device_id'] ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            @if ($nextCursor)
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <flux:button size="sm" variant="ghost" wire:click="nextPage">
                        Load more
                    </flux:button>
                </div>
            @endif
        @endif
    </div>
</div>
