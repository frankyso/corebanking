<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Rekening Aktif</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($this->summary['total_accounts']) }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Outstanding</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">Rp {{ number_format($this->summary['total_outstanding'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total CKPN</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">Rp {{ number_format($this->summary['total_ckpn'], 0, ',', '.') }}</div>
            </div>
        </div>

        @php
            $nplRatio = $this->summary['total_outstanding'] > 0
                ? ($this->summary['npl_amount'] / $this->summary['total_outstanding']) * 100
                : 0;
        @endphp
        <div class="rounded-xl border-2 {{ $nplRatio <= 5 ? 'border-green-300 dark:border-green-600 bg-green-50 dark:bg-green-900/20' : 'border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/20' }} px-4 py-4">
            <div class="flex justify-between font-bold text-lg">
                <span>NPL Ratio (Kol 3-5)</span>
                <span class="{{ $nplRatio <= 5 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                    {{ number_format($nplRatio, 2) }}%
                    ({{ $this->summary['npl_count'] }} rekening)
                </span>
            </div>
        </div>

        {{-- By Collectibility --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Berdasarkan Kolektibilitas</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Kolektibilitas</th>
                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Jumlah</th>
                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Outstanding</th>
                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Tarif CKPN</th>
                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">CKPN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @php $totalOutstanding = 0; $totalCkpn = 0; $totalCount = 0; @endphp
                    @forelse ($this->portfolioByCollectibility as $row)
                        @php $totalOutstanding += $row['total_outstanding']; $totalCkpn += $row['total_ckpn']; $totalCount += $row['count']; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $row['collectibility'] }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($row['count']) }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($row['total_outstanding'], 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">{{ $row['ckpn_rate'] }}%</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($row['total_ckpn'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-800 font-semibold">
                    <tr>
                        <td class="px-4 py-3">Total</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totalCount) }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($totalOutstanding, 2, ',', '.') }}</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-right">{{ number_format($totalCkpn, 2, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- By Product --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Berdasarkan Produk</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Produk</th>
                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Jumlah</th>
                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Total Plafon</th>
                        <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Outstanding</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->portfolioByProduct as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $row->product_name }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($row->count) }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($row->total_plafon, 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($row->total_outstanding, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Tidak ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
