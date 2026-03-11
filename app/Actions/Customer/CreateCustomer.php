<?php

namespace App\Actions\Customer;

use App\DTOs\Customer\CreateCustomerData;
use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\RiskRating;
use App\Models\Customer;
use App\Services\SequenceService;
use Illuminate\Support\Facades\DB;

class CreateCustomer
{
    public function __construct(
        private SequenceService $sequenceService,
    ) {}

    public function execute(CreateCustomerData $dto): Customer
    {
        return DB::transaction(function () use ($dto): Customer {
            $branchCode = $dto->creator->branch?->code ?? '001';
            $cifNumber = $this->sequenceService->generateCifNumber($branchCode);

            $customer = Customer::create([
                'cif_number' => $cifNumber,
                'customer_type' => $dto->data['customer_type'],
                'status' => CustomerStatus::PendingApproval,
                'risk_rating' => $dto->data['risk_rating'] ?? RiskRating::Low,
                'branch_id' => $dto->data['branch_id'] ?? $dto->creator->branch_id,
                'approval_status' => ApprovalStatus::Pending,
                'created_by' => $dto->creator->id,
            ]);

            if ($customer->customer_type === CustomerType::Individual && isset($dto->data['individual'])) {
                $customer->individualDetail()->create($dto->data['individual']);
            }

            if ($customer->customer_type === CustomerType::Corporate && isset($dto->data['corporate'])) {
                $customer->corporateDetail()->create($dto->data['corporate']);
            }

            return $customer;
        });
    }
}
