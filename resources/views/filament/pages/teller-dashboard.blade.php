@include('filament.partials.custom-page-styles')
<x-filament-panels::page>
    <div class="space-y-6">
        @if ($this->activeSession)
            {{-- Session Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-filament::section>
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-gray-100 p-2 dark:bg-white/5">
                            <x-heroicon-o-banknotes style="width:1.25rem;height:1.25rem" class="text-gray-500 dark:text-gray-400" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Kas Awal</p>
                            <p class="text-lg font-bold text-gray-950 dark:text-white tabular-nums">
                                Rp {{ number_format($this->activeSession->opening_balance, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-primary-50 p-2 dark:bg-primary-400/10">
                            <x-heroicon-o-currency-dollar style="width:1.25rem;height:1.25rem" class="text-primary-500" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Saldo Saat Ini</p>
                            <p class="text-lg font-bold text-primary-600 dark:text-primary-400 tabular-nums">
                                Rp {{ number_format($this->activeSession->current_balance, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-success-50 p-2 dark:bg-success-400/10">
                            <x-heroicon-o-arrow-down-tray style="width:1.25rem;height:1.25rem" class="text-success-500" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Kas Masuk</p>
                            <p class="text-lg font-bold text-success-600 dark:text-success-400 tabular-nums">
                                Rp {{ number_format($this->activeSession->total_cash_in, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-danger-50 p-2 dark:bg-danger-400/10">
                            <x-heroicon-o-arrow-up-tray style="width:1.25rem;height:1.25rem" class="text-danger-500" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Kas Keluar</p>
                            <p class="text-lg font-bold text-danger-600 dark:text-danger-400 tabular-nums">
                                Rp {{ number_format($this->activeSession->total_cash_out, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Session Info --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-filament::section compact>
                    <div class="flex items-center gap-2 text-sm">
                        <x-heroicon-o-hashtag style="width:1rem;height:1rem" class="text-gray-400" />
                        <span class="text-gray-500 dark:text-gray-400">Transaksi:</span>
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $this->activeSession->transaction_count }}</span>
                    </div>
                </x-filament::section>

                <x-filament::section compact>
                    <div class="flex items-center gap-2 text-sm">
                        <x-heroicon-o-lock-closed style="width:1rem;height:1rem" class="text-gray-400" />
                        <span class="text-gray-500 dark:text-gray-400">Vault:</span>
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $this->activeSession->vault?->name ?? '-' }}</span>
                    </div>
                </x-filament::section>

                <x-filament::section compact>
                    <div class="flex items-center gap-2 text-sm">
                        <x-heroicon-o-clock style="width:1rem;height:1rem" class="text-gray-400" />
                        <span class="text-gray-500 dark:text-gray-400">Dibuka:</span>
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $this->activeSession->opened_at->format('H:i') }}</span>
                    </div>
                </x-filament::section>
            </div>

            {{-- Recent Transactions --}}
            <x-filament::section heading="Transaksi Terakhir" icon="heroicon-o-clipboard-document-list">
                <div class="overflow-x-auto -mx-6 -mb-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Referensi</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipe</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nasabah</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Jumlah</th>
                                <th class="px-4 py-2.5 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Arah</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @forelse ($this->recentTransactions as $tx)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $tx->reference_number }}</td>
                                    <td class="px-4 py-2">
                                        <x-filament::badge :color="$tx->transaction_type->getColor()" size="sm">
                                            {{ $tx->transaction_type->getLabel() }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $tx->customer?->display_name ?? '-' }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums font-medium text-gray-950 dark:text-white">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-center">
                                        @if ($tx->direction === 'in')
                                            <x-filament::badge color="success" size="sm">MASUK</x-filament::badge>
                                        @else
                                            <x-filament::badge color="danger" size="sm">KELUAR</x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $tx->created_at->format('H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center gap-1">
                                            <x-heroicon-o-inbox style="width:2rem;height:2rem" class="text-gray-400 dark:text-gray-500" />
                                            <span>Belum ada transaksi</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @else
            {{-- No Active Session --}}
            <x-filament::section>
                <div class="py-8 text-center">
                    <div class="mx-auto mb-4 rounded-full bg-gray-100 p-3 w-fit dark:bg-white/5">
                        <x-heroicon-o-computer-desktop style="width:2rem;height:2rem" class="text-gray-400 dark:text-gray-500" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Belum Ada Sesi Aktif</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Klik tombol "Buka Sesi" di atas untuk memulai transaksi teller.</p>
                </div>
            </x-filament::section>

            {{-- Previous Sessions --}}
            @if ($this->previousSessions->isNotEmpty())
                <x-filament::section heading="Sesi Sebelumnya" icon="heroicon-o-clock">
                    <div class="overflow-x-auto -mx-6 -mb-6">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-white/5">
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kas Awal</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kas Akhir</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Transaksi</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Ditutup</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach ($this->previousSessions as $session)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">Rp {{ number_format($session->opening_balance, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">Rp {{ number_format($session->closing_balance, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ $session->transaction_count }}</td>
                                        <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $session->closed_at?->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
