<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal</label>
                <input type="date" wire:model.live="startDate" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal</label>
                <input type="date" wire:model.live="endDate" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
        </div>

        <div class="space-y-6">
            {{-- Revenue --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-green-50 dark:bg-green-900/30 px-4 py-3">
                    <h3 class="font-semibold text-green-800 dark:text-green-200">PENDAPATAN</h3>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->incomeStatement['revenues'] ?? [] as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $item['account_code'] }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $item['account_name'] }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($item['balance'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-green-50 dark:bg-green-900/20 font-semibold">
                        <tr>
                            <td class="px-4 py-3" colspan="2">Total Pendapatan</td>
                            <td class="px-4 py-3 text-right">{{ number_format($this->incomeStatement['total_revenue'] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Expenses --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-red-50 dark:bg-red-900/30 px-4 py-3">
                    <h3 class="font-semibold text-red-800 dark:text-red-200">BEBAN</h3>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->incomeStatement['expenses'] ?? [] as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $item['account_code'] }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $item['account_name'] }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($item['balance'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-red-50 dark:bg-red-900/20 font-semibold">
                        <tr>
                            <td class="px-4 py-3" colspan="2">Total Beban</td>
                            <td class="px-4 py-3 text-right">{{ number_format($this->incomeStatement['total_expense'] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Net Income --}}
            @php
                $netIncome = $this->incomeStatement['net_income'] ?? 0;
                $isProfit = $netIncome >= 0;
            @endphp
            <div class="rounded-xl border-2 {{ $isProfit ? 'border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/20' : 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' }} px-4 py-4">
                <div class="flex justify-between font-bold text-lg">
                    <span>{{ $isProfit ? 'Laba Bersih' : 'Rugi Bersih' }}</span>
                    <span class="{{ $isProfit ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                        {{ number_format(abs($netIncome), 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
