<x-filament-panels::page>
    @include('filament.partials.custom-page-styles')
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="fi-custom-field" style="width:14rem;">
                    <label for="startDate" class="fi-custom-label">Dari Tanggal</label>
                    <input
                        type="date"
                        id="startDate"
                        wire:model.live="startDate"
                        value="{{ $startDate }}"
                        class="fi-custom-input"
                    />
                </div>
                <div class="fi-custom-field" style="width:14rem;">
                    <label for="endDate" class="fi-custom-label">Sampai Tanggal</label>
                    <input
                        type="date"
                        id="endDate"
                        wire:model.live="endDate"
                        value="{{ $endDate }}"
                        class="fi-custom-input"
                    />
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 pb-1">
                    <x-heroicon-o-information-circle style="width:0.875rem;height:0.875rem;display:inline;vertical-align:text-bottom;" class="text-gray-400" />
                    Laporan laba rugi untuk periode yang dipilih
                </div>
            </div>
        </x-filament::section>

        {{-- Summary Cards --}}
        @php
            $totalRevenue = $this->incomeStatement['total_revenue'] ?? 0;
            $totalExpense = $this->incomeStatement['total_expense'] ?? 0;
            $netIncome = $this->incomeStatement['net_income'] ?? 0;
            $isProfit = $netIncome >= 0;
            $marginPct = $totalRevenue > 0 ? ($netIncome / $totalRevenue * 100) : 0;
            $costRatio = $totalRevenue > 0 ? ($totalExpense / $totalRevenue * 100) : 0;
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="rounded-xl border border-success-200 dark:border-success-500/30 bg-success-50 dark:bg-success-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-arrow-trending-up style="width:1rem;height:1rem" class="text-success-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-success-600 dark:text-success-400">Total Pendapatan</span>
                </div>
                <p class="text-xl font-bold text-success-700 dark:text-success-300 tabular-nums">{{ number_format($totalRevenue, 2, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-danger-200 dark:border-danger-500/30 bg-danger-50 dark:bg-danger-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-arrow-trending-down style="width:1rem;height:1rem" class="text-danger-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-danger-600 dark:text-danger-400">Total Beban</span>
                </div>
                <p class="text-xl font-bold text-danger-700 dark:text-danger-300 tabular-nums">{{ number_format($totalExpense, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 tabular-nums" title="Rasio beban terhadap pendapatan">{{ number_format($costRatio, 1) }}% dari pendapatan</p>
            </div>
            <div class="rounded-xl border {{ $isProfit ? 'border-primary-200 dark:border-primary-500/30 bg-primary-50 dark:bg-primary-400/10' : 'border-danger-200 dark:border-danger-500/30 bg-danger-50 dark:bg-danger-400/10' }} p-4">
                <div class="flex items-center gap-2 mb-1">
                    @if ($isProfit)
                        <x-heroicon-o-check-circle style="width:1rem;height:1rem" class="text-primary-500" />
                        <span class="text-xs font-medium uppercase tracking-wider text-primary-600 dark:text-primary-400">Laba Bersih</span>
                    @else
                        <x-heroicon-o-exclamation-triangle style="width:1rem;height:1rem" class="text-danger-500" />
                        <span class="text-xs font-medium uppercase tracking-wider text-danger-600 dark:text-danger-400">Rugi Bersih</span>
                    @endif
                </div>
                <p class="text-xl font-bold {{ $isProfit ? 'text-primary-700 dark:text-primary-300' : 'text-danger-700 dark:text-danger-300' }} tabular-nums">{{ number_format(abs($netIncome), 2, ',', '.') }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-chart-pie style="width:1rem;height:1rem" class="text-gray-500 dark:text-gray-400" />
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Profit Margin</span>
                </div>
                <p class="text-2xl font-bold {{ $isProfit ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }} tabular-nums">{{ number_format($marginPct, 1) }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" title="Rasio laba bersih terhadap pendapatan">Laba bersih / pendapatan</p>
            </div>
        </div>

        {{-- Revenue vs Expense Visual Bar --}}
        @if ($totalRevenue > 0 || $totalExpense > 0)
            <div class="rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-wider">Komposisi Pendapatan vs Beban</p>
                <div class="composition-bar" style="height:1rem;">
                    @php $revenueWidth = $totalRevenue > 0 ? ($totalRevenue / max($totalRevenue, $totalExpense) * 100) : 0; @endphp
                    <div class="composition-bar-segment bg-success-500" style="width: 100%;" title="Pendapatan: {{ number_format($totalRevenue, 0, ',', '.') }}"></div>
                </div>
                <div class="composition-bar mt-2" style="height:1rem;">
                    @php $expenseWidth = $totalRevenue > 0 ? ($totalExpense / $totalRevenue * 100) : 0; @endphp
                    <div class="composition-bar-segment bg-danger-500" style="width: {{ min($expenseWidth, 100) }}%;" title="Beban: {{ number_format($totalExpense, 0, ',', '.') }}"></div>
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-1.5">
                        <div class="status-dot bg-success-500"></div>
                        <span>Pendapatan</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="status-dot bg-danger-500"></div>
                        <span>Beban ({{ number_format($costRatio, 1) }}%)</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Revenue --}}
        <x-filament::section icon="heroicon-o-arrow-trending-up">
            <x-slot name="heading">
                <span class="text-success-600 dark:text-success-400">PENDAPATAN</span>
            </x-slot>
            <x-slot name="description">Bunga kredit, provisi, dan pendapatan lainnya</x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Akun</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 w-24">% Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->incomeStatement['revenues'] ?? [] as $item)
                            @php $pct = $totalRevenue > 0 ? ($item['balance'] / $totalRevenue * 100) : 0; @endphp
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
                            <td class="px-4 py-3" colspan="2">Total Pendapatan</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($totalRevenue, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-xs">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>

        {{-- Expenses --}}
        <x-filament::section icon="heroicon-o-arrow-trending-down">
            <x-slot name="heading">
                <span class="text-danger-600 dark:text-danger-400">BEBAN</span>
            </x-slot>
            <x-slot name="description">Bunga simpanan, gaji, operasional, dan beban lainnya</x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Akun</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 w-24">% Beban</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->incomeStatement['expenses'] ?? [] as $item)
                            @php $pct = $totalExpense > 0 ? ($item['balance'] / $totalExpense * 100) : 0; @endphp
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
                            <td class="px-4 py-3" colspan="2">Total Beban</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($totalExpense, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-xs">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>

        {{-- Net Income --}}
        <div @class([
            'rounded-xl ring-2 px-6 py-5',
            'bg-success-50 ring-success-300 dark:bg-success-400/10 dark:ring-success-400/30' => $isProfit,
            'bg-danger-50 ring-danger-300 dark:bg-danger-400/10 dark:ring-danger-400/30' => ! $isProfit,
        ])>
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-3">
                    @if ($isProfit)
                        <div class="rounded-full bg-success-100 dark:bg-success-500/20 p-2">
                            <x-heroicon-o-arrow-trending-up style="width:1.5rem;height:1.5rem" class="text-success-600 dark:text-success-400" />
                        </div>
                    @else
                        <div class="rounded-full bg-danger-100 dark:bg-danger-500/20 p-2">
                            <x-heroicon-o-arrow-trending-down style="width:1.5rem;height:1.5rem" class="text-danger-600 dark:text-danger-400" />
                        </div>
                    @endif
                    <div>
                        <p class="text-lg font-bold text-gray-950 dark:text-white">{{ $isProfit ? 'Laba Bersih' : 'Rugi Bersih' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Profit margin: {{ number_format($marginPct, 1) }}%</p>
                    </div>
                </div>
                <span @class([
                    'text-2xl font-extrabold tabular-nums',
                    'text-success-700 dark:text-success-300' => $isProfit,
                    'text-danger-700 dark:text-danger-300' => ! $isProfit,
                ])>
                    {{ number_format(abs($netIncome), 2, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
