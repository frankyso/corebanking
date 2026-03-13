<x-filament-panels::page>
    @include('filament.partials.custom-page-styles')
    <div class="space-y-6">
        {{-- Filters --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-funnel style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                    <span>Filter</span>
                </div>
            </x-slot>

            <div class="space-y-4">
                {{-- Row 1: Date From, Date To, Model Type, Event Type --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="fi-custom-field">
                        <label class="fi-custom-label">Dari Tanggal</label>
                        <input type="date" wire:model.live.debounce.500ms="dateFrom" class="fi-custom-input" />
                    </div>

                    <div class="fi-custom-field">
                        <label class="fi-custom-label">Sampai Tanggal</label>
                        <input type="date" wire:model.live.debounce.500ms="dateTo" class="fi-custom-input" />
                    </div>

                    <div class="fi-custom-field">
                        <label class="fi-custom-label">Tipe Entitas</label>
                        <select wire:model.live.debounce.500ms="modelType" class="fi-custom-input">
                            <option value="">Semua Entitas</option>
                            @foreach ($this->auditableTypes as $class => $label)
                                <option value="{{ $class }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fi-custom-field">
                        <label class="fi-custom-label">Tipe Event</label>
                        <select wire:model.live.debounce.500ms="eventType" class="fi-custom-input">
                            <option value="">Semua Event</option>
                            <option value="created">Dibuat</option>
                            <option value="updated">Diubah</option>
                            <option value="deleted">Dihapus</option>
                            <option value="restored">Dipulihkan</option>
                        </select>
                    </div>
                </div>

                {{-- Row 2: User, Search, Reset --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="fi-custom-field">
                        <label class="fi-custom-label">User</label>
                        <select wire:model.live.debounce.500ms="userId" class="fi-custom-input">
                            <option value="">Semua User</option>
                            @foreach ($this->users as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fi-custom-field">
                        <label class="fi-custom-label">Cari</label>
                        <input type="text" wire:model.live.debounce.500ms="search" placeholder="CIF, no. rekening, nama nasabah..." class="fi-custom-input" />
                    </div>

                    <div class="fi-custom-field flex items-end">
                        <button wire:click="resetFilters" class="fi-custom-input cursor-pointer text-center font-medium text-danger-600 dark:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-400/10" style="border-color: rgb(239 68 68 / 0.3);">
                            Reset Filter
                        </button>
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Audit --}}
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-shield-check style="width:1rem;height:1rem" class="text-gray-500 dark:text-gray-400" />
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Audit</span>
                </div>
                <p class="text-2xl font-bold text-gray-950 dark:text-white tabular-nums">{{ number_format($this->stats['total']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Dalam rentang filter</p>
            </div>

            {{-- Audit Hari Ini --}}
            <div class="rounded-xl border border-primary-200 dark:border-primary-500/30 bg-primary-50 dark:bg-primary-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-clock style="width:1rem;height:1rem" class="text-primary-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-primary-600 dark:text-primary-400">Audit Hari Ini</span>
                </div>
                <p class="text-2xl font-bold text-primary-700 dark:text-primary-300 tabular-nums">{{ number_format($this->stats['today']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ now()->translatedFormat('d F Y') }}</p>
            </div>

            {{-- Model Paling Aktif --}}
            <div class="rounded-xl border border-success-200 dark:border-success-500/30 bg-success-50 dark:bg-success-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-cube style="width:1rem;height:1rem" class="text-success-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-success-600 dark:text-success-400">Model Paling Aktif</span>
                </div>
                <p class="text-xl font-bold text-success-700 dark:text-success-300">{{ $this->stats['most_active_model'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Entitas terbanyak diubah</p>
            </div>

            {{-- User Paling Aktif --}}
            <div class="rounded-xl border border-warning-200 dark:border-warning-300 bg-warning-50 dark:bg-warning-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-user style="width:1rem;height:1rem" class="text-warning-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-warning-600 dark:text-warning-400">User Paling Aktif</span>
                </div>
                <p class="text-xl font-bold text-warning-700 dark:text-warning-300">{{ $this->stats['most_active_user'] }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Aktivitas terbanyak</p>
            </div>
        </div>

        {{-- Audit Timeline --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-clock style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                    <span>Riwayat Audit</span>
                    <x-filament::badge color="gray" size="sm">
                        {{ number_format($this->audits->total()) }} entri
                    </x-filament::badge>
                </div>
            </x-slot>
            <x-slot name="description">Klik baris untuk melihat detail perubahan</x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Waktu</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">User</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Event</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Entitas</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">ID</th>
                            <th class="px-4 py-2.5 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->audits as $audit)
                            <tr wire:click="toggleExpand({{ $audit->id }})" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-2.5 whitespace-nowrap text-gray-500 dark:text-gray-400 tabular-nums">
                                    {{ $audit->created_at->format('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <x-filament::badge color="gray" size="sm">
                                        {{ $audit->user?->name ?? 'System' }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    <x-filament::badge :color="$this->getEventColor($audit->event)" size="sm">
                                        {{ $this->getEventLabel($audit->event) }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap font-medium text-gray-950 dark:text-white">
                                    {{ $this->getModelLabel($audit->auditable_type) }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap tabular-nums text-gray-500 dark:text-gray-400">
                                    #{{ $audit->auditable_id }}
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    @if ($expandedAuditId === $audit->id)
                                        <x-heroicon-o-chevron-up style="width:1rem;height:1rem;display:inline;" class="text-primary-500" />
                                    @else
                                        <x-heroicon-o-chevron-down style="width:1rem;height:1rem;display:inline;" class="text-gray-400" />
                                    @endif
                                </td>
                            </tr>

                            {{-- Expanded Detail --}}
                            @if ($expandedAuditId === $audit->id)
                                @php
                                    $oldValues = is_array($audit->old_values) ? $audit->old_values : (json_decode($audit->old_values, true) ?? []);
                                    $newValues = is_array($audit->new_values) ? $audit->new_values : (json_decode($audit->new_values, true) ?? []);
                                    $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                                    sort($allKeys);
                                @endphp
                                <tr>
                                    <td colspan="6" class="px-4 py-3 bg-gray-50 dark:bg-white/5">
                                        @if (count($allKeys) > 0)
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-xs">
                                                    <thead>
                                                        <tr class="border-b border-gray-200 dark:border-white/10">
                                                            <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Field</th>
                                                            @if ($audit->event !== 'created')
                                                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sebelum</th>
                                                            @endif
                                                            @if ($audit->event !== 'deleted')
                                                                <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sesudah</th>
                                                            @endif
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                                        @foreach ($allKeys as $key)
                                                            @php
                                                                $oldVal = $oldValues[$key] ?? null;
                                                                $newVal = $newValues[$key] ?? null;
                                                                $changed = $audit->event === 'updated' && $oldVal !== $newVal;
                                                            @endphp
                                                            <tr>
                                                                <td class="px-3 py-1.5 font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $key }}</td>
                                                                @if ($audit->event !== 'created')
                                                                    <td class="px-3 py-1.5 tabular-nums {{ $audit->event === 'deleted' ? 'line-through text-danger-600 dark:text-danger-400' : ($changed ? 'text-danger-600 dark:text-danger-400' : 'text-gray-500 dark:text-gray-400') }}">
                                                                        {{ is_array($oldVal) ? json_encode($oldVal) : ($oldVal ?? '-') }}
                                                                    </td>
                                                                @endif
                                                                @if ($audit->event !== 'deleted')
                                                                    <td class="px-3 py-1.5 tabular-nums {{ $changed ? 'font-semibold text-success-600 dark:text-success-400' : 'text-gray-500 dark:text-gray-400' }}">
                                                                        {{ is_array($newVal) ? json_encode($newVal) : ($newVal ?? '-') }}
                                                                    </td>
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="flex flex-col items-center gap-2 py-4">
                                                <x-heroicon-o-document-magnifying-glass style="width:1.5rem;height:1.5rem" class="text-gray-400 dark:text-gray-500" />
                                                <span class="text-xs text-gray-500 dark:text-gray-400">Tidak ada data perubahan tercatat</span>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="rounded-full bg-gray-100 dark:bg-white/5 p-3">
                                            <x-heroicon-o-shield-check style="width:1.5rem;height:1.5rem" class="text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <span class="font-medium">Tidak ada audit trail</span>
                                        <span class="text-xs">Belum ada perubahan data dalam rentang waktu yang dipilih</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Pagination --}}
        @if ($this->audits->hasPages())
            <div class="px-4">
                {{ $this->audits->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
