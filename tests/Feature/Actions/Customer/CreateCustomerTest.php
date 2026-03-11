<?php

use App\Actions\Customer\CreateCustomer;
use App\DTOs\Customer\CreateCustomerData;
use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;

describe('CreateCustomer', function (): void {
    beforeEach(function (): void {
        $this->action = app(CreateCustomer::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->creator = User::factory()->create(['branch_id' => $this->branch->id]);
    });

    it('creates a customer with CIF number and PendingApproval status', function (): void {
        $customer = $this->action->execute(new CreateCustomerData(
            data: ['customer_type' => CustomerType::Individual, 'branch_id' => $this->branch->id],
            creator: $this->creator,
        ));

        expect($customer)->toBeInstanceOf(Customer::class)
            ->and($customer->cif_number)->not->toBeEmpty()
            ->and($customer->status)->toBe(CustomerStatus::PendingApproval)
            ->and($customer->approval_status)->toBe(ApprovalStatus::Pending)
            ->and($customer->created_by)->toBe($this->creator->id);
    });

    it('generates CIF number with branch code prefix', function (): void {
        $customer = $this->action->execute(new CreateCustomerData(
            data: ['customer_type' => CustomerType::Individual],
            creator: $this->creator,
        ));

        $year = date('y');
        expect($customer->cif_number)->toStartWith("001{$year}");
    });

    it('creates IndividualDetail when individual data is provided', function (): void {
        $customer = $this->action->execute(new CreateCustomerData(
            data: [
                'customer_type' => CustomerType::Individual,
                'individual' => [
                    'nik' => '3201010101010001',
                    'full_name' => 'John Doe',
                    'nationality' => 'IDN',
                    'monthly_income' => 5_000_000,
                ],
            ],
            creator: $this->creator,
        ));

        expect($customer->individualDetail)->not->toBeNull()
            ->and($customer->individualDetail->nik)->toBe('3201010101010001')
            ->and($customer->individualDetail->full_name)->toBe('John Doe');
    });

    it('creates CorporateDetail when corporate data is provided', function (): void {
        $customer = $this->action->execute(new CreateCustomerData(
            data: [
                'customer_type' => CustomerType::Corporate,
                'corporate' => [
                    'company_name' => 'PT Test Corp',
                    'legal_type' => 'PT',
                    'business_sector' => 'Perdagangan',
                ],
            ],
            creator: $this->creator,
        ));

        expect($customer->corporateDetail)->not->toBeNull()
            ->and($customer->corporateDetail->company_name)->toBe('PT Test Corp');
    });

    it('uses creator branch_id as default when branch_id not in data', function (): void {
        $customer = $this->action->execute(new CreateCustomerData(
            data: ['customer_type' => CustomerType::Individual],
            creator: $this->creator,
        ));

        expect($customer->branch_id)->toBe($this->branch->id);
    });
});
