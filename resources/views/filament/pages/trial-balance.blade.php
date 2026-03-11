<x-filament-panels::page>
    @include('filament.partials.custom-page-styles')
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="fi-custom-field" style="width:9rem;">
                    <label for="year" class="fi-custom-label">Tahun</label>
                    <select
                        id="year"
                        wire:model.live="year"
                        class="fi-custom-input"
                    >
                        @for ($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" @selected($y === $this->year)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="fi-custom-field" style="width:11rem;">
                    <label for="month" class="fi-custom-label">Bulan</label>
                    <select
                        id="month"
                        wire:model.live="month"
                        class="fi-custom-input"
                    >
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($m === $this->month)>{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 pb-1">
                    <x-heroicon-o-information-circle style="width:0.875rem;height:0.875rem;display:inline;vertical-align:text-bottom;" class="text-gray-400" />
                    Neraca saldo menampilkan saldo debit dan kredit untuk setiap akun
                </div>
            </div>
        </x-filament::section>

        {{-- Balance Check Banner --}}
        @if ($this->trialBalance->isNotEmpty())
            @php
                $totalDebit = $this->trialBalance->sum('debit');
                $totalCredit = $this->trialBalance->sum('credit');
                $isBalanced = abs($totalDebit - $totalCredit) < 0.01;
                $accountCount = $this->trialBalance->count();
            @endphp

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-o-document-text style="width:1rem;height:1rem" class="text-gray-400" />
                        <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah Akun</span>
                    </div>
                    <p class="text-2xl font-bold text-gray-950 dark:text-white tabular-nums">{{ $accountCount }}</p>
                </div>
                <div class="rounded-xl border border-primary-200 dark:border-primary-500/30 bg-primary-50 dark:bg-primary-400/10 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-o-arrow-trending-up style="width:1rem;height:1rem" class="text-primary-500" />
                        <span class="text-xs font-medium uppercase tracking-wider text-primary-600 dark:text-primary-400">Total Debit</span>
                    </div>
                    <p class="text-xl font-bold text-primary-700 dark:text-primary-300 tabular-nums">{{ number_format($totalDebit, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border border-danger-200 dark:border-danger-500/30 bg-danger-50 dark:bg-danger-400/10 p-4">
                    <div class="flex items-center gap-2 mb-1">
                        <x-heroicon-o-arrow-trending-down style="width:1rem;height:1rem" class="text-danger-500" />
                        <span class="text-xs font-medium uppercase tracking-wider text-danger-600 dark:text-danger-400">Total Kredit</span>
                    </div>
                    <p class="text-xl font-bold text-danger-700 dark:text-danger-300 tabular-nums">{{ number_format($totalCredit, 2, ',', '.') }}</p>
                </div>
                <div class="rounded-xl border {{ $isBalanced ? 'border-success-200 dark:border-success-500/30 bg-success-50 dark:bg-success-400/10' : 'border-danger-200 dark:border-danger-500/30 bg-danger-50 dark:bg-danger-400/10' }} p-4">
                    <div class="flex items-center gap-2 mb-1">
                        @if ($isBalanced)
                            <x-heroicon-o-check-circle style="width:1rem;height:1rem" class="text-success-500" />
                            <span class="text-xs font-medium uppercase tracking-wider text-success-600 dark:text-success-400">Seimbang</span>
                        @else
                            <x-heroicon-o-exclamation-triangle style="width:1rem;height:1rem" class="text-danger-500" />
                            <span class="text-xs font-medium uppercase tracking-wider text-danger-600 dark:text-danger-400">Selisih</span>
                        @endif
                    </div>
                    <p class="text-xl font-bold {{ $isBalanced ? 'text-success-700 dark:text-success-300' : 'text-danger-700 dark:text-danger-300' }} tabular-nums">
                        {{ $isBalanced ? 'Balance' : number_format(abs($totalDebit - $totalCredit), 2, ',', '.') }}
                    </p>
                </div>
            </div>
        @endif

        {{-- Trial Balance Table --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-scale style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                    <span>Neraca Saldo</span>
                    @if ($this->trialBalance->isNotEmpty())
                        <x-filament::badge color="gray" size="sm">{{ $this->trialBalance->count() }} akun</x-filament::badge>
                    @endif
                </div>
            </x-slot>
            <x-slot name="description">Periode {{ \Carbon\Carbon::create($this->year, $this->month)->translatedFormat('F Y') }}</x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode Akun</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Akun</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Debit</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kredit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->trialBalance as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 {{ $row['debit'] == 0 && $row['credit'] == 0 ? 'opacity-50' : '' }}">
                                <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row['account_code'] }}</td>
                                <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $row['account_name'] }}</td>
                                <td class="px-4 py-2 text-right tabular-nums {{ $row['debit'] > 0 ? 'font-medium text-gray-950 dark:text-white' : 'text-gray-300 dark:text-gray-600' }}">
                                    {{ number_format($row['debit'], 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums {{ $row['credit'] > 0 ? 'font-medium text-gray-950 dark:text-white' : 'text-gray-300 dark:text-gray-600' }}">
                                    {{ number_format($row['credit'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="rounded-full bg-gray-100 dark:bg-white/5 p-3">
                                            <x-heroicon-o-document-magnifying-glass style="width:1.5rem;height:1.5rem" class="text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <span class="font-medium">Tidak ada data untuk periode ini</span>
                                        <span class="text-xs">Coba pilih periode lain atau pastikan jurnal sudah diposting</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($this->trialBalance->isNotEmpty())
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <td class="px-4 py-3" colspan="2">Total</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->trialBalance->sum('debit'), 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->trialBalance->sum('credit'), 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
