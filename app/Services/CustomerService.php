<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\RiskRating;
use App\Models\Customer;
use App\Models\IndividualDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(
        private SequenceService $sequenceService,
    ) {}

    public function getSequenceService(): SequenceService
    {
        return $this->sequenceService;
    }

    public function create(array $data, User $creator): Customer
    {
        return DB::transaction(function () use ($data, $creator) {
            $branchCode = $creator->branch?->code ?? '001';
            $cifNumber = $this->sequenceService->generateCifNumber($branchCode);

            $customer = Customer::create([
                'cif_number' => $cifNumber,
                'customer_type' => $data['customer_type'],
                'status' => CustomerStatus::PendingApproval,
                'risk_rating' => $data['risk_rating'] ?? RiskRating::Low,
                'branch_id' => $data['branch_id'] ?? $creator->branch_id,
                'approval_status' => ApprovalStatus::Pending,
                'created_by' => $creator->id,
            ]);

            if ($customer->customer_type === CustomerType::Individual && isset($data['individual'])) {
                $customer->individualDetail()->create($data['individual']);
            }

            if ($customer->customer_type === CustomerType::Corporate && isset($data['corporate'])) {
                $customer->corporateDetail()->create($data['corporate']);
            }

            return $customer;
        });
    }

    public function approve(Customer $customer, User $approver): bool
    {
        if (! $customer->canBeApprovedBy($approver)) {
            return false;
        }

        return DB::transaction(function () use ($customer, $approver) {
            $customer->approve($approver);
            $customer->update(['status' => CustomerStatus::Active]);

            return true;
        });
    }

    public function reject(Customer $customer, User $approver, string $reason): bool
    {
        if (! $customer->canBeApprovedBy($approver)) {
            return false;
        }

        return DB::transaction(function () use ($customer, $approver, $reason) {
            $customer->reject($approver, $reason);

            return true;
        });
    }

    public function block(Customer $customer): void
    {
        $customer->update(['status' => CustomerStatus::Blocked]);
    }

    public function unblock(Customer $customer): void
    {
        $customer->update(['status' => CustomerStatus::Active]);
    }

    public function deactivate(Customer $customer): void
    {
        $customer->update(['status' => CustomerStatus::Inactive]);
    }

    public function close(Customer $customer): void
    {
        $customer->update(['status' => CustomerStatus::Closed]);
    }

    public function checkDuplicateNik(string $nik, ?int $excludeCustomerId = null): bool
    {
        $query = IndividualDetail::where('nik', $nik);

        if ($excludeCustomerId) {
            $query->where('customer_id', '!=', $excludeCustomerId);
        }

        return $query->exists();
    }

    public function calculateRiskRating(array $data): RiskRating
    {
        $nationality = $data['nationality'] ?? 'IDN';
        $monthlyIncome = (float) ($data['monthly_income'] ?? 0);

        if ($nationality !== 'IDN' || $monthlyIncome > 500_000_000) {
            return RiskRating::High;
        }

        if ($monthlyIncome > 100_000_000) {
            return RiskRating::Medium;
        }

        return RiskRating::Low;
    }
}
