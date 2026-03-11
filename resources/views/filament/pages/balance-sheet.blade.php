<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="w-56">
                    <label for="reportDate" class="text-sm font-medium text-gray-950 dark:text-white mb-1 block">Tanggal Laporan</label>
                    <input
                        type="date"
                        id="reportDate"
                        wire:model.live="reportDate"
                        class="w-full rounded-lg border-gray-300 bg-white shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm"
                    />
                </div>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Assets --}}
            <x-filament::section icon="heroicon-o-building-library" class="h-fit">
                <x-slot name="heading">
                    <span class="text-primary-600 dark:text-primary-400">ASET</span>
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
                            @forelse ($this->balanceSheet['assets'] ?? [] as $item)
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
                            <tr class="bg-primary-50 dark:bg-primary-400/10 font-semibold text-primary-700 dark:text-primary-300">
                                <td class="px-4 py-3" colspan="2">Total Aset</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->balanceSheet['total_assets'] ?? 0, 2, ',', '.') }}</td>
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
                                @forelse ($this->balanceSheet['liabilities'] ?? [] as $item)
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
                                    <td class="px-4 py-3" colspan="2">Total Kewajiban</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->balanceSheet['total_liabilities'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-filament::section>

                <x-filament::section icon="heroicon-o-banknotes">
                    <x-slot name="heading">
                        <span class="text-success-600 dark:text-success-400">EKUITAS</span>
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
                                @forelse ($this->balanceSheet['equity'] ?? [] as $item)
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
                                    <td class="px-4 py-3" colspan="2">Total Ekuitas</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->balanceSheet['total_equity'] ?? 0, 2, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </x-filament::section>

                {{-- Balance Verification --}}
                <div class="rounded-xl bg-gray-100 dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10 px-6 py-4">
                    <div class="flex justify-between items-center font-bold text-lg text-gray-950 dark:text-white">
                        <span>Total Kewajiban + Ekuitas</span>
                        <span class="tabular-nums">{{ number_format(($this->balanceSheet['total_liabilities'] ?? 0) + ($this->balanceSheet['total_equity'] ?? 0), 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
