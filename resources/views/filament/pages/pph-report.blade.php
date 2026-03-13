<x-filament-panels::page>
    @include('filament.partials.custom-page-styles')
    <div class="space-y-6">
        {{-- Filters --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-funnel style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                    <span>Filter Laporan</span>
                </div>
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Year --}}
                <div>
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Tahun</label>
                    <select wire:model.live="year"
                        class="w-full rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 px-3 py-2 text-sm text-gray-950 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        @for ($y = 2024; $y <= 2030; $y++)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Month --}}
                <div>
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Bulan</label>
                    <select wire:model.live="month"
                        class="w-full rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 px-3 py-2 text-sm text-gray-950 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        <option value="0">Tahunan (Semua Bulan)</option>
                        <option value="1">Januari</option>
                        <option value="2">Februari</option>
                        <option value="3">Maret</option>
                        <option value="4">April</option>
                        <option value="5">Mei</option>
                        <option value="6">Juni</option>
                        <option value="7">Juli</option>
                        <option value="8">Agustus</option>
                        <option value="9">September</option>
                        <option value="10">Oktober</option>
                        <option value="11">November</option>
                        <option value="12">Desember</option>
                    </select>
                </div>

                {{-- Product Type --}}
                <div>
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Jenis Produk</label>
                    <select wire:model.live="productType"
                        class="w-full rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 px-3 py-2 text-sm text-gray-950 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        <option value="">Semua Produk</option>
                        <option value="savings">Tabungan</option>
                        <option value="deposit">Deposito</option>
                    </select>
                </div>

                {{-- Branch --}}
                <div>
                    <label class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 block">Cabang</label>
                    <select wire:model.live="branchId"
                        class="w-full rounded-lg border border-gray-300 dark:border-white/10 bg-white dark:bg-white/5 px-3 py-2 text-sm text-gray-950 dark:text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500">
                        <option value="">Semua Cabang</option>
                        @foreach ($this->branches as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Bunga Bruto --}}
            <div class="rounded-xl border border-primary-200 dark:border-primary-500/30 bg-primary-50 dark:bg-primary-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-banknotes style="width:1rem;height:1rem" class="text-primary-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-primary-600 dark:text-primary-400">Total Bunga Bruto</span>
                </div>
                <p class="text-xl font-bold text-primary-700 dark:text-primary-300 tabular-nums">{{ number_format($this->report->totalGrossInterest, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sebelum pemotongan pajak</p>
            </div>

            {{-- Total PPh --}}
            <div class="rounded-xl border border-danger-200 dark:border-danger-500/30 bg-danger-50 dark:bg-danger-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-receipt-percent style="width:1rem;height:1rem" class="text-danger-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-danger-600 dark:text-danger-400">Total PPh</span>
                </div>
                <p class="text-xl font-bold text-danger-700 dark:text-danger-300 tabular-nums">{{ number_format($this->report->totalTax, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PPh Pasal 4 ayat 2</p>
            </div>

            {{-- Total Bunga Neto --}}
            <div class="rounded-xl border border-success-200 dark:border-success-500/30 bg-success-50 dark:bg-success-400/10 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-currency-dollar style="width:1rem;height:1rem" class="text-success-500" />
                    <span class="text-xs font-medium uppercase tracking-wider text-success-600 dark:text-success-400">Total Bunga Neto</span>
                </div>
                <p class="text-xl font-bold text-success-700 dark:text-success-300 tabular-nums">{{ number_format($this->report->totalNetInterest, 2, ',', '.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Setelah pemotongan pajak</p>
            </div>

            {{-- Jumlah Nasabah --}}
            <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 p-4">
                <div class="flex items-center gap-2 mb-1">
                    <x-heroicon-o-users style="width:1rem;height:1rem" class="text-gray-500 dark:text-gray-400" />
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah Nasabah</span>
                </div>
                <p class="text-2xl font-bold text-gray-950 dark:text-white tabular-nums">{{ number_format($this->report->customerCount) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Nasabah terkena PPh</p>
            </div>
        </div>

        {{-- Detail Table --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-table-cells style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                    <span>Detail Per Nasabah</span>
                </div>
            </x-slot>
            <x-slot name="description">Rincian pemotongan PPh bunga per nasabah</x-slot>

            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">No</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Nasabah</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">NPWP</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipe</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Produk</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Bunga Bruto</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">PPh (20%)</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Bunga Neto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @php $no = 1; @endphp
                        @forelse ($this->report->customerBreakdown as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-2.5 tabular-nums text-gray-500 dark:text-gray-400">{{ $no++ }}</td>
                                <td class="px-4 py-2.5 font-medium text-gray-950 dark:text-white">{{ $row['customer_name'] }}</td>
                                <td class="px-4 py-2.5 tabular-nums text-gray-600 dark:text-gray-300">{{ $row['npwp'] ?? '-' }}</td>
                                <td class="px-4 py-2.5">
                                    <x-filament::badge :color="$row['customer_type'] === 'Perorangan' ? 'info' : 'warning'" size="sm">
                                        {{ $row['customer_type'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-2.5">
                                    <x-filament::badge :color="str_contains($row['product_type'], 'Deposito') ? 'primary' : 'success'" size="sm">
                                        {{ $row['product_type'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['gross_interest'], 2, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums text-danger-600 dark:text-danger-400">{{ number_format($row['tax_amount'], 2, ',', '.') }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums font-medium text-gray-950 dark:text-white">{{ number_format($row['net_interest'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="rounded-full bg-gray-100 dark:bg-white/5 p-3">
                                            <x-heroicon-o-document-magnifying-glass style="width:1.5rem;height:1.5rem" class="text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <span class="font-medium">Tidak ada data</span>
                                        <span class="text-xs">Belum ada data pemotongan PPh untuk periode ini</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($this->report->customerBreakdown->isNotEmpty())
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <td class="px-4 py-3" colspan="5">Total</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->report->totalGrossInterest, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-danger-600 dark:text-danger-400">{{ number_format($this->report->totalTax, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->report->totalNetInterest, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
