<?php

use App\Actions\Customer\CreateCustomer;
use App\Actions\Customer\RejectCustomer;
use App\DTOs\Customer\CreateCustomerData;
use App\Enums\ApprovalStatus;
use App\Enums\CustomerType;
use App\Exceptions\Customer\CustomerApprovalException;
use App\Models\Branch;
use App\Models\User;

describe('RejectCustomer', function (): void {
    beforeEach(function (): void {
        $this->action = app(RejectCustomer::class);

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

    it('changes approval status and sets rejection reason', function (): void {
        $result = $this->action->execute($this->customer, $this->approver, 'Data tidak lengkap');

        $result->refresh();

        expect($result->approval_status)->toBe(ApprovalStatus::Rejected)
            ->and($result->rejection_reason)->toBe('Data tidak lengkap')
            ->and($result->approved_by)->toBe($this->approver->id);
    });

    it('throws when same user who created tries to reject', function (): void {
        $this->action->execute($this->customer, $this->creator, 'Alasan');
    })->throws(CustomerApprovalException::class, 'Pembuat data tidak dapat menyetujui/menolak data sendiri');
});
