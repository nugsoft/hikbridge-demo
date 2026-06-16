<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Devices</flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                Manage Hikvision device connections and assign them to organizations.
            </flux:text>
        </div>
        <flux:button icon="arrow-path" variant="ghost" wire:click="loadDevices">Refresh</flux:button>
    </div>

    @if ($error)
        <flux:callout variant="danger" icon="exclamation-triangle">
            <flux:callout.text>{{ $error }}</flux:callout.text>
        </flux:callout>
    @endif

    {{-- Card grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">

        {{-- Device cards --}}
        @foreach ($devices as $device)
            @php
                $isOnline  = ($device['status'] ?? '') === 'online';
                $name      = $device['name'] ?? $device['ip_address'] ?? "Device #{$device['id']}";
                $ip        = $device['ip_address'] ?? null;
                $serial    = $device['serial_number'] ?? null;
                $type      = $device['connection_type'] ?? $device['type'] ?? null;
                $org       = $device['organization']['name'] ?? $device['org_name'] ?? null;
                $address   = $device['address'] ?? ($ip ? "{$ip}:7660" : null);
                $caps      = $device['capabilities'] ?? [];
            @endphp
            <div class="group relative flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                {{-- Top row: icon + name + menu --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-950">
                            <flux:icon.server class="size-5 text-indigo-500" />
                        </div>
                        <div>
                            <div class="font-semibold text-zinc-900 dark:text-white">{{ $name }}</div>
                            @if ($type)
                                <div class="text-xs text-zinc-400">{{ $type }}</div>
                            @endif
                        </div>
                    </div>

                    <flux:dropdown position="bottom" align="end">
                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item icon="eye" :href="route('employees.index')" wire:navigate>
                                View Persons
                            </flux:menu.item>
                            <flux:menu.item icon="clock" :href="route('events.index')" wire:navigate>
                                View Events
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>

                {{-- Address + org --}}
                <div class="flex flex-col gap-1 text-sm">
                    @if ($address)
                        <span class="font-mono text-blue-600 dark:text-blue-400">{{ $address }}</span>
                    @endif
                    @if ($org)
                        <div class="flex items-center gap-1 text-zinc-500">
                            <flux:icon.building-office-2 class="size-3.5 shrink-0" />
                            <span>{{ $org }}</span>
                        </div>
                    @endif
                </div>

                {{-- Tags / capabilities + status --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    @if ($serial)
                        <span class="rounded border border-zinc-200 px-2 py-0.5 text-xs text-zinc-500 dark:border-zinc-700">
                            {{ $serial }}
                        </span>
                    @endif
                    @foreach ($caps as $cap)
                        <span class="rounded border border-zinc-200 px-2 py-0.5 text-xs text-zinc-500 dark:border-zinc-700">
                            {{ $cap }}
                        </span>
                    @endforeach

                    @if ($isOnline)
                        <span class="flex items-center gap-1 rounded border border-green-200 bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-400">
                            <flux:icon.wifi class="size-3" />
                            Online
                        </span>
                    @else
                        <span class="flex items-center gap-1 rounded border border-red-200 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600 dark:border-red-800 dark:bg-red-950 dark:text-red-400">
                            <flux:icon.x-circle class="size-3" />
                            Offline
                        </span>
                    @endif
                </div>
            </div>
        @endforeach

        {{-- Add Device placeholder (SDK doesn't support creation) --}}
        <div class="flex cursor-default flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
            <div class="flex size-10 items-center justify-center rounded-full border border-zinc-300 dark:border-zinc-600">
                <flux:icon.plus class="size-5 text-zinc-400" />
            </div>
            <div>
                <div class="font-medium text-zinc-700 dark:text-zinc-300">Add Device</div>
                <div class="text-xs text-zinc-400">Connect a new Hikvision device</div>
            </div>
        </div>
    </div>
</div>
