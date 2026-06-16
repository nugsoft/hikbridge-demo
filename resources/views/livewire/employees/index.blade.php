<div class="flex flex-col gap-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Employees</flux:heading>
        <flux:button icon="plus" variant="primary" wire:click="$set('showCreateModal', true)">
            Add Employee
        </flux:button>
    </div>

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

    {{-- Table --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        @if (count($persons) === 0)
            <div class="p-10 text-center">
                <flux:icon.users class="mx-auto mb-3 size-12 text-zinc-400" />
                <flux:text class="text-zinc-500">No employees yet. Add your first one.</flux:text>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left dark:border-zinc-700">
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" class="rounded border-zinc-300 dark:border-zinc-600" />
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-500">Person</th>
                        <th class="px-4 py-3 font-medium text-zinc-500">Status</th>
                        <th class="px-4 py-3 font-medium text-zinc-500">Sync Status</th>
                        <th class="px-4 py-3 font-medium text-zinc-500">Devices</th>
                        <th class="px-4 py-3 font-medium text-zinc-500">Synced At</th>
                        <th class="w-12 px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($persons as $person)
                        @php
                            $fullName   = trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''));
                            $syncStatus = $person['sync_status'] ?? null;
                            $syncedAt   = $person['synced_at'] ?? $person['updated_at'] ?? null;
                            $personDevices = $person['devices'] ?? [];
                        @endphp
                        <tr class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <input type="checkbox" class="rounded border-zinc-300 dark:border-zinc-600" />
                            </td>

                            {{-- Person --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <flux:avatar :name="$fullName ?: 'Unknown'" size="sm" />
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-white">
                                            {{ $fullName ?: '—' }}
                                        </div>
                                        <div class="text-xs text-zinc-400">
                                            {{ $person['person_code'] ?? '' }}
                                            @if ($orgName)
                                                — {{ $orgName }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3">
                                <flux:badge
                                    :color="($person['status'] ?? '') === 'active' ? 'blue' : 'zinc'"
                                    size="sm"
                                >
                                    {{ $person['status'] ?? 'unknown' }}
                                </flux:badge>
                            </td>

                            {{-- Sync Status --}}
                            <td class="px-4 py-3">
                                @if ($syncStatus === 'synced' || $syncStatus === 'completed')
                                    <span class="font-medium text-zinc-700 dark:text-zinc-200">Synced</span>
                                @elseif ($syncStatus === 'failed')
                                    <span class="font-medium text-red-500">Failed</span>
                                @elseif ($syncStatus === 'pending')
                                    <span class="font-medium text-amber-500">Pending</span>
                                @elseif ($syncStatus !== null)
                                    <span class="text-zinc-400">{{ $syncStatus }}</span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>

                            {{-- Devices --}}
                            <td class="px-4 py-3">
                                @if (count($personDevices) > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($personDevices as $dev)
                                            <flux:badge color="green" size="sm">
                                                {{ strtoupper(substr($dev['name'] ?? "D{$dev['id']}", 0, 8)) }}
                                            </flux:badge>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>

                            {{-- Synced At --}}
                            <td class="px-4 py-3 text-zinc-500">
                                @if ($syncedAt)
                                    {{ \Carbon\Carbon::parse($syncedAt)->format('n/j/Y, g:i:s A') }}
                                @else
                                    —
                                @endif
                            </td>

                            {{-- Actions dropdown --}}
                            <td class="px-4 py-3 text-right">
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="ellipsis-vertical"
                                        class="opacity-0 group-hover:opacity-100"
                                    />
                                    <flux:menu>
                                        <flux:menu.item
                                            icon="pencil"
                                            wire:click="openEditModal({{ $person['id'] }})"
                                        >
                                            Edit
                                        </flux:menu.item>

                                        <flux:menu.item
                                            icon="finger-print"
                                            :href="route('employees.show', $person['id'])"
                                            wire:navigate
                                        >
                                            Biometrics
                                        </flux:menu.item>

                                        <flux:menu.item
                                            icon="arrow-path"
                                            wire:click="openSyncModal({{ $person['id'] }})"
                                        >
                                            Sync to Device
                                        </flux:menu.item>

                                        @if (($person['status'] ?? '') === 'active')
                                            <flux:menu.item
                                                icon="no-symbol"
                                                wire:click="disableAccess({{ $person['id'] }})"
                                            >
                                                Disable Access
                                            </flux:menu.item>
                                        @else
                                            <flux:menu.item
                                                icon="check-circle"
                                                wire:click="enableAccess({{ $person['id'] }})"
                                            >
                                                Enable Access
                                            </flux:menu.item>
                                        @endif

                                        <flux:menu.separator />

                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            wire:click="deletePerson({{ $person['id'] }})"
                                            wire:confirm="Delete {{ $fullName ?: 'this employee' }}? They will be removed from all devices."
                                        >
                                            Delete
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- ── Create Modal ─────────────────────────────────────────────────── --}}
    <flux:modal wire:model="showCreateModal" class="w-full max-w-md">
        <div class="p-6">
            <flux:heading size="lg">Add Employee</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Creates the person and syncs to all devices.</flux:text>
        </div>

        <div class="flex flex-col gap-4 px-6 pb-2">
            <flux:field>
                <flux:label>Employee Code</flux:label>
                <flux:input wire:model="person_code" placeholder="EMP001" />
                <flux:error name="person_code" />
            </flux:field>
            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>First Name</flux:label>
                    <flux:input wire:model="first_name" placeholder="Amina" />
                    <flux:error name="first_name" />
                </flux:field>
                <flux:field>
                    <flux:label>Last Name</flux:label>
                    <flux:input wire:model="last_name" placeholder="Nakato" />
                    <flux:error name="last_name" />
                </flux:field>
            </div>
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="status">
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="inactive">Inactive</flux:select.option>
                </flux:select>
            </flux:field>
        </div>

        <div class="flex justify-end gap-3 border-t border-zinc-200 p-6 dark:border-zinc-700">
            <flux:button variant="ghost" wire:click="$set('showCreateModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="createPerson" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="createPerson">Create Employee</span>
                <span wire:loading wire:target="createPerson">Creating…</span>
            </flux:button>
        </div>
    </flux:modal>

    {{-- ── Edit Modal ───────────────────────────────────────────────────── --}}
    <flux:modal wire:model="showEditModal" class="w-full max-w-md">
        <div class="p-6">
            <flux:heading size="lg">Edit Employee</flux:heading>
            @if ($editPersonCode)
                <flux:text class="mt-1 font-mono text-sm text-zinc-400">{{ $editPersonCode }}</flux:text>
            @endif
        </div>

        <div class="flex flex-col gap-4 px-6 pb-2">
            <div class="grid grid-cols-2 gap-3">
                <flux:field>
                    <flux:label>First Name</flux:label>
                    <flux:input wire:model="editFirstName" />
                    <flux:error name="editFirstName" />
                </flux:field>
                <flux:field>
                    <flux:label>Last Name</flux:label>
                    <flux:input wire:model="editLastName" />
                    <flux:error name="editLastName" />
                </flux:field>
            </div>
            <flux:field>
                <flux:label>Status</flux:label>
                <flux:select wire:model="editStatus">
                    <flux:select.option value="active">Active</flux:select.option>
                    <flux:select.option value="inactive">Inactive</flux:select.option>
                </flux:select>
            </flux:field>
        </div>

        <div class="flex justify-end gap-3 border-t border-zinc-200 p-6 dark:border-zinc-700">
            <flux:button variant="ghost" wire:click="$set('showEditModal', false)">Cancel</flux:button>
            <flux:button variant="primary" wire:click="updatePerson" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="updatePerson">Save Changes</span>
                <span wire:loading wire:target="updatePerson">Saving…</span>
            </flux:button>
        </div>
    </flux:modal>

    {{-- ── Sync to Device Modal ─────────────────────────────────────────── --}}
    <flux:modal wire:model="showSyncModal" class="w-full max-w-sm">
        <div class="p-6">
            <flux:heading size="lg">Sync to Device</flux:heading>
            <flux:text class="mt-1 text-zinc-500">Add this employee to a specific device.</flux:text>
        </div>

        <div class="px-6 pb-2">
            <flux:field>
                <flux:label>Device</flux:label>
                @if (count($devices) > 0)
                    <flux:select wire:model="syncDeviceId">
                        @foreach ($devices as $device)
                            <flux:select.option :value="$device['id']">
                                {{ $device['name'] ?? "Device #{$device['id']}" }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:text class="text-zinc-400">No devices available.</flux:text>
                @endif
            </flux:field>
        </div>

        <div class="flex justify-end gap-3 border-t border-zinc-200 p-6 dark:border-zinc-700">
            <flux:button variant="ghost" wire:click="$set('showSyncModal', false)">Cancel</flux:button>
            <flux:button
                variant="primary"
                wire:click="syncToDevice"
                wire:loading.attr="disabled"
                :disabled="count($devices) === 0"
            >
                <span wire:loading.remove wire:target="syncToDevice">Sync</span>
                <span wire:loading wire:target="syncToDevice">Syncing…</span>
            </flux:button>
        </div>
    </flux:modal>
</div>
