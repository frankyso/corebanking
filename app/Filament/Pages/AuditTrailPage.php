<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\LoanAccount;
use App\Models\SavingsAccount;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use OwenIt\Auditing\Models\Audit;
use UnitEnum;

class AuditTrailPage extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 61;

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?string $title = 'Audit Trail';

    protected ?string $subheading = 'Riwayat perubahan data seluruh entitas';

    protected string $view = 'filament.pages.audit-trail';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $modelType = '';

    public string $eventType = '';

    public ?int $userId = null;

    public string $search = '';

    public ?int $expandedAuditId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('audit.view') ?? false;
    }

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    /**
     * @return LengthAwarePaginator<int, Audit>
     */
    #[Computed]
    public function audits(): LengthAwarePaginator
    {
        $query = Audit::query() // @phpstan-ignore larastan.relationExistence
            ->with('user')
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo);

        if ($this->modelType !== '') {
            $query->where('auditable_type', $this->modelType);
        }

        if ($this->eventType !== '') {
            $query->where('event', $this->eventType);
        }

        if ($this->userId !== null) {
            $query->where('user_id', $this->userId);
        }

        if ($this->search !== '') {
            $searchTerm = '%'.$this->search.'%';
            /** @var Collection<int, array{type: class-string, id: int}> $matchingPairs */
            $matchingPairs = collect();

            /** @var Collection<int, int> $customerIds */
            $customerIds = Customer::query()
                ->where('cif_number', 'like', $searchTerm)
                ->orWhereHas('individualDetail', fn ($q) => $q->where('full_name', 'like', $searchTerm))
                ->orWhereHas('corporateDetail', fn ($q) => $q->where('company_name', 'like', $searchTerm))
                ->pluck('id');

            foreach ($customerIds as $id) {
                $matchingPairs->push(['type' => Customer::class, 'id' => $id]);
            }

            /** @var Collection<int, int> $savingsIds */
            $savingsIds = SavingsAccount::query()
                ->where('account_number', 'like', $searchTerm)
                ->pluck('id');

            foreach ($savingsIds as $id) {
                $matchingPairs->push(['type' => SavingsAccount::class, 'id' => $id]);
            }

            /** @var Collection<int, int> $depositIds */
            $depositIds = DepositAccount::query()
                ->where('account_number', 'like', $searchTerm)
                ->pluck('id');

            foreach ($depositIds as $id) {
                $matchingPairs->push(['type' => DepositAccount::class, 'id' => $id]);
            }

            /** @var Collection<int, int> $loanIds */
            $loanIds = LoanAccount::query()
                ->where('account_number', 'like', $searchTerm)
                ->pluck('id');

            foreach ($loanIds as $id) {
                $matchingPairs->push(['type' => LoanAccount::class, 'id' => $id]);
            }

            if ($matchingPairs->isNotEmpty()) {
                $query->where(function ($q) use ($matchingPairs): void {
                    foreach ($matchingPairs as $pair) {
                        $q->orWhere(function ($sub) use ($pair): void {
                            $sub->where('auditable_type', $pair['type'])
                                ->where('auditable_id', $pair['id']);
                        });
                    }
                });
            } else {
                // No matching entities found, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        return $query->orderByDesc('created_at')->paginate(25);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function auditableTypes(): array
    {
        return [
            'App\Models\Customer' => 'Nasabah',
            'App\Models\SavingsAccount' => 'Tabungan',
            'App\Models\SavingsProduct' => 'Produk Tabungan',
            'App\Models\SavingsTransaction' => 'Transaksi Tabungan',
            'App\Models\DepositAccount' => 'Deposito',
            'App\Models\DepositProduct' => 'Produk Deposito',
            'App\Models\LoanAccount' => 'Pinjaman',
            'App\Models\LoanProduct' => 'Produk Pinjaman',
            'App\Models\LoanApplication' => 'Pengajuan Kredit',
            'App\Models\JournalEntry' => 'Jurnal',
            'App\Models\ChartOfAccount' => 'Akun GL',
            'App\Models\User' => 'Pengguna',
            'App\Models\Branch' => 'Cabang',
            'App\Models\SystemParameter' => 'Parameter Sistem',
            'App\Models\Vault' => 'Brankas',
        ];
    }

    /**
     * @return Collection<int|string, mixed>
     */
    #[Computed]
    public function users(): Collection
    {
        return User::orderBy('name')->pluck('name', 'id');
    }

    /**
     * @return array{total: int, today: int, most_active_model: string, most_active_user: string}
     */
    #[Computed]
    public function stats(): array
    {
        $baseQuery = Audit::query()
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo);

        if ($this->modelType !== '') {
            $baseQuery->where('auditable_type', $this->modelType);
        }

        if ($this->eventType !== '') {
            $baseQuery->where('event', $this->eventType);
        }

        if ($this->userId !== null) {
            $baseQuery->where('user_id', $this->userId);
        }

        $total = $baseQuery->count();

        $today = Audit::query()
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->count();

        $mostActiveModel = Audit::query()
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo)
            ->select('auditable_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('auditable_type')
            ->orderByDesc('cnt')
            ->first();

        $mostActiveUser = Audit::query()
            ->whereDate('created_at', '>=', $this->dateFrom)
            ->whereDate('created_at', '<=', $this->dateTo)
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->first();

        $activeModelLabel = '-';
        if ($mostActiveModel !== null) {
            /** @var string $auditableType */
            $auditableType = $mostActiveModel->auditable_type; // @phpstan-ignore property.notFound
            $activeModelLabel = $this->getModelLabel($auditableType);
        }

        $activeUserLabel = '-';
        if ($mostActiveUser !== null) {
            /** @var int $activeUserId */
            $activeUserId = $mostActiveUser->user_id; // @phpstan-ignore property.notFound
            $activeUserLabel = User::find($activeUserId)?->name ?? '-'; // @phpstan-ignore nullsafe.neverNull
        }

        return [
            'total' => $total,
            'today' => $today,
            'most_active_model' => $activeModelLabel,
            'most_active_user' => $activeUserLabel,
        ];
    }

    public function toggleExpand(int $auditId): void
    {
        $this->expandedAuditId = $this->expandedAuditId === $auditId ? null : $auditId;
    }

    public function resetFilters(): void
    {
        $this->dateFrom = now()->subDays(7)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->modelType = '';
        $this->eventType = '';
        $this->userId = null;
        $this->search = '';
        $this->expandedAuditId = null;

        unset($this->audits, $this->stats);
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        unset($this->audits, $this->stats);
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        unset($this->audits, $this->stats);
        $this->resetPage();
    }

    public function updatedModelType(): void
    {
        unset($this->audits, $this->stats);
        $this->resetPage();
    }

    public function updatedEventType(): void
    {
        unset($this->audits, $this->stats);
        $this->resetPage();
    }

    public function updatedUserId(): void
    {
        unset($this->audits, $this->stats);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        unset($this->audits, $this->stats);
        $this->resetPage();
    }

    public function getModelLabel(string $morphClass): string
    {
        $types = $this->auditableTypes();

        return $types[$morphClass] ?? class_basename($morphClass);
    }

    public function getEventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Dibuat',
            'updated' => 'Diubah',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            default => ucfirst($event),
        };
    }

    public function getEventColor(string $event): string
    {
        return match ($event) {
            'created' => 'success',
            'updated' => 'primary',
            'deleted' => 'danger',
            'restored' => 'info',
            default => 'gray',
        };
    }
}
