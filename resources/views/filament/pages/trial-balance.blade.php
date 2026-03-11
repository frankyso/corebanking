<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="w-36">
                    <label for="year" class="text-sm font-medium text-gray-950 dark:text-white mb-1 block">Tahun</label>
                    <select
                        id="year"
                        wire:model.live="year"
                        class="w-full rounded-lg border-gray-300 bg-white shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm"
                    >
                        @for ($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="w-44">
                    <label for="month" class="text-sm font-medium text-gray-950 dark:text-white mb-1 block">Bulan</label>
                    <select
                        id="month"
                        wire:model.live="month"
                        class="w-full rounded-lg border-gray-300 bg-white shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm"
                    >
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </x-filament::section>

        {{-- Trial Balance Table --}}
        <x-filament::section heading="Neraca Saldo" icon="heroicon-o-scale">
            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kode Akun</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama Akun</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Debit</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Kredit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->trialBalance as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $row['account_code'] }}</td>
                                <td class="px-4 py-2 text-gray-950 dark:text-white">{{ $row['account_name'] }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['debit'], 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['credit'], 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-1">
                                        <x-heroicon-o-document-magnifying-glass class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                        <span>Tidak ada data untuk periode ini</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($this->trialBalance->isNotEmpty())
                        <tfoot>
                            <tr class="bg-gray-100 dark:bg-white/5 font-semibold text-gray-950 dark:text-white">
                                <td class="px-4 py-3" colspan="2">Total</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->trialBalance->sum('debit'), 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($this->trialBalance->sum('credit'), 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
