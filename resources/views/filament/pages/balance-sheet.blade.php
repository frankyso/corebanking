<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Laporan</label>
                <input type="date" wire:model.live="reportDate" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Assets --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-blue-50 dark:bg-blue-900/30 px-4 py-3">
                    <h3 class="font-semibold text-blue-800 dark:text-blue-200">ASET</h3>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->balanceSheet['assets'] ?? [] as $item)
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
                    <tfoot class="bg-blue-50 dark:bg-blue-900/20 font-semibold">
                        <tr>
                            <td class="px-4 py-3" colspan="2">Total Aset</td>
                            <td class="px-4 py-3 text-right">{{ number_format($this->balanceSheet['total_assets'] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Liabilities & Equity --}}
            <div class="space-y-6">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-red-50 dark:bg-red-900/30 px-4 py-3">
                        <h3 class="font-semibold text-red-800 dark:text-red-200">KEWAJIBAN</h3>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($this->balanceSheet['liabilities'] ?? [] as $item)
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
                                <td class="px-4 py-3" colspan="2">Total Kewajiban</td>
                                <td class="px-4 py-3 text-right">{{ number_format($this->balanceSheet['total_liabilities'] ?? 0, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-green-50 dark:bg-green-900/30 px-4 py-3">
                        <h3 class="font-semibold text-green-800 dark:text-green-200">EKUITAS</h3>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($this->balanceSheet['equity'] ?? [] as $item)
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
                                <td class="px-4 py-3" colspan="2">Total Ekuitas</td>
                                <td class="px-4 py-3 text-right">{{ number_format($this->balanceSheet['total_equity'] ?? 0, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="rounded-xl border-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-4 py-3">
                    <div class="flex justify-between font-bold text-lg">
                        <span>Total Kewajiban + Ekuitas</span>
                        <span>{{ number_format(($this->balanceSheet['total_liabilities'] ?? 0) + ($this->balanceSheet['total_equity'] ?? 0), 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
