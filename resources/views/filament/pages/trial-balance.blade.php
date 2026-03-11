<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tahun</label>
                <select wire:model.live="year" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @for ($y = now()->year; $y >= now()->year - 5; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bulan</label>
                <select wire:model.live="month" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}">{{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Kode Akun</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Nama Akun</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Debit</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Kredit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->trialBalance as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $row['account_code'] }}</td>
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $row['account_name'] }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($row['debit'], 2, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">{{ number_format($row['credit'], 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data untuk periode ini</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($this->trialBalance->isNotEmpty())
                    <tfoot class="bg-gray-100 dark:bg-gray-800 font-semibold">
                        <tr>
                            <td class="px-4 py-3" colspan="2">Total</td>
                            <td class="px-4 py-3 text-right">{{ number_format($this->trialBalance->sum('debit'), 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($this->trialBalance->sum('credit'), 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-filament-panels::page>
