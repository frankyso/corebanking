<?php

use App\Enums\ApprovalStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->branch = Branch::factory()->create();

    $this->creator = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    $this->approver = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);
});

it('approves a pending customer and changes status to Approved', function () {
    $customer = Customer::factory()->pendingApproval()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
    ]);

    $result = $customer->approve($this->approver);

    expect($result)->toBeTrue();
    expect($customer->fresh()->approval_status)->toBe(ApprovalStatus::Approved);
    expect($customer->fresh()->approved_by)->toBe($this->approver->id);
    expect($customer->fresh()->approved_at)->not->toBeNull();
});

it('fails to approve when same user is creator (maker-checker)', function () {
    $customer = Customer::factory()->pendingApproval()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
    ]);

    $result = $customer->approve($this->creator);

    expect($result)->toBeFalse();
    expect($customer->fresh()->approval_status)->toBe(ApprovalStatus::Pending);
});

it('fails to approve when status is not Pending', function () {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
        'approval_status' => ApprovalStatus::Approved,
        'approved_by' => $this->approver->id,
        'approved_at' => now(),
    ]);

    $newApprover = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    $result = $customer->approve($newApprover);

    expect($result)->toBeFalse();
});

it('rejects a pending customer with a reason', function () {
    $customer = Customer::factory()->pendingApproval()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
    ]);

    $reason = 'Incomplete documentation provided.';
    $result = $customer->reject($this->approver, $reason);

    expect($result)->toBeTrue();
    expect($customer->fresh()->approval_status)->toBe(ApprovalStatus::Rejected);
    expect($customer->fresh()->rejection_reason)->toBe($reason);
    expect($customer->fresh()->approved_by)->toBe($this->approver->id);
    expect($customer->fresh()->approved_at)->not->toBeNull();
});

it('fails to reject when same user is creator', function () {
    $customer = Customer::factory()->pendingApproval()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
    ]);

    $result = $customer->reject($this->creator, 'Some reason');

    expect($result)->toBeFalse();
    expect($customer->fresh()->approval_status)->toBe(ApprovalStatus::Pending);
});

it('canBeApprovedBy returns true for different user with Pending status', function () {
    $customer = Customer::factory()->pendingApproval()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
    ]);

    expect($customer->canBeApprovedBy($this->approver))->toBeTrue();
});

it('canBeApprovedBy returns false for same user as creator', function () {
    $customer = Customer::factory()->pendingApproval()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
    ]);

    expect($customer->canBeApprovedBy($this->creator))->toBeFalse();
});

it('canBeApprovedBy returns false for non-Pending status', function () {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
        'approval_status' => ApprovalStatus::Approved,
        'approved_by' => $this->approver->id,
        'approved_at' => now(),
    ]);

    $newApprover = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    expect($customer->canBeApprovedBy($newApprover))->toBeFalse();
});

it('isPending returns true for Pending status', function () {
    $customer = Customer::factory()->pendingApproval()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
    ]);

    expect($customer->isPending())->toBeTrue();
});

it('isApproved returns true for Approved status', function () {
    $customer = Customer::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
        'approval_status' => ApprovalStatus::Approved,
        'approved_by' => $this->approver->id,
        'approved_at' => now(),
    ]);

    expect($customer->isApproved())->toBeTrue();
});

it('scopePendingApproval filters only pending records', function () {
    $pendingCustomers = Customer::factory()->pendingApproval()->count(3)->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
    ]);

    Customer::factory()->count(2)->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->creator->id,
        'approval_status' => ApprovalStatus::Approved,
        'approved_by' => $this->approver->id,
        'approved_at' => now(),
    ]);

    $results = Customer::pendingApproval()->get();

    expect($results)->toHaveCount(3);
    $results->each(function ($customer) {
        expect($customer->approval_status)->toBe(ApprovalStatus::Pending);
    });
});
