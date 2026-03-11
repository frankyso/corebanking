<?php

namespace App\Filament\Pages;

use App\Enums\EodStatus;
use App\Models\EodProcess;
use App\Services\EodService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use UnitEnum;

class EodProcessPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 40;

    protected static ?string $navigationLabel = 'End of Day';

    protected static ?string $title = 'End of Day Process';

    protected string $view = 'filament.pages.eod-process';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('eod.execute') ?? false;
    }

    public string $processDate = '';

    public function mount(): void
    {
        $this->processDate = now()->toDateString();
    }

    #[Computed]
    public function currentProcess(): ?EodProcess
    {
        return EodProcess::with('steps')
            ->where('process_date', $this->processDate)
            ->latest()
            ->first();
    }

    #[Computed]
    public function recentProcesses(): Collection
    {
        return EodProcess::with('startedBy')
            ->latest('process_date')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function stepNames(): array
    {
        return app(EodService::class)->getStepNames();
    }

    public function updatedProcessDate(): void
    {
        unset($this->currentProcess, $this->recentProcesses);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runEod')
                ->label('Jalankan EOD')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Jalankan End of Day')
                ->modalDescription(fn () => "Jalankan proses EOD untuk tanggal {$this->processDate}?")
                ->visible(fn () => ! $this->currentProcess || $this->currentProcess->status === EodStatus::Failed)
                ->action(function () {
                    try {
                        $process = app(EodService::class)->run(
                            processDate: Carbon::parse($this->processDate),
                            performer: auth()->user(),
                        );

                        if ($process->status === EodStatus::Completed) {
                            Notification::make()
                                ->title('EOD berhasil diselesaikan')
                                ->body("Semua {$process->total_steps} langkah selesai.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('EOD gagal')
                                ->body($process->error_message)
                                ->danger()
                                ->send();
                        }

                        unset($this->currentProcess, $this->recentProcesses);
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title('Gagal')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
