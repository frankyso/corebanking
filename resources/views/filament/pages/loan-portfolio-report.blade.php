<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-gray-100 p-2 dark:bg-white/5">
                        <x-heroicon-o-document-text class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Rekening Aktif</p>
                        <p class="text-2xl font-bold text-gray-950 dark:text-white tabular-nums">{{ number_format($this->summary['total_accounts']) }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-primary-50 p-2 dark:bg-primary-400/10">
                        <x-heroicon-o-banknotes class="h-5 w-5 text-primary-500" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Outstanding</p>
                        <p class="text-xl font-bold text-primary-600 dark:text-primary-400 tabular-nums">Rp {{ number_format($this->summary['total_outstanding'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-danger-50 p-2 dark:bg-danger-400/10">
                        <x-heroicon-o-shield-exclamation class="h-5 w-5 text-danger-500" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total CKPN</p>
                        <p class="text-xl font-bold text-danger-600 dark:text-danger-400 tabular-nums">Rp {{ number_format($this->summary['total_ckpn'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>

            {{-- NPL Ratio Card --}}
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div @class([
                        'rounded-lg p-2',
                        'bg-success-50 dark:bg-success-400/10' => $this->nplRatio <= 5,
                        'bg-danger-50 dark:bg-danger-400/10' => $this->nplRatio > 5,
                    ])>
                        <x-heroicon-o-chart-bar class="h-5 w-5 {{ $this->nplRatio <= 5 ? 'text-success-500' : 'text-danger-500' }}" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">NPL Ratio (Kol 3-5)</p>
                        <p @class([
                            'text-2xl font-bold tabular-nums',
                            'text-success-600 dark:text-success-400' => $this->nplRatio <= 5,
                            'text-danger-600 dark:text-danger-400' => $this->nplRatio > 5,
                        ])>{{ number_format($this->nplRatio, 2) }}%</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $this->summary['npl_count'] }} rekening</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- By Collectibility --}}
        <x-filament::section heading="Berdasarkan Kolektibilitas" icon="heroicon-o-chart-bar-square">
            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kolektibilitas</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Outstanding</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tarif CKPN</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">CKPN</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @php $totalOutstanding = 0; $totalCkpn = 0; $totalCount = 0; @endphp
                        @forelse ($this->portfolioByCollectibility as $row)
                            @php $totalOutstanding += $row['total_outstanding']; $totalCkpn += $row['total_ckpn']; $totalCount += $row['count']; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-2">
                                    <x-filament::badge :color="$row['color']" size="sm">
                                        {{ $row['collectibility'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['count']) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['total_outstanding'], 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ $row['ckpn_rate'] }}%</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['total_ckpn'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-1">
                                        <x-heroicon-o-document-magnifying-glass class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                        <span>Tidak ada data</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($this->portfolioByCollectibility->isNotEmpty())
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <td class="px-4 py-3">Total</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($totalCount) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($totalOutstanding, 2, ',', '.') }}</td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($totalCkpn, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::section>

        {{-- By Product --}}
        <x-filament::section heading="Berdasarkan Produk" icon="heroicon-o-cube">
            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Produk</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Plafon</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Outstanding</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->portfolioByProduct as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-2 font-medium text-gray-950 dark:text-white">{{ $row->product_name }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row->count) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row->total_plafon, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row->total_outstanding, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-1">
                                        <x-heroicon-o-document-magnifying-glass class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                        <span>Tidak ada data</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
