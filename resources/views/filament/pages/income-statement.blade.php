<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="w-56">
                    <label for="startDate" class="text-sm font-medium text-gray-950 dark:text-white mb-1 block">Dari Tanggal</label>
                    <input
                        type="date"
                        id="startDate"
                        wire:model.live="startDate"
                        class="w-full rounded-lg border-gray-300 bg-white shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm"
                    />
                </div>
                <div class="w-56">
                    <label for="endDate" class="text-sm font-medium text-gray-950 dark:text-white mb-1 block">Sampai Tanggal</label>
                    <input
                        type="date"
                        id="endDate"
                        wire:model.live="endDate"
                        class="w-full rounded-lg border-gray-300 bg-white shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm"
                    />
                </div>
            </div>
        </x-filament::section>

        {{-- Revenue --}}
        <x-filament::section icon="heroicon-o-arrow-trending-up">
            <x-slot name="heading">
                <span class="text-success-600 dark:text-success-400">PENDAPATAN</span>
            </x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Akun</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->incomeStatement['revenues'] ?? [] as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['account_code'] }}</td>
                                <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $item['account_name'] }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($item['balance'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-success-50 dark:bg-success-400/10 font-semibold text-success-700 dark:text-success-300">
                            <td class="px-4 py-3" colspan="2">Total Pendapatan</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->incomeStatement['total_revenue'] ?? 0, 2, ',', '.') }}</td>
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

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Akun</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->incomeStatement['expenses'] ?? [] as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item['account_code'] }}</td>
                                <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $item['account_name'] }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($item['balance'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-danger-50 dark:bg-danger-400/10 font-semibold text-danger-700 dark:text-danger-300">
                            <td class="px-4 py-3" colspan="2">Total Beban</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->incomeStatement['total_expense'] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>

        {{-- Net Income --}}
        @php
            $netIncome = $this->incomeStatement['net_income'] ?? 0;
            $isProfit = $netIncome >= 0;
        @endphp
        <div @class([
            'rounded-xl ring-2 px-6 py-4',
            'bg-success-50 ring-success-300 dark:bg-success-400/10 dark:ring-success-400/30' => $isProfit,
            'bg-danger-50 ring-danger-300 dark:bg-danger-400/10 dark:ring-danger-400/30' => ! $isProfit,
        ])>
            <div class="flex justify-between items-center font-bold text-lg">
                <div class="flex items-center gap-2">
                    @if ($isProfit)
                        <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-success-600 dark:text-success-400" />
                    @else
                        <x-heroicon-o-arrow-trending-down class="h-6 w-6 text-danger-600 dark:text-danger-400" />
                    @endif
                    <span class="text-gray-950 dark:text-white">{{ $isProfit ? 'Laba Bersih' : 'Rugi Bersih' }}</span>
                </div>
                <span @class([
                    'tabular-nums',
                    'text-success-700 dark:text-success-300' => $isProfit,
                    'text-danger-700 dark:text-danger-300' => ! $isProfit,
                ])>
                    {{ number_format(abs($netIncome), 2, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
