<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="w-56">
                    <label for="processDate" class="text-sm font-medium text-gray-950 dark:text-white mb-1 block">Tanggal Proses</label>
                    <input
                        type="date"
                        id="processDate"
                        wire:model.live="processDate"
                        class="w-full rounded-lg border-gray-300 bg-white shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white sm:text-sm"
                    />
                </div>
            </div>
        </x-filament::section>

        {{-- Current Process Status --}}
        @if ($this->currentProcess)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <span>EOD {{ $this->currentProcess->process_date->format('d/m/Y') }}</span>
                        <x-filament::badge :color="match($this->currentProcess->status->value) {
                            'completed' => 'success',
                            'failed' => 'danger',
                            'running' => 'warning',
                            default => 'gray',
                        }">
                            {{ $this->currentProcess->status->getLabel() }}
                        </x-filament::badge>
                    </div>
                </x-slot>

                {{-- Progress Bar --}}
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-1.5">
                        <span>Progress</span>
                        <span class="font-medium">{{ $this->currentProcess->completed_steps }}/{{ $this->currentProcess->total_steps }} langkah</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-white/10 rounded-full h-2.5 overflow-hidden">
                        <div @class([
                            'h-2.5 rounded-full transition-all duration-500',
                            'bg-success-500' => $this->currentProcess->status->value !== 'failed',
                            'bg-danger-500' => $this->currentProcess->status->value === 'failed',
                        ]) style="width: {{ $this->currentProcess->progressPercentage() }}%"></div>
                    </div>
                </div>

                {{-- Steps Table --}}
                <div class="overflow-x-auto -mx-6 -mb-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 w-12">#</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Langkah</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Record</th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Durasi</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($this->currentProcess->steps as $step)
                                <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $step->step_number }}</td>
                                    <td class="px-4 py-2 text-gray-950 dark:text-white font-medium">{{ $step->step_name }}</td>
                                    <td class="px-4 py-2">
                                        <x-filament::badge :color="match($step->status->value) {
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'running' => 'warning',
                                            default => 'gray',
                                        }" size="sm">
                                            {{ $step->status->getLabel() }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-4 py-2 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ number_format($step->records_processed) }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums text-gray-500 dark:text-gray-400">
                                        {{ $step->durationInSeconds() !== null ? $step->durationInSeconds() . 's' : '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-danger-600 dark:text-danger-400 text-xs max-w-xs truncate" title="{{ $step->error_message }}">
                                        {{ $step->error_message ?? '' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Error Message --}}
                @if ($this->currentProcess->error_message)
                    <div class="-mx-6 -mb-6 mt-4 bg-danger-50 dark:bg-danger-400/10 border-t border-danger-200 dark:border-danger-400/20 px-4 py-3">
                        <div class="flex gap-2">
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-danger-500 shrink-0 mt-0.5" />
                            <div>
                                <p class="text-sm font-medium text-danger-800 dark:text-danger-200">Error</p>
                                <p class="text-sm text-danger-700 dark:text-danger-300 mt-0.5">{{ $this->currentProcess->error_message }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </x-filament::section>
        @endif

        {{-- Recent Processes --}}
        <x-filament::section heading="Riwayat EOD" icon="heroicon-o-clock">
            <div class="overflow-x-auto -mx-6 -mb-6">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Progress</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Dijalankan</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Selesai</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->recentProcesses as $process)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer"
                                wire:click="$set('processDate', '{{ $process->process_date->toDateString() }}')">
                                <td class="px-4 py-2 font-medium text-gray-950 dark:text-white">{{ $process->process_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-2">
                                    <x-filament::badge :color="match($process->status->value) {
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        'running' => 'warning',
                                        default => 'gray',
                                    }" size="sm">
                                        {{ $process->status->getLabel() }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $process->completed_steps }}/{{ $process->total_steps }}</td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $process->started_at?->format('H:i:s') ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $process->completed_at?->format('H:i:s') ?? '-' }}</td>
                                <td class="px-4 py-2 text-gray-500 dark:text-gray-400">{{ $process->startedBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-1">
                                        <x-heroicon-o-clock class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                        <span>Belum ada riwayat EOD</span>
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
