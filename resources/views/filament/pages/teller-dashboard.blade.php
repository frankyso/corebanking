<x-filament-panels::page>
    @include('filament.partials.custom-page-styles')
    <div class="space-y-6">
        @if ($this->activeSession)
            {{-- Session Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-filament::section>
                    <div class="stat-card">
                        <div class="stat-card-icon bg-gray-100 dark:bg-white/5">
                            <x-heroicon-o-banknotes style="width:1.25rem;height:1.25rem" class="text-gray-500 dark:text-gray-400" />
                        </div>
                        <div class="stat-card-content">
                            <p class="stat-card-label">Kas Awal</p>
                            <p class="stat-card-value text-gray-950 dark:text-white">
                                Rp {{ number_format($this->activeSession->opening_balance, 0, ',', '.') }}
                            </p>
                            <p class="stat-card-meta">Saldo pembukaan sesi</p>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="stat-card">
                        <div class="stat-card-icon bg-primary-50 dark:bg-primary-400/10">
                            <x-heroicon-o-currency-dollar style="width:1.25rem;height:1.25rem" class="text-primary-500" />
                        </div>
                        <div class="stat-card-content">
                            <p class="stat-card-label">Saldo Saat Ini</p>
                            <p class="stat-card-value text-primary-600 dark:text-primary-400">
                                Rp {{ number_format($this->activeSession->current_balance, 0, ',', '.') }}
                            </p>
                            @php
                                $diff = $this->activeSession->current_balance - $this->activeSession->opening_balance;
                            @endphp
                            <p class="stat-card-meta">
                                {{ $diff >= 0 ? '+' : '' }}Rp {{ number_format($diff, 0, ',', '.') }} dari kas awal
                            </p>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="stat-card">
                        <div class="stat-card-icon bg-success-50 dark:bg-success-400/10">
                            <x-heroicon-o-arrow-down-tray style="width:1.25rem;height:1.25rem" class="text-success-500" />
                        </div>
                        <div class="stat-card-content">
                            <p class="stat-card-label">Total Kas Masuk</p>
                            <p class="stat-card-value text-success-600 dark:text-success-400">
                                Rp {{ number_format($this->activeSession->total_cash_in, 0, ',', '.') }}
                            </p>
                            <p class="stat-card-meta">Setoran & penerimaan</p>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="stat-card">
                        <div class="stat-card-icon bg-danger-50 dark:bg-danger-400/10">
                            <x-heroicon-o-arrow-up-tray style="width:1.25rem;height:1.25rem" class="text-danger-500" />
                        </div>
                        <div class="stat-card-content">
                            <p class="stat-card-label">Total Kas Keluar</p>
                            <p class="stat-card-value text-danger-600 dark:text-danger-400">
                                Rp {{ number_format($this->activeSession->total_cash_out, 0, ',', '.') }}
                            </p>
                            <p class="stat-card-meta">Penarikan & pengeluaran</p>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Session Info Bar --}}
            <div class="rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 px-5 py-3">
                <div class="flex flex-wrap gap-6 items-center text-sm">
                    <div class="flex items-center gap-2">
                        <div class="status-dot bg-success-500 animate-pulse-dot"></div>
                        <span class="text-gray-500 dark:text-gray-400">Sesi Aktif</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-hashtag style="width:0.875rem;height:0.875rem" class="text-gray-400" />
                        <span class="text-gray-500 dark:text-gray-400">Transaksi:</span>
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $this->activeSession->transaction_count }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-lock-closed style="width:0.875rem;height:0.875rem" class="text-gray-400" />
                        <span class="text-gray-500 dark:text-gray-400">Vault:</span>
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $this->activeSession->vault?->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock style="width:0.875rem;height:0.875rem" class="text-gray-400" />
                        <span class="text-gray-500 dark:text-gray-400">Dibuka:</span>
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $this->activeSession->opened_at->format('H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        <x-heroicon-o-user style="width:0.875rem;height:0.875rem" class="text-gray-400" />
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()?->name }}</span>
                    </div>
                </div>
            </div>

            {{-- Recent Transactions --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-clipboard-document-list style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                        <span>Transaksi Terakhir</span>
                        <x-filament::badge color="gray" size="sm">
                            {{ $this->recentTransactions->count() }} transaksi
                        </x-filament::badge>
                    </div>
                </x-slot>
                <x-slot name="description">10 transaksi terbaru dalam sesi ini</x-slot>

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
                                <tr class="table-row-highlight hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-2.5 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $tx->reference_number }}</td>
                                    <td class="px-4 py-2.5">
                                        <x-filament::badge :color="$tx->transaction_type->getColor()" size="sm">
                                            {{ $tx->transaction_type->getLabel() }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="font-medium text-gray-950 dark:text-white">{{ $tx->customer?->display_name ?? '-' }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-950 dark:text-white">
                                        Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if ($tx->direction === 'in')
                                            <span class="inline-flex items-center gap-1">
                                                <x-heroicon-o-arrow-down style="width:0.75rem;height:0.75rem" class="text-success-500" />
                                                <x-filament::badge color="success" size="sm">MASUK</x-filament::badge>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1">
                                                <x-heroicon-o-arrow-up style="width:0.75rem;height:0.75rem" class="text-danger-500" />
                                                <x-filament::badge color="danger" size="sm">KELUAR</x-filament::badge>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $tx->created_at->format('H:i:s') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="rounded-full bg-gray-100 dark:bg-white/5 p-3">
                                                <x-heroicon-o-inbox style="width:1.5rem;height:1.5rem" class="text-gray-400 dark:text-gray-500" />
                                            </div>
                                            <span class="font-medium">Belum ada transaksi</span>
                                            <span class="text-xs">Gunakan tombol di atas untuk memulai transaksi pertama</span>
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
                    <div class="mx-auto mb-4 rounded-2xl bg-primary-50 dark:bg-primary-400/10 p-4 w-fit">
                        <x-heroicon-o-computer-desktop style="width:2.5rem;height:2.5rem" class="text-primary-500" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-950 dark:text-white mb-1">Belum Ada Sesi Aktif</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Buka sesi teller untuk mulai memproses transaksi nasabah.
                    </p>
                    <div class="flex flex-col items-center gap-3 mt-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-md mx-auto text-left">
                            <div class="flex items-start gap-2 p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                                <div class="rounded-full bg-primary-100 dark:bg-primary-500/20 p-1 mt-0.5">
                                    <span class="text-xs font-bold text-primary-600 dark:text-primary-400" style="width:1rem;height:1rem;display:flex;align-items:center;justify-content:center;">1</span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-950 dark:text-white">Buka Sesi</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Pilih vault & kas awal</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                                <div class="rounded-full bg-success-100 dark:bg-success-500/20 p-1 mt-0.5">
                                    <span class="text-xs font-bold text-success-600 dark:text-success-400" style="width:1rem;height:1rem;display:flex;align-items:center;justify-content:center;">2</span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-950 dark:text-white">Transaksi</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Setor, tarik, bayar</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-2 p-3 rounded-lg bg-gray-50 dark:bg-white/5">
                                <div class="rounded-full bg-danger-100 dark:bg-danger-500/20 p-1 mt-0.5">
                                    <span class="text-xs font-bold text-danger-600 dark:text-danger-400" style="width:1rem;height:1rem;display:flex;align-items:center;justify-content:center;">3</span>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-950 dark:text-white">Tutup Sesi</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Kas kembali ke vault</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- Previous Sessions --}}
            @if ($this->previousSessions->isNotEmpty())
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-clock style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                            <span>Sesi Sebelumnya</span>
                        </div>
                    </x-slot>
                    <x-slot name="description">5 sesi terakhir yang telah ditutup</x-slot>

                    <div class="overflow-x-auto -mx-6 -mb-6">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-white/5">
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kas Awal</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kas Akhir</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Selisih</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Transaksi</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Ditutup</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                                @foreach ($this->previousSessions as $session)
                                    @php $sessionDiff = ($session->closing_balance ?? 0) - $session->opening_balance; @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-2.5 font-medium text-gray-950 dark:text-white">{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums text-gray-950 dark:text-white">Rp {{ number_format($session->opening_balance, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums text-gray-950 dark:text-white">Rp {{ number_format($session->closing_balance, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2.5 text-right tabular-nums {{ $sessionDiff >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                            {{ $sessionDiff >= 0 ? '+' : '' }}Rp {{ number_format($sessionDiff, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-2.5 text-right tabular-nums text-gray-950 dark:text-white">{{ $session->transaction_count }}</td>
                                        <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $session->closed_at?->format('d/m/Y H:i') }}</td>
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
