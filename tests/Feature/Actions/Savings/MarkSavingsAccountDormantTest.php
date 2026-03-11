<?php

use App\Actions\Savings\FreezeSavingsAccount;
use App\Actions\Savings\MarkSavingsAccountDormant;
use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\SavingsAccountStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;

describe('MarkSavingsAccountDormant', function (): void {
    beforeEach(function (): void {
        $this->action = app(MarkSavingsAccountDormant::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $this->product = SavingsProduct::factory()->create([
            'code' => 'T01',
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
            'max_balance' => null,
            'closing_fee' => 25000,
        ]);
    });

    it('sets status to Dormant for Active account', function (): void {
        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        $this->action->execute($account);

        $account->refresh();

        expect($account->status)->toBe(SavingsAccountStatus::Dormant)
            ->and($account->dormant_at)->not->toBeNull();
    });

    it('does nothing for non-Active account', function (): void {
        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        app(FreezeSavingsAccount::class)->execute($account);
        $account->refresh();

        $this->action->execute($account);

        expect($account->fresh()->status)->toBe(SavingsAccountStatus::Frozen);
    });
});
