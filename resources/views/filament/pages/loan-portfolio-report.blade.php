<x-filament-panels::page>
    @include('filament.partials.custom-page-styles')
    <div class="space-y-6">
        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-document-text style="width:1rem;height:1rem" class="text-gray-500 dark:text-gray-400" />
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Rekening Aktif</span>
                </div>
                <p class="text-2xl font-bold text-gray-950 dark:text-white tabular-nums">{{ number_format($this->summary['total_accounts']) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Status aktif & overdue</p>
            </div>

            <div class="rounded-xl border border-primary-200 dark:border-primary-500/30 bg-primary-50 dark:bg-primary-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-banknotes style="width:1rem;height:1rem" class="text-primary-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-primary-600 dark:text-primary-400">Total Outstanding</span>
                </div>
                <p class="text-xl font-bold text-primary-700 dark:text-primary-300 tabular-nums">{{ number_format($this->summary['total_outstanding'], 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 tabular-nums" title="Total plafon">Plafon: {{ number_format($this->summary['total_plafon'], 0, ',', '.') }}</p>
            </div>

            <div class="rounded-xl border border-danger-200 dark:border-danger-500/30 bg-danger-50 dark:bg-danger-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-shield-exclamation style="width:1rem;height:1rem" class="text-danger-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-danger-600 dark:text-danger-400">Total CKPN</span>
                </div>
                <p class="text-xl font-bold text-danger-700 dark:text-danger-300 tabular-nums">{{ number_format($this->summary['total_ckpn'], 0, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" title="Cadangan Kerugian Penurunan Nilai">Cadangan kerugian kredit</p>
            </div>

            {{-- NPL Ratio Card --}}
            @php
                $nplColor = match(true) {
                    $this->nplRatio <= 5 => 'success',
                    $this->nplRatio <= 8 => 'warning',
                    default => 'danger',
                };
            @endphp
            <div @class([
                'rounded-xl border p-4',
                'border-success-200 dark:border-success-500/30 bg-success-50 dark:bg-success-400/10' => $nplColor === 'success',
                'border-warning-200 dark:border-warning-300 bg-warning-50 dark:bg-warning-400/10' => $nplColor === 'warning',
                'border-danger-200 dark:border-danger-500/30 bg-danger-50 dark:bg-danger-400/10' => $nplColor === 'danger',
            ])>
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-chart-bar style="width:1rem;height:1rem" class="{{ $nplColor === 'success' ? 'text-success-500' : ($nplColor === 'warning' ? 'text-warning-500' : 'text-danger-500') }}" />
                    <span @class([
                        'text-xs font-medium uppercase tracking-wider',
                        'text-success-600 dark:text-success-400' => $nplColor === 'success',
                        'text-warning-600 dark:text-warning-400' => $nplColor === 'warning',
                        'text-danger-600 dark:text-danger-400' => $nplColor === 'danger',
                    ])>NPL Ratio</span>
                </div>
                <p @class([
                    'text-2xl font-bold tabular-nums',
                    'text-success-700 dark:text-success-300' => $nplColor === 'success',
                    'text-warning-700 dark:text-warning-300' => $nplColor === 'warning',
                    'text-danger-700 dark:text-danger-300' => $nplColor === 'danger',
                ])>{{ number_format($this->nplRatio, 2) }}%</p>
                <p class="text-xs mt-1">
                    <span class="text-gray-500 dark:text-gray-400">{{ $this->summary['npl_count'] }} rek</span>
                    &middot;
                    @if ($nplColor === 'success')
                        <span class="text-success-600 dark:text-success-400 font-medium">Sehat</span>
                    @elseif ($nplColor === 'warning')
                        <span class="text-warning-600 dark:text-warning-400 font-medium">Perlu perhatian</span>
                    @else
                        <span class="text-danger-600 dark:text-danger-400 font-medium">Kritis!</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Collectibility Composition Bar --}}
        @if ($this->portfolioByCollectibility->isNotEmpty())
            @php
                $totalOutstanding = $this->portfolioByCollectibility->sum('total_outstanding');
                $collectColors = [
                    'success' => '#22c55e',
                    'warning' => '#eab308',
                    'info' => '#f97316',
                    'danger' => '#ef4444',
                    'gray' => '#6b7280',
                ];
            @endphp
            <div class="rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-3 uppercase tracking-wider">Distribusi Portofolio Kredit</p>
                <div class="composition-bar" style="height:1.5rem;border-radius:0.5rem;">
                    @foreach ($this->portfolioByCollectibility as $row)
                        @php
                            $pct = $totalOutstanding > 0 ? ($row['total_outstanding'] / $totalOutstanding * 100) : 0;
                            $bgColor = $collectColors[$row['color']] ?? '#6b7280';
                        @endphp
                        @if ($pct >= 1)
                            <div class="composition-bar-segment" style="width: {{ $pct }}%; background-color: {{ $bgColor }};" title="{{ $row['collectibility'] }}: {{ number_format($pct, 1) }}%"></div>
                        @endif
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-4 mt-3">
                    @foreach ($this->portfolioByCollectibility as $row)
                        @php
                            $pct = $totalOutstanding > 0 ? ($row['total_outstanding'] / $totalOutstanding * 100) : 0;
                            $bgColor = $collectColors[$row['color']] ?? '#6b7280';
                        @endphp
                        <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                            <div class="status-dot" style="background-color: {{ $bgColor }};"></div>
                            <span>{{ $row['collectibility'] }}</span>
                            <span class="font-semibold tabular-nums">{{ number_format($pct, 1) }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- By Collectibility --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-chart-bar-square style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                    <span>Berdasarkan Kolektibilitas</span>
                </div>
            </x-slot>
            <x-slot name="description">Kualitas kredit per tingkat kolektibilitas</x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kolektibilitas</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Outstanding</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Distribusi</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tarif CKPN</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">CKPN</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @php $grandOutstanding = 0; $grandCkpn = 0; $grandCount = 0; @endphp
                        @forelse ($this->portfolioByCollectibility as $row)
                            @php
                                $grandOutstanding += $row['total_outstanding'];
                                $grandCkpn += $row['total_ckpn'];
                                $grandCount += $row['count'];
                                $pct = $totalOutstanding > 0 ? ($row['total_outstanding'] / $totalOutstanding * 100) : 0;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-2.5">
                                    <x-filament::badge :color="$row['color']" size="sm">
                                        {{ $row['collectibility'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['count']) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium text-gray-950 dark:text-white">{{ number_format($row['total_outstanding'], 2, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-right" style="min-width:8rem;">
                                    <div class="pct-bar">
                                        <div class="pct-bar-track">
                                            @php
                                                $barColor = match($row['color']) {
                                                    'success' => 'bg-success-500',
                                                    'warning' => 'bg-warning-500',
                                                    'danger' => 'bg-danger-500',
                                                    'info' => 'bg-info-500',
                                                    default => 'bg-gray-500',
                                                };
                                            @endphp
                                            <div class="pct-bar-fill {{ $barColor }}" style="width: {{ min($pct, 100) }}%"></div>
                                        </div>
                                        <span class="pct-bar-label">{{ number_format($pct, 1) }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ $row['ckpn_rate'] }}%</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['total_ckpn'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="rounded-full bg-gray-100 dark:bg-white/5 p-3">
                                            <x-heroicon-o-document-magnifying-glass style="width:1.5rem;height:1.5rem" class="text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <span class="font-medium">Tidak ada data</span>
                                        <span class="text-xs">Belum ada kredit aktif dalam sistem</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($this->portfolioByCollectibility->isNotEmpty())
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <td class="px-4 py-3">Total</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($grandCount) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($grandOutstanding, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-xs">100%</td>
                                <td class="px-4 py-3"></td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($grandCkpn, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::section>

        {{-- By Product --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-cube style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                    <span>Berdasarkan Produk</span>
                </div>
            </x-slot>
            <x-slot name="description">Sebaran kredit per produk pinjaman</x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Produk</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Plafon</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Outstanding</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 w-24">Utilisasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->portfolioByProduct as $row)
                            @php $util = $row->total_plafon > 0 ? ($row->total_outstanding / $row->total_plafon * 100) : 0; @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-2.5 font-medium text-gray-950 dark:text-white">{{ $row->product_name }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row->count) }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row->total_plafon, 2, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium text-gray-950 dark:text-white">{{ number_format($row->total_outstanding, 2, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-right" title="Rasio outstanding terhadap plafon">
                                    <div class="pct-bar">
                                        <div class="pct-bar-track">
                                            <div class="pct-bar-fill bg-primary-500" style="width: {{ min($util, 100) }}%"></div>
                                        </div>
                                        <span class="pct-bar-label">{{ number_format($util, 1) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="rounded-full bg-gray-100 dark:bg-white/5 p-3">
                                            <x-heroicon-o-document-magnifying-glass style="width:1.5rem;height:1.5rem" class="text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <span class="font-medium">Tidak ada data</span>
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
