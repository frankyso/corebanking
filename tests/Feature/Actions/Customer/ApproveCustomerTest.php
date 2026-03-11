<?php

use App\Actions\Customer\ApproveCustomer;
use App\Actions\Customer\CreateCustomer;
use App\DTOs\Customer\CreateCustomerData;
use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Exceptions\Customer\CustomerApprovalException;
use App\Models\Branch;
use App\Models\User;

describe('ApproveCustomer', function (): void {
    beforeEach(function (): void {
        $this->action = app(ApproveCustomer::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->creator = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->approver = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer = app(CreateCustomer::class)->execute(new CreateCustomerData(
            data: ['customer_type' => CustomerType::Individual, 'branch_id' => $this->branch->id],
            creator: $this->creator,
        ));
    });

    it('changes status to Active and sets approver', function (): void {
        $result = $this->action->execute($this->customer, $this->approver);

        $result->refresh();

        expect($result->status)->toBe(CustomerStatus::Active)
            ->and($result->approval_status)->toBe(ApprovalStatus::Approved)
            ->and($result->approved_by)->toBe($this->approver->id)
            ->and($result->approved_at)->not->toBeNull();
    });

    it('throws when same user who created tries to approve (maker-checker)', function (): void {
        $this->action->execute($this->customer, $this->creator);
    })->throws(CustomerApprovalException::class, 'Pembuat data tidak dapat menyetujui/menolak data sendiri');

    it('throws when customer is not in pending status', function (): void {
        $this->action->execute($this->customer, $this->approver);
        $this->customer->refresh();

        $thirdUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->action->execute($this->customer, $thirdUser);
    })->throws(CustomerApprovalException::class, 'Nasabah tidak dalam status menunggu persetujuan');
});
