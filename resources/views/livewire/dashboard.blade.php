<div class="flex flex-col gap-6">
    {{-- Alerts --}}
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

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Access Dashboard</flux:heading>
            <div class="mt-1 flex items-center gap-2">
                <flux:text class="text-zinc-500">Real-time overview of access events and enrolled persons.</flux:text>
                @if ($lastSynced)
                    <flux:text class="text-zinc-400">· Last synced: {{ $lastSynced }}</flux:text>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2">
            <flux:button size="sm" :variant="$period === 'today' ? 'primary' : 'ghost'" wire:click="setPeriod('today')">Today</flux:button>
            <flux:button size="sm" :variant="$period === 'week' ? 'primary' : 'ghost'" wire:click="setPeriod('week')">This Week</flux:button>
            <flux:button size="sm" :variant="$period === 'month' ? 'primary' : 'ghost'" wire:click="setPeriod('month')">This Month</flux:button>
            <flux:button size="sm" icon="arrow-path" wire:click="triggerSync" wire:loading.attr="disabled">
                Sync Events
            </flux:button>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-5">
        {{-- Access Rate --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950">
                <flux:icon.chart-bar class="size-5 text-blue-500" />
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-white">
                    {{ $totalPersons > 0 ? round(($eventsToday / max($activePersons, 1)) * 100) . '%' : '0%' }}
                </div>
                <div class="text-xs text-zinc-500">Access Rate</div>
            </div>
        </div>

        {{-- Active persons --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-green-50 dark:bg-green-950">
                <flux:icon.user-group class="size-5 text-green-500" />
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-white">{{ $activePersons }}</div>
                <div class="text-xs text-zinc-500">of {{ $totalPersons }} active</div>
            </div>
        </div>

        {{-- Events today --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-950">
                <flux:icon.bolt class="size-5 text-violet-500" />
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-white">{{ $eventsToday }}</div>
                <div class="text-xs text-zinc-500">Events Today</div>
            </div>
        </div>

        {{-- Sync Pending --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950">
                <flux:icon.clock class="size-5 text-amber-500" />
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-white">{{ $syncPending }}</div>
                <div class="text-xs text-zinc-500">Sync Pending</div>
            </div>
        </div>

        {{-- Sync Failed --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-red-50 dark:bg-red-950">
                <flux:icon.exclamation-triangle class="size-5 text-red-500" />
            </div>
            <div>
                <div class="text-xl font-bold text-zinc-900 dark:text-white">{{ $syncFailed }}</div>
                <div class="text-xs text-zinc-500">Sync Failed</div>
            </div>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Attendance — Last 7 Days --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading class="mb-1">Attendance — Last 7 Days</flux:heading>
            <div class="mb-4 flex items-center gap-4 text-xs text-zinc-500">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-0.5 w-4 bg-blue-500"></span> Unique Persons
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block h-0.5 w-4 border-t-2 border-dashed border-zinc-400"></span> Total Events
                </span>
            </div>

            @if (count($chartLabels) > 0)
                <div
                    x-data="{
                        chart: null,
                        init() {
                            this.chart = new Chart(this.$refs.canvas, {
                                type: 'line',
                                data: {
                                    labels: {{ json_encode($chartLabels) }},
                                    datasets: [
                                        {
                                            label: 'Unique Persons',
                                            data: {{ json_encode($chartUnique) }},
                                            borderColor: 'rgb(59,130,246)',
                                            backgroundColor: 'rgba(59,130,246,0.08)',
                                            tension: 0.4,
                                            fill: true,
                                            pointRadius: 4,
                                        },
                                        {
                                            label: 'Total Events',
                                            data: {{ json_encode($chartTotal) }},
                                            borderColor: 'rgb(161,161,170)',
                                            borderDash: [6,3],
                                            tension: 0.4,
                                            fill: false,
                                            pointRadius: 4,
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: {
                                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                                        x: { grid: { display: false } }
                                    }
                                }
                            });
                        }
                    }"
                    class="relative h-52"
                >
                    <canvas x-ref="canvas"></canvas>
                </div>
            @else
                <div class="flex h-52 items-center justify-center text-zinc-400">No data available</div>
            @endif
        </div>

        {{-- Live Feed --}}
        <div class="flex flex-col rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center gap-2 border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <flux:heading>Live Feed</flux:heading>
                <span class="size-2 rounded-full bg-green-500 animate-pulse"></span>
            </div>
            <div class="flex-1 divide-y divide-zinc-100 overflow-y-auto dark:divide-zinc-800" style="max-height: 220px">
                @forelse ($liveFeed as $event)
                    <div class="flex items-center justify-between px-5 py-2.5">
                        <div class="flex items-center gap-3">
                            <flux:avatar :name="$event['person_code'] ?? '?'" size="xs" />
                            <div>
                                <div class="text-sm font-medium text-zinc-800 dark:text-white">
                                    {{ $event['person_code'] ?? '—' }}
                                </div>
                                <div class="text-xs text-zinc-400">
                                    {{ $event['event_type'] ?? '' }}
                                    @if ($event['device_id'] ?? null) · Device {{ $event['device_id'] }} @endif
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <flux:badge
                                size="sm"
                                :color="str_contains($event['event_type'] ?? '', 'granted') ? 'green' : (str_contains($event['event_type'] ?? '', 'denied') ? 'red' : 'zinc')"
                            >
                                {{ str_contains($event['event_type'] ?? '', 'granted') ? 'Granted' : (str_contains($event['event_type'] ?? '', 'denied') ? 'Denied' : 'Event') }}
                            </flux:badge>
                            <div class="mt-0.5 text-xs text-zinc-400">
                                {{ isset($event['occurred_at']) ? \Carbon\Carbon::parse($event['occurred_at'])->format('H:i:s') : '' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex h-full items-center justify-center py-10 text-zinc-400 text-sm">
                        No events today
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Bottom row --}}
    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Today's Roster --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <flux:heading>Today's Roster</flux:heading>
                <flux:text class="text-sm text-zinc-500">{{ count($todayRoster) }} people</flux:text>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($todayRoster as $entry)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <flux:avatar :name="$entry['person_code'] ?? '?'" size="sm" />
                            <div>
                                <div class="text-sm font-medium text-zinc-800 dark:text-white">
                                    {{ $entry['person_code'] ?? '—' }}
                                </div>
                                <div class="text-xs text-zinc-400">{{ $entry['event_type'] ?? '' }}</div>
                            </div>
                        </div>
                        <div class="text-xs text-zinc-400">
                            {{ isset($entry['occurred_at']) ? \Carbon\Carbon::parse($entry['occurred_at'])->format('H:i') : '' }}
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-sm text-zinc-400">No entries today</div>
                @endforelse
            </div>
            @if (count($todayRoster) > 0)
                <div class="border-t border-zinc-200 px-5 py-3 dark:border-zinc-700">
                    <flux:link :href="route('attendance.report')" wire:navigate class="text-sm">
                        View full attendance report →
                    </flux:link>
                </div>
            @endif
        </div>

        {{-- Quick Links --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <flux:heading>Quick Actions</flux:heading>
            </div>
            <div class="grid grid-cols-2 gap-3 p-5">
                <flux:button variant="ghost" icon="users" :href="route('employees.index')" wire:navigate class="justify-start">
                    Employees
                </flux:button>
                <flux:button variant="ghost" icon="server" :href="route('devices.index')" wire:navigate class="justify-start">
                    Devices
                </flux:button>
                <flux:button variant="ghost" icon="clock" :href="route('events.index')" wire:navigate class="justify-start">
                    Access Events
                </flux:button>
                <flux:button variant="ghost" icon="calendar-days" :href="route('attendance.report')" wire:navigate class="justify-start">
                    Attendance
                </flux:button>
                <flux:button variant="ghost" icon="globe-alt" :href="route('webhooks.index')" wire:navigate class="justify-start">
                    Webhooks
                </flux:button>
                <flux:button variant="ghost" icon="arrow-path" wire:click="triggerSync" class="justify-start">
                    Sync Now
                </flux:button>
            </div>
        </div>
    </div>
</div>
