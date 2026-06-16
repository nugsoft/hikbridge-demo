<div class="flex flex-col gap-6">

    {{-- Back --}}
    <div>
        <flux:button variant="ghost" icon="arrow-left" :href="route('employees.index')" wire:navigate size="sm">
            Back to Employees
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

    @if ($person)
        @php $p = $person['data']; @endphp

        {{-- Employee header --}}
        <div class="flex items-center gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:avatar :name="trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''))" size="xl" />
            <div class="flex-1">
                <flux:heading size="xl">
                    {{ trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?: '—' }}
                </flux:heading>
                <div class="mt-1.5 flex items-center gap-2">
                    <span class="font-mono text-sm text-zinc-400">{{ $p['person_code'] ?? '' }}</span>
                    <flux:badge :color="($p['status'] ?? '') === 'active' ? 'green' : 'zinc'" size="sm">
                        {{ $p['status'] ?? 'unknown' }}
                    </flux:badge>
                </div>
            </div>
        </div>

        {{-- Device selector --}}
        <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:text class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Device</flux:text>
            @if (count($devices) > 0)
                <flux:select wire:model.live="selectedDeviceId" class="max-w-xs">
                    @foreach ($devices as $device)
                        <flux:select.option :value="$device['id']">
                            {{ $device['name'] ?? "Device #{$device['id']}" }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <flux:text class="text-sm text-zinc-400">No devices available</flux:text>
            @endif
            <flux:button size="sm" variant="ghost" icon="arrow-path" wire:click="loadSummary">
                Refresh
            </flux:button>
        </div>

        {{-- Three columns --}}
        <div class="grid gap-5 lg:grid-cols-3">

            {{-- ── FACE ──────────────────────────────────────────────────── --}}
            <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-950">
                            <flux:icon.face-smile class="size-4 text-violet-500" />
                        </div>
                        <flux:heading>Face</flux:heading>
                    </div>
                    @if ($summary !== null)
                        @if ($summary['has_face'] ?? false)
                            <flux:badge color="green" size="sm">Enrolled</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">Not enrolled</flux:badge>
                        @endif
                    @endif
                </div>

                {{-- Current face status --}}
                @if ($summary !== null)
                    <div class="flex items-center justify-center rounded-xl border border-dashed border-zinc-200 bg-zinc-50 py-6 dark:border-zinc-700 dark:bg-zinc-800/50">
                        @if ($summary['has_face'] ?? false)
                            <div class="flex flex-col items-center gap-2 text-green-600 dark:text-green-400">
                                <div class="flex size-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                    <flux:icon.check-circle class="size-6" />
                                </div>
                                <span class="text-sm font-medium">Face enrolled</span>
                            </div>
                        @else
                            <div class="flex flex-col items-center gap-2 text-zinc-400">
                                <div class="flex size-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon.face-smile class="size-6" />
                                </div>
                                <span class="text-sm">No face enrolled</span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Upload --}}
                <flux:field>
                    <flux:label class="text-xs">Upload image</flux:label>
                    <flux:input type="file" wire:model="faceImage" accept="image/*" />
                    <flux:error name="faceImage" />
                </flux:field>

                <div class="flex flex-col gap-2">
                    <flux:button
                        variant="primary"
                        icon="arrow-up-tray"
                        wire:click="uploadFace"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="uploadFace">Upload</span>
                        <span wire:loading wire:target="uploadFace">Uploading…</span>
                    </flux:button>
                    <flux:button variant="ghost" icon="camera" wire:click="captureFace">
                        Capture on device
                    </flux:button>
                    @if ($summary['has_face'] ?? false)
                        <flux:button
                            variant="danger"
                            icon="trash"
                            wire:click="deleteFace"
                            wire:confirm="Delete enrolled face?"
                        >
                            Delete face
                        </flux:button>
                    @endif
                </div>
            </div>

            {{-- ── FINGERPRINTS ──────────────────────────────────────────── --}}
            <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-950">
                            <flux:icon.finger-print class="size-4 text-blue-500" />
                        </div>
                        <flux:heading>Fingerprints</flux:heading>
                    </div>
                    @if ($summary !== null)
                        @php $fpCount = count($summary['fingerprints'] ?? []) ?: ($summary['fingerprint_count'] ?? 0); @endphp
                        <flux:badge :color="$fpCount > 0 ? 'blue' : 'zinc'" size="sm">
                            {{ $fpCount }} enrolled
                        </flux:badge>
                    @endif
                </div>

                {{-- Enrolled fingerprint list --}}
                @if ($summary !== null)
                    @php $enrolledFingers = $summary['fingerprints'] ?? []; @endphp
                    @if (count($enrolledFingers) > 0)
                        <div class="flex flex-col gap-1 rounded-xl border border-zinc-100 bg-zinc-50 p-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                            @foreach ($enrolledFingers as $fp)
                                @php $idx = $fp['finger_index'] ?? $fp['index'] ?? null; @endphp
                                <div class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                    <div class="flex items-center gap-2">
                                        <div class="flex size-6 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                            <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ $idx }}</span>
                                        </div>
                                        <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                            {{ $fingerLabels[$idx] ?? "Finger {$idx}" }}
                                        </span>
                                    </div>
                                    @if ($idx !== null)
                                        <button
                                            wire:click="deleteFingerprint({{ $idx }})"
                                            wire:confirm="Remove {{ $fingerLabels[$idx] ?? 'this fingerprint' }}?"
                                            class="text-zinc-400 hover:text-red-500"
                                        >
                                            <flux:icon.trash class="size-3.5" />
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif (($summary['fingerprint_count'] ?? 0) > 0)
                        {{-- API returned count but no array — show count only --}}
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
                            {{ $summary['fingerprint_count'] }} fingerprint(s) enrolled
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-zinc-200 bg-zinc-50 py-4 text-center text-sm text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800/50">
                            No fingerprints enrolled
                        </div>
                    @endif
                @endif

                {{-- Capture new --}}
                <flux:field>
                    <flux:label class="text-xs">Capture finger</flux:label>
                    <flux:select wire:model="fingerIndex">
                        @foreach ($fingerLabels as $i => $label)
                            <flux:select.option :value="$i">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:button
                    variant="primary"
                    icon="finger-print"
                    wire:click="captureFingerprint"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="captureFingerprint">Capture on device</span>
                    <span wire:loading wire:target="captureFingerprint">Capturing…</span>
                </flux:button>
            </div>

            {{-- ── ACCESS CARDS ──────────────────────────────────────────── --}}
            <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex size-8 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-950">
                            <flux:icon.credit-card class="size-4 text-amber-500" />
                        </div>
                        <flux:heading>Access Cards</flux:heading>
                    </div>
                    @if ($summary !== null)
                        @php $cardCount = count($summary['cards'] ?? []) ?: ($summary['card_count'] ?? 0); @endphp
                        <flux:badge :color="$cardCount > 0 ? 'amber' : 'zinc'" size="sm">
                            {{ $cardCount }} registered
                        </flux:badge>
                    @endif
                </div>

                {{-- Enrolled cards list --}}
                @if ($summary !== null)
                    @php $cards = $summary['cards'] ?? []; @endphp
                    @if (count($cards) > 0)
                        <div class="flex flex-col gap-1 rounded-xl border border-zinc-100 bg-zinc-50 p-2 dark:border-zinc-800 dark:bg-zinc-800/50">
                            @foreach ($cards as $card)
                                @php
                                    $cardNo   = $card['card_no'] ?? $card['number'] ?? '—';
                                    $cardType = match ($card['card_type'] ?? 1) {
                                        2       => 'Patrol',
                                        default => 'Normal',
                                    };
                                @endphp
                                <div class="flex items-center justify-between rounded-lg px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                    <div class="flex items-center gap-2">
                                        <flux:icon.credit-card class="size-4 text-amber-500" />
                                        <div>
                                            <div class="font-mono text-sm font-medium text-zinc-800 dark:text-white">{{ $cardNo }}</div>
                                            <div class="text-xs text-zinc-400">{{ $cardType }}</div>
                                        </div>
                                    </div>
                                    <button
                                        wire:click="deleteCard('{{ $cardNo }}')"
                                        wire:confirm="Remove card {{ $cardNo }}?"
                                        class="text-zinc-400 hover:text-red-500"
                                    >
                                        <flux:icon.trash class="size-3.5" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @elseif (($summary['card_count'] ?? 0) > 0)
                        <div class="rounded-xl border border-zinc-100 bg-zinc-50 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800/50">
                            {{ $summary['card_count'] }} card(s) registered
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-zinc-200 bg-zinc-50 py-4 text-center text-sm text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800/50">
                            No cards registered
                        </div>
                    @endif
                @endif

                {{-- Add card --}}
                <flux:field>
                    <flux:label class="text-xs">Card number</flux:label>
                    <flux:input wire:model="cardNo" placeholder="1234567890" />
                    <flux:error name="cardNo" />
                </flux:field>
                <flux:field>
                    <flux:label class="text-xs">Card type</flux:label>
                    <flux:select wire:model="cardType">
                        <flux:select.option value="1">Normal</flux:select.option>
                        <flux:select.option value="2">Patrol</flux:select.option>
                    </flux:select>
                </flux:field>
                <flux:button
                    variant="primary"
                    icon="credit-card"
                    wire:click="addCard"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="addCard">Add Card</span>
                    <span wire:loading wire:target="addCard">Adding…</span>
                </flux:button>
            </div>

        </div>
    @endif
</div>
