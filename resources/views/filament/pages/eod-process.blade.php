<x-filament-panels::page>
    @include('filament.partials.custom-page-styles')
    <div class="space-y-6">
        {{-- Filter --}}
        <x-filament::section>
            <div class="flex flex-wrap gap-4 items-end">
                <div class="fi-custom-field" style="width:14rem;">
                    <label for="processDate" class="fi-custom-label">Tanggal Proses</label>
                    <input
                        type="date"
                        id="processDate"
                        wire:model.live="processDate"
                        value="{{ $processDate }}"
                        class="fi-custom-input"
                    />
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 pb-1">
                    <x-heroicon-o-information-circle style="width:0.875rem;height:0.875rem;display:inline;vertical-align:text-bottom;" class="text-gray-400" />
                    Pilih tanggal untuk melihat atau menjalankan proses EOD
                </div>
            </div>
        </x-filament::section>

        {{-- Current Process Status --}}
        @if ($this->currentProcess)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <span>EOD {{ $this->currentProcess->process_date->format('d/m/Y') }}</span>
                        </div>
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
                <x-slot name="description">
                    @if ($this->currentProcess->status->value === 'completed')
                        Semua langkah telah selesai diproses
                    @elseif ($this->currentProcess->status->value === 'failed')
                        Proses terhenti karena error, silakan cek detail dan jalankan ulang
                    @elseif ($this->currentProcess->status->value === 'running')
                        Proses sedang berjalan, harap tunggu...
                    @else
                        Proses menunggu eksekusi
                    @endif
                </x-slot>

                {{-- Progress Bar --}}
                <div class="mb-4">
                    <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-1.5">
                        <span>Progress</span>
                        <span class="font-semibold tabular-nums">
                            {{ $this->currentProcess->completed_steps }}/{{ $this->currentProcess->total_steps }} langkah
                            ({{ $this->currentProcess->total_steps > 0 ? number_format($this->currentProcess->progressPercentage(), 0) : 0 }}%)
                        </span>
                    </div>
                    <div class="progress-bar-track">
                        <div @class([
                            'progress-bar-fill',
                            'bg-success-500' => $this->currentProcess->status->value !== 'failed',
                            'bg-danger-500' => $this->currentProcess->status->value === 'failed',
                        ]) style="width: {{ $this->currentProcess->progressPercentage() }}%"></div>
                    </div>
                </div>

                {{-- Steps Timeline --}}
                <div class="step-timeline mt-4">
                    @foreach ($this->currentProcess->steps as $step)
                        <div class="step-timeline-item">
                            <div class="step-timeline-line"></div>
                            <div @class([
                                'step-timeline-dot',
                                'bg-success-100 border-success-500 dark:bg-success-500/20 dark:border-success-400' => $step->status->value === 'completed',
                                'bg-danger-100 border-danger-500 dark:bg-danger-500/20 dark:border-danger-400' => $step->status->value === 'failed',
                                'bg-warning-100 border-warning-500 dark:bg-warning-500/20 dark:border-warning-400' => $step->status->value === 'running',
                                'bg-gray-100 border-gray-300 dark:bg-white/5 dark:border-gray-700' => !in_array($step->status->value, ['completed', 'failed', 'running']),
                            ])>
                                @if ($step->status->value === 'completed')
                                    <x-heroicon-o-check style="width:0.875rem;height:0.875rem" class="text-success-600 dark:text-success-400" />
                                @elseif ($step->status->value === 'failed')
                                    <x-heroicon-o-x-mark style="width:0.875rem;height:0.875rem" class="text-danger-600 dark:text-danger-400" />
                                @elseif ($step->status->value === 'running')
                                    <div class="status-dot bg-warning-500 animate-pulse-dot" style="width:0.5rem;height:0.5rem;"></div>
                                @else
                                    <div class="status-dot bg-gray-300 dark:bg-gray-600" style="width:0.375rem;height:0.375rem;"></div>
                                @endif
                            </div>
                            <div class="step-timeline-content">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-950 dark:text-white">
                                            {{ $step->step_number }}. {{ $step->step_name }}
                                        </p>
                                        @if ($step->error_message)
                                            <p class="text-xs text-danger-600 dark:text-danger-400 mt-0.5 truncate max-w-md" title="{{ $step->error_message }}">
                                                {{ $step->error_message }}
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        @if ($step->records_processed > 0)
                                            <span class="tabular-nums" title="Jumlah record yang diproses">{{ number_format($step->records_processed) }} rec</span>
                                        @endif
                                        @if ($step->durationInSeconds() !== null)
                                            <span class="tabular-nums" title="Durasi proses">{{ $step->durationInSeconds() }}s</span>
                                        @endif
                                        <x-filament::badge :color="match($step->status->value) {
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'running' => 'warning',
                                            default => 'gray',
                                        }" size="sm">
                                            {{ $step->status->getLabel() }}
                                        </x-filament::badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Error Message --}}
                @if ($this->currentProcess->error_message)
                    <div class="-mx-6 -mb-6 mt-4 bg-danger-50 dark:bg-danger-400/10 border-t border-danger-200 dark:border-danger-400/20 px-4 py-3">
                        <div class="flex gap-2">
                            <x-heroicon-o-exclamation-triangle style="width:1.25rem;height:1.25rem" class="text-danger-500 shrink-0 mt-0.5" />
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
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-clock style="width:1.25rem;height:1.25rem" class="text-gray-400" />
                    <span>Riwayat EOD</span>
                </div>
            </x-slot>
            <x-slot name="description">Klik pada baris untuk melihat detail proses EOD</x-slot>

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
                            <tr class="table-row-highlight hover:bg-gray-50 dark:hover:bg-white/5 cursor-pointer"
                                wire:click="$set('processDate', '{{ $process->process_date->toDateString() }}')">
                                <td class="px-4 py-2.5 font-medium text-gray-950 dark:text-white">{{ $process->process_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5">
                                    <x-filament::badge :color="match($process->status->value) {
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        'running' => 'warning',
                                        default => 'gray',
                                    }" size="sm">
                                        {{ $process->status->getLabel() }}
                                    </x-filament::badge>
                                </td>
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="pct-bar-track" style="max-width:4rem;">
                                            <div class="pct-bar-fill {{ $process->status->value === 'completed' ? 'bg-success-500' : ($process->status->value === 'failed' ? 'bg-danger-500' : 'bg-warning-500') }}"
                                                style="width: {{ $process->total_steps > 0 ? ($process->completed_steps / $process->total_steps * 100) : 0 }}%"></div>
                                        </div>
                                        <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400">{{ $process->completed_steps }}/{{ $process->total_steps }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400 tabular-nums">{{ $process->started_at?->format('H:i:s') ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400 tabular-nums">{{ $process->completed_at?->format('H:i:s') ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">{{ $process->startedBy?->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="rounded-full bg-gray-100 dark:bg-white/5 p-3">
                                            <x-heroicon-o-clock style="width:1.5rem;height:1.5rem" class="text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <span class="font-medium">Belum ada riwayat EOD</span>
                                        <span class="text-xs">Jalankan proses EOD pertama menggunakan tombol di atas</span>
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
