<x-filament-panels::page>
    <div class="space-y-6">
        @if ($this->activeSession)
            {{-- Session Info --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Kas Awal</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        Rp {{ number_format($this->activeSession->opening_balance, 0, ',', '.') }}
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Saldo Saat Ini</div>
                    <div class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        Rp {{ number_format($this->activeSession->current_balance, 0, ',', '.') }}
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Kas Masuk</div>
                    <div class="text-xl font-bold text-green-600 dark:text-green-400">
                        Rp {{ number_format($this->activeSession->total_cash_in, 0, ',', '.') }}
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Kas Keluar</div>
                    <div class="text-xl font-bold text-red-600 dark:text-red-400">
                        Rp {{ number_format($this->activeSession->total_cash_out, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Jumlah Transaksi</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $this->activeSession->transaction_count }}
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Vault</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $this->activeSession->vault?->name ?? '-' }}
                    </div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Dibuka Sejak</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $this->activeSession->opened_at->format('H:i') }}
                    </div>
                </div>
            </div>

            {{-- Recent Transactions --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Transaksi Terakhir</h3>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Referensi</th>
                            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Tipe</th>
                            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Nasabah</th>
                            <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Jumlah</th>
                            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Arah</th>
                            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($this->recentTransactions as $tx)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-2 font-mono text-gray-600 dark:text-gray-400 text-xs">{{ $tx->reference_number }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $tx->transaction_type->getColor() === 'success' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                        {{ $tx->transaction_type->getColor() === 'danger' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                        {{ $tx->transaction_type->getColor() === 'info' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}
                                        {{ $tx->transaction_type->getColor() === 'gray' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300' : '' }}
                                    ">{{ $tx->transaction_type->getLabel() }}</span>
                                </td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $tx->customer?->display_name ?? '-' }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-2">
                                    @if ($tx->direction === 'in')
                                        <span class="text-green-600 dark:text-green-400 font-medium">MASUK</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400 font-medium">KELUAR</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $tx->created_at->format('H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada transaksi</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            {{-- No Active Session --}}
            <div class="rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-12 text-center">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <x-heroicon-o-computer-desktop class="h-12 w-12" />
                </div>
                <h3 class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Belum Ada Sesi Aktif</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Klik tombol "Buka Sesi" untuk memulai transaksi teller.</p>
            </div>

            {{-- Previous Sessions --}}
            @php
                $previousSessions = \App\Models\TellerSession::query()
                    ->forUser(auth()->id())
                    ->where('status', 'closed')
                    ->latest('closed_at')
                    ->limit(5)
                    ->get();
            @endphp
            @if ($previousSessions->isNotEmpty())
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Sesi Sebelumnya</h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Tanggal</th>
                                <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Kas Awal</th>
                                <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Kas Akhir</th>
                                <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Transaksi</th>
                                <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Ditutup</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($previousSessions as $session)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">Rp {{ number_format($session->opening_balance, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">Rp {{ number_format($session->closing_balance, 0, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ $session->transaction_count }}</td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $session->closed_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
