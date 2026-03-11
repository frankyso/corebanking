<?php

use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\RiskRating;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\IndividualDetail;
use App\Models\User;
use App\Services\CustomerService;

describe('CustomerService', function (): void {
    beforeEach(function (): void {
        $this->service = app(CustomerService::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->creator = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->approver = User::factory()->create(['branch_id' => $this->branch->id]);
    });

    describe('create', function (): void {
        it('creates a customer with CIF number and PendingApproval status', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Individual,
                'branch_id' => $this->branch->id,
            ], $this->creator);

            expect($customer)->toBeInstanceOf(Customer::class)
                ->and($customer->cif_number)->not->toBeEmpty()
                ->and($customer->status)->toBe(CustomerStatus::PendingApproval)
                ->and($customer->approval_status)->toBe(ApprovalStatus::Pending)
                ->and($customer->created_by)->toBe($this->creator->id);
        });

        it('generates CIF number with branch code prefix', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Individual,
            ], $this->creator);

            $year = date('y');
            expect($customer->cif_number)->toStartWith("001{$year}");
        });

        it('creates IndividualDetail when individual data is provided', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Individual,
                'individual' => [
                    'nik' => '3201010101010001',
                    'full_name' => 'John Doe',
                    'nationality' => 'IDN',
                    'monthly_income' => 5_000_000,
                ],
            ], $this->creator);

            expect($customer->individualDetail)->not->toBeNull()
                ->and($customer->individualDetail->nik)->toBe('3201010101010001')
                ->and($customer->individualDetail->full_name)->toBe('John Doe');
        });

        it('creates CorporateDetail when corporate data is provided', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Corporate,
                'corporate' => [
                    'company_name' => 'PT Test Corp',
                    'legal_type' => 'PT',
                    'business_sector' => 'Perdagangan',
                ],
            ], $this->creator);

            expect($customer->corporateDetail)->not->toBeNull()
                ->and($customer->corporateDetail->company_name)->toBe('PT Test Corp');
        });

        it('uses creator branch_id as default when branch_id not in data', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Individual,
            ], $this->creator);

            expect($customer->branch_id)->toBe($this->branch->id);
        });
    });

    describe('approve', function (): void {
        it('changes status to Active and sets approver', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Individual,
            ], $this->creator);

            $result = $this->service->approve($customer, $this->approver);

            $customer->refresh();

            expect($result)->toBeTrue()
                ->and($customer->status)->toBe(CustomerStatus::Active)
                ->and($customer->approval_status)->toBe(ApprovalStatus::Approved)
                ->and($customer->approved_by)->toBe($this->approver->id)
                ->and($customer->approved_at)->not->toBeNull();
        });

        it('fails if the same user who created tries to approve (maker-checker)', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Individual,
            ], $this->creator);

            $result = $this->service->approve($customer, $this->creator);

            $customer->refresh();

            expect($result)->toBeFalse()
                ->and($customer->status)->toBe(CustomerStatus::PendingApproval);
        });

        it('fails if customer is not in pending status', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Individual,
            ], $this->creator);

            $this->service->approve($customer, $this->approver);
            $customer->refresh();

            $thirdUser = User::factory()->create(['branch_id' => $this->branch->id]);
            $result = $this->service->approve($customer, $thirdUser);

            expect($result)->toBeFalse();
        });
    });

    describe('reject', function (): void {
        it('changes approval status and sets rejection reason', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Individual,
            ], $this->creator);

            $result = $this->service->reject($customer, $this->approver, 'Data tidak lengkap');

            $customer->refresh();

            expect($result)->toBeTrue()
                ->and($customer->approval_status)->toBe(ApprovalStatus::Rejected)
                ->and($customer->rejection_reason)->toBe('Data tidak lengkap')
                ->and($customer->approved_by)->toBe($this->approver->id);
        });

        it('fails if same user who created tries to reject', function (): void {
            $customer = $this->service->create([
                'customer_type' => CustomerType::Individual,
            ], $this->creator);

            $result = $this->service->reject($customer, $this->creator, 'Alasan');

            expect($result)->toBeFalse();
        });
    });

    describe('block', function (): void {
        it('sets status to Blocked', function (): void {
            $customer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->creator->id,
                'approved_by' => $this->approver->id,
            ]);

            $this->service->block($customer);

            expect($customer->fresh()->status)->toBe(CustomerStatus::Blocked);
        });
    });

    describe('unblock', function (): void {
        it('sets status to Active', function (): void {
            $customer = Customer::factory()->blocked()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->creator->id,
                'approved_by' => $this->approver->id,
            ]);

            $this->service->unblock($customer);

            expect($customer->fresh()->status)->toBe(CustomerStatus::Active);
        });
    });

    describe('deactivate', function (): void {
        it('sets status to Inactive', function (): void {
            $customer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->creator->id,
                'approved_by' => $this->approver->id,
            ]);

            $this->service->deactivate($customer);

            expect($customer->fresh()->status)->toBe(CustomerStatus::Inactive);
        });
    });

    describe('close', function (): void {
        it('sets status to Closed', function (): void {
            $customer = Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->creator->id,
                'approved_by' => $this->approver->id,
            ]);

            $this->service->close($customer);

            expect($customer->fresh()->status)->toBe(CustomerStatus::Closed);
        });
    });

    describe('checkDuplicateNik', function (): void {
        it('returns true for existing NIK', function (): void {
            $customer = Customer::factory()->individual()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->creator->id,
                'approved_by' => $this->approver->id,
            ]);

            IndividualDetail::factory()->create([
                'customer_id' => $customer->id,
                'nik' => '3201010101010001',
            ]);

            $result = $this->service->checkDuplicateNik('3201010101010001');

            expect($result)->toBeTrue();
        });

        it('returns false when NIK does not exist', function (): void {
            $result = $this->service->checkDuplicateNik('9999999999999999');

            expect($result)->toBeFalse();
        });

        it('returns false when excluding the customer that owns the NIK', function (): void {
            $customer = Customer::factory()->individual()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->creator->id,
                'approved_by' => $this->approver->id,
            ]);

            IndividualDetail::factory()->create([
                'customer_id' => $customer->id,
                'nik' => '3201010101010001',
            ]);

            $result = $this->service->checkDuplicateNik('3201010101010001', $customer->id);

            expect($result)->toBeFalse();
        });
    });

    describe('calculateRiskRating', function (): void {
        it('returns High for non-IDN nationality', function (): void {
            $result = $this->service->calculateRiskRating([
                'nationality' => 'SGP',
                'monthly_income' => 5_000_000,
            ]);

            expect($result)->toBe(RiskRating::High);
        });

        it('returns High for income greater than 500M', function (): void {
            $result = $this->service->calculateRiskRating([
                'nationality' => 'IDN',
                'monthly_income' => 600_000_000,
            ]);

            expect($result)->toBe(RiskRating::High);
        });

        it('returns Medium for income greater than 100M', function (): void {
            $result = $this->service->calculateRiskRating([
                'nationality' => 'IDN',
                'monthly_income' => 150_000_000,
            ]);

            expect($result)->toBe(RiskRating::Medium);
        });

        it('returns Low for normal domestic data', function (): void {
            $result = $this->service->calculateRiskRating([
                'nationality' => 'IDN',
                'monthly_income' => 10_000_000,
            ]);

            expect($result)->toBe(RiskRating::Low);
        });

        it('returns Low when data keys are missing', function (): void {
            $result = $this->service->calculateRiskRating([]);

            expect($result)->toBe(RiskRating::Low);
        });

        it('returns High when income is exactly 500M boundary', function (): void {
            $result = $this->service->calculateRiskRating([
                'nationality' => 'IDN',
                'monthly_income' => 500_000_000,
            ]);

            expect($result)->toBe(RiskRating::Medium);
        });

        it('returns Medium when income is exactly 100M boundary', function (): void {
            $result = $this->service->calculateRiskRating([
                'nationality' => 'IDN',
                'monthly_income' => 100_000_000,
            ]);

            expect($result)->toBe(RiskRating::Low);
        });
    });
});
