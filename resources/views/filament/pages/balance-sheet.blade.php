<x-filament-panels::page>
    @include('filament.partials.custom-page-styles')
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="fi-custom-field" style="width:14rem;">
                    <label for="reportDate" class="fi-custom-label">Tanggal Laporan</label>
                    <input
                        type="date"
                        id="reportDate"
                        wire:model.live="reportDate"
                        value="{{ $reportDate }}"
                        class="fi-custom-input"
                    />
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 pb-1">
                    <x-heroicon-o-information-circle style="width:0.875rem;height:0.875rem;display:inline;vertical-align:text-bottom;" class="text-gray-400" />
                    Laporan neraca per tanggal yang dipilih
                </div>
            </div>
        </x-filament::section>

        {{-- Balance Verification Banner --}}
        @php
            $totalAssets = $this->balanceSheet['total_assets'] ?? 0;
            $totalLiabilities = $this->balanceSheet['total_liabilities'] ?? 0;
            $totalEquity = $this->balanceSheet['total_equity'] ?? 0;
            $totalLiabEquity = $totalLiabilities + $totalEquity;
            $isBalanced = abs($totalAssets - $totalLiabEquity) < 0.01;
        @endphp
        <div class="{{ $isBalanced ? 'verification-banner-balanced' : 'verification-banner-unbalanced' }} verification-banner">
            @if ($isBalanced)
                <x-heroicon-o-check-circle style="width:1.25rem;height:1.25rem" class="text-success-600 dark:text-success-400 shrink-0" />
                <span>Neraca Seimbang &mdash; Total Aset = Kewajiban + Ekuitas</span>
            @else
                <x-heroicon-o-exclamation-triangle style="width:1.25rem;height:1.25rem" class="text-danger-600 dark:text-danger-400 shrink-0" />
                <span>Neraca Tidak Seimbang &mdash; Selisih: {{ number_format(abs($totalAssets - $totalLiabEquity), 2, ',', '.') }}</span>
            @endif
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl border border-primary-200 dark:border-primary-500/30 bg-primary-50 dark:bg-primary-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-building-library style="width:1rem;height:1rem" class="text-primary-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-primary-600 dark:text-primary-400">Total Aset</span>
                </div>
                <p class="text-xl font-bold text-primary-700 dark:text-primary-300 tabular-nums">{{ number_format($totalAssets, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-danger-200 dark:border-danger-500/30 bg-danger-50 dark:bg-danger-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-scale style="width:1rem;height:1rem" class="text-danger-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-danger-600 dark:text-danger-400">Total Kewajiban</span>
                </div>
                <p class="text-xl font-bold text-danger-700 dark:text-danger-300 tabular-nums">{{ number_format($totalLiabilities, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-success-200 dark:border-success-500/30 bg-success-50 dark:bg-success-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-banknotes style="width:1rem;height:1rem" class="text-success-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-success-600 dark:text-success-400">Total Ekuitas</span>
                </div>
                <p class="text-xl font-bold text-success-700 dark:text-success-300 tabular-nums">{{ number_format($totalEquity, 2, ',', '.') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Assets --}}
            <x-filament::section icon="heroicon-o-building-library" class="h-fit">
                <x-slot name="heading">
                    <span class="text-primary-600 dark:text-primary-400">ASET</span>
                </x-slot>
                <x-slot name="description">Kas, kredit, dan harta lainnya</x-slot>

                <div class="overflow-x-auto -mx-6 -mb-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Akun</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 w-20">%</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($this->balanceSheet['assets'] ?? [] as $item)
                                @php $pct = $totalAssets > 0 ? ($item['balance'] / $totalAssets * 100) : 0; @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['account_code'] }}</td>
                                    <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $item['account_name'] }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($item['balance'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <div class="pct-bar">
                                            <div class="pct-bar-track">
                                                <div class="pct-bar-fill bg-primary-500" style="width: {{ min($pct, 100) }}%"></div>
                                            </div>
                                            <span class="pct-bar-label">{{ number_format($pct, 1) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-primary-50 dark:bg-primary-400/10 font-semibold text-primary-700 dark:text-primary-300">
                                <td class="px-4 py-3" colspan="2">Total Aset</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($totalAssets, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-xs">100%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>

            {{-- Liabilities & Equity --}}
            <div class="space-y-6">
                <x-filament::section icon="heroicon-o-scale">
                    <x-slot name="heading">
                        <span class="text-danger-600 dark:text-danger-400">KEWAJIBAN</span>
                    </x-slot>
                    <x-slot name="description">Tabungan, deposito, dan utang lainnya</x-slot>

                    <div class="overflow-x-auto -mx-6 -mb-6">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-white/5">
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Akun</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 w-20">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @forelse ($this->balanceSheet['liabilities'] ?? [] as $item)
                                    @php $pct = $totalLiabEquity > 0 ? ($item['balance'] / $totalLiabEquity * 100) : 0; @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['account_code'] }}</td>
                                        <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $item['account_name'] }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($item['balance'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right">
                                            <div class="pct-bar">
                                                <div class="pct-bar-track">
                                                    <div class="pct-bar-fill bg-danger-500" style="width: {{ min($pct, 100) }}%"></div>
                                                </div>
                                                <span class="pct-bar-label">{{ number_format($pct, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-danger-50 dark:bg-danger-400/10 font-semibold text-danger-700 dark:text-danger-300">
                                    <td class="px-4 py-3" colspan="2">Total Kewajiban</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($totalLiabilities, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-filament::section>

                <x-filament::section icon="heroicon-o-banknotes">
                    <x-slot name="heading">
                        <span class="text-success-600 dark:text-success-400">EKUITAS</span>
                    </x-slot>
                    <x-slot name="description">Modal disetor dan laba ditahan</x-slot>

                    <div class="overflow-x-auto -mx-6 -mb-6">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-white/5">
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Akun</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 w-20">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @forelse ($this->balanceSheet['equity'] ?? [] as $item)
                                    @php $pct = $totalLiabEquity > 0 ? ($item['balance'] / $totalLiabEquity * 100) : 0; @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['account_code'] }}</td>
                                        <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $item['account_name'] }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($item['balance'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right">
                                            <div class="pct-bar">
                                                <div class="pct-bar-track">
                                                    <div class="pct-bar-fill bg-success-500" style="width: {{ min($pct, 100) }}%"></div>
                                                </div>
                                                <span class="pct-bar-label">{{ number_format($pct, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-success-50 dark:bg-success-400/10 font-semibold text-success-700 dark:text-success-300">
                                    <td class="px-4 py-3" colspan="2">Total Ekuitas</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($totalEquity, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-filament::section>

                {{-- Balance Verification --}}
                <div class="rounded-xl bg-gray-100 dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-4">
                    <div class="flex justify-between items-center font-bold text-lg text-gray-950 dark:text-white">
                        <div class="flex items-center gap-2">
                            @if ($isBalanced)
                                <x-heroicon-o-check-circle style="width:1.25rem;height:1.25rem" class="text-success-500" />
                            @else
                                <x-heroicon-o-exclamation-triangle style="width:1.25rem;height:1.25rem" class="text-danger-500" />
                            @endif
                            <span>Total Kewajiban + Ekuitas</span>
                        </div>
                        <span class="tabular-nums">{{ number_format($totalLiabEquity, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
