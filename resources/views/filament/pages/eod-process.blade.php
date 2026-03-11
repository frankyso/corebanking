<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Date Picker --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Proses</label>
            <input type="date" wire:model.live="processDate" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>

        {{-- Current Process Status --}}
        @if ($this->currentProcess)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-4 py-3 flex justify-between items-center
                    {{ $this->currentProcess->status->value === 'completed' ? 'bg-green-50 dark:bg-green-900/30' : '' }}
                    {{ $this->currentProcess->status->value === 'failed' ? 'bg-red-50 dark:bg-red-900/30' : '' }}
                    {{ $this->currentProcess->status->value === 'running' ? 'bg-yellow-50 dark:bg-yellow-900/30' : '' }}
                    {{ $this->currentProcess->status->value === 'pending' ? 'bg-gray-50 dark:bg-gray-800' : '' }}
                ">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        EOD {{ $this->currentProcess->process_date->format('d/m/Y') }}
                    </h3>
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        {{ $this->currentProcess->status->value === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' : '' }}
                        {{ $this->currentProcess->status->value === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200' : '' }}
                        {{ $this->currentProcess->status->value === 'running' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200' : '' }}
                        {{ $this->currentProcess->status->value === 'pending' ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' : '' }}
                    ">
                        {{ $this->currentProcess->status->getLabel() }}
                    </span>
                </div>

                {{-- Progress Bar --}}
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                        <span>Progress</span>
                        <span>{{ $this->currentProcess->completed_steps }}/{{ $this->currentProcess->total_steps }} langkah</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full {{ $this->currentProcess->status->value === 'failed' ? 'bg-red-600' : 'bg-green-600' }}"
                            style="width: {{ $this->currentProcess->progressPercentage() }}%">
                        </div>
                    </div>
                </div>

                {{-- Steps --}}
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400 w-12">#</th>
                            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Langkah</th>
                            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Status</th>
                            <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Record</th>
                            <th class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">Durasi</th>
                            <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($this->currentProcess->steps as $step)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $step->step_number }}</td>
                                <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $step->step_name }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $step->status->value === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                        {{ $step->status->value === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                        {{ $step->status->value === 'running' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}
                                        {{ $step->status->value === 'pending' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300' : '' }}
                                    ">{{ $step->status->getLabel() }}</span>
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">{{ $step->records_processed }}</td>
                                <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">
                                    @if ($step->durationInSeconds() !== null)
                                        {{ $step->durationInSeconds() }}s
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-red-600 dark:text-red-400 text-xs">
                                    {{ $step->error_message ? Str::limit($step->error_message, 50) : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if ($this->currentProcess->error_message)
                    <div class="px-4 py-3 bg-red-50 dark:bg-red-900/20 border-t border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-700 dark:text-red-300">
                            <strong>Error:</strong> {{ $this->currentProcess->error_message }}
                        </p>
                    </div>
                @endif
            </div>
        @endif

        {{-- Recent Processes --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Riwayat EOD</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Tanggal</th>
                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Status</th>
                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Progress</th>
                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Dijalankan</th>
                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Selesai</th>
                        <th class="px-4 py-2 text-left text-gray-600 dark:text-gray-400">Oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->recentProcesses as $process)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer"
                            wire:click="$set('processDate', '{{ $process->process_date->toDateString() }}')">
                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">{{ $process->process_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $process->status->value === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                    {{ $process->status->value === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                    {{ $process->status->value === 'running' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}
                                    {{ $process->status->value === 'pending' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300' : '' }}
                                ">{{ $process->status->getLabel() }}</span>
                            </td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $process->completed_steps }}/{{ $process->total_steps }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $process->started_at?->format('H:i:s') ?? '-' }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $process->completed_at?->format('H:i:s') ?? '-' }}</td>
                            <td class="px-4 py-2 text-gray-600 dark:text-gray-400">{{ $process->startedBy?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">Belum ada riwayat EOD</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
