<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Attendance Report</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                Daily attendance matrix based on access events.
            </flux:text>
        </div>

        {{-- Date range + generate --}}
        <div class="flex flex-wrap items-end gap-3">
            <flux:field>
                <flux:label>From</flux:label>
                <flux:input type="date" wire:model="from" />
            </flux:field>
            <flux:field>
                <flux:label>To</flux:label>
                <flux:input type="date" wire:model="to" />
            </flux:field>
            <flux:button variant="primary" icon="arrow-path" wire:click="generateReport" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generateReport">Generate</span>
                <span wire:loading wire:target="generateReport">Loading…</span>
            </flux:button>
        </div>
    </div>

    @if ($error)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ $error }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Summary stat --}}
    @if (count($persons) > 0)
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500">Period</flux:text>
                <flux:heading class="mt-1">{{ count($dates) }} days</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500">Unique Persons</flux:text>
                <flux:heading class="mt-1">{{ count($persons) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500">Total Check-ins</flux:text>
                <flux:heading class="mt-1">
                    {{ collect($report)->flatMap(fn ($day) => collect($day)->pluck('count'))->sum() }}
                </flux:heading>
            </div>
        </div>
    @endif

    {{-- Matrix table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        @if (count($persons) === 0)
            <div class="p-10 text-center">
                <flux:icon.calendar-days class="mx-auto mb-3 size-12 text-zinc-400" />
                <flux:text class="text-zinc-500">No attendance data for this period.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50 text-left dark:border-zinc-700 dark:bg-zinc-800/50">
                        <th class="sticky left-0 z-10 bg-zinc-50 px-4 py-3 font-medium text-zinc-500 dark:bg-zinc-800/50">
                            Person
                        </th>
                        @foreach ($dates as $date)
                            <th class="min-w-[90px] px-3 py-3 text-center font-medium text-zinc-500">
                                <div>{{ \Carbon\Carbon::parse($date)->format('D') }}</div>
                                <div class="text-xs font-normal">{{ \Carbon\Carbon::parse($date)->format('M j') }}</div>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-right font-medium text-zinc-500">Days Present</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($persons as $personCode)
                        @php
                            $daysPresent = 0;
                            foreach ($dates as $date) {
                                if (isset($report[$date][$personCode])) $daysPresent++;
                            }
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="sticky left-0 z-10 bg-white px-4 py-3 dark:bg-zinc-900">
                                <div class="flex items-center gap-2">
                                    <flux:avatar :name="$personCode" size="xs" />
                                    <span class="font-mono font-medium text-zinc-800 dark:text-white">{{ $personCode }}</span>
                                </div>
                            </td>
                            @foreach ($dates as $date)
                                @php $entry = $report[$date][$personCode] ?? null; @endphp
                                <td class="px-3 py-3 text-center">
                                    @if ($entry)
                                        <div
                                            class="group relative mx-auto flex flex-col items-center"
                                            title="{{ $entry['first_in'] ?? '' }} – {{ $entry['last_out'] ?? '' }} ({{ $entry['count'] ?? 0 }} events)"
                                        >
                                            <div class="flex size-7 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                                <flux:icon.check class="size-4 text-green-600 dark:text-green-400" />
                                            </div>
                                            <div class="mt-0.5 text-xs text-zinc-400">
                                                {{ $entry['first_in'] ?? '' }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="mx-auto size-7 rounded-full bg-zinc-100 dark:bg-zinc-800"></div>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-right">
                                <flux:badge
                                    :color="$daysPresent === count($dates) ? 'green' : ($daysPresent > 0 ? 'blue' : 'zinc')"
                                    size="sm"
                                >
                                    {{ $daysPresent }} / {{ count($dates) }}
                                </flux:badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Legend --}}
            <div class="flex items-center gap-4 border-t border-zinc-200 px-5 py-3 text-xs text-zinc-500 dark:border-zinc-700">
                <div class="flex items-center gap-1.5">
                    <div class="flex size-5 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                        <flux:icon.check class="size-3 text-green-600" />
                    </div>
                    Present — hover for first-in / last-out time
                </div>
                <div class="flex items-center gap-1.5">
                    <div class="size-5 rounded-full bg-zinc-100 dark:bg-zinc-800"></div>
                    Absent
                </div>
            </div>
        @endif
    </div>
</div>
