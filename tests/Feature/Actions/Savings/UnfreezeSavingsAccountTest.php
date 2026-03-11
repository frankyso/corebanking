<?php

use App\Actions\Savings\FreezeSavingsAccount;
use App\Actions\Savings\OpenSavingsAccount;
use App\Actions\Savings\UnfreezeSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\SavingsAccountStatus;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;

describe('UnfreezeSavingsAccount', function (): void {
    beforeEach(function (): void {
        $this->action = app(UnfreezeSavingsAccount::class);

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

    it('sets status back to Active when account is Frozen', function (): void {
        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        app(FreezeSavingsAccount::class)->execute($account);
        $this->action->execute($account);

        expect($account->fresh()->status)->toBe(SavingsAccountStatus::Active);
    });

    it('throws if account is not in Frozen status', function (): void {
        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $this->product,
            customerId: $this->customer->id,
            branchId: $this->branch->id,
            initialDeposit: 100000,
            performer: $this->user,
        ));

        expect(fn () => $this->action->execute($account))
            ->toThrow(InvalidSavingsAccountStatusException::class);
    });
});
