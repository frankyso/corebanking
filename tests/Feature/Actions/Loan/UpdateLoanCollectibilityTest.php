<?php

use App\Actions\Loan\UpdateLoanCollectibility;
use App\Enums\Collectibility;
use App\Enums\LoanStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use App\Models\User;

describe('UpdateLoanCollectibility', function (): void {
    beforeEach(function (): void {
        $this->action = app(UpdateLoanCollectibility::class);

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

        $this->product = LoanProduct::factory()->create(['code' => 'KMK']);

        $this->loanAccount = LoanAccount::factory()->create([
            'customer_id' => $this->customer->id,
            'loan_product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
            'outstanding_principal' => 10000000,
        ]);
    });

    it('sets Current collectibility for dpd 0', function (): void {
        $this->loanAccount->update(['dpd' => 0]);
        $this->action->execute($this->loanAccount);

        expect($this->loanAccount->fresh()->collectibility)->toBe(Collectibility::Current);
    });

    it('sets SpecialMention for dpd 1-90', function (): void {
        $this->loanAccount->update(['dpd' => 45]);
        $this->action->execute($this->loanAccount);

        expect($this->loanAccount->fresh()->collectibility)->toBe(Collectibility::SpecialMention);
    });

    it('sets Substandard for dpd 91-120', function (): void {
        $this->loanAccount->update(['dpd' => 100]);
        $this->action->execute($this->loanAccount);

        expect($this->loanAccount->fresh()->collectibility)->toBe(Collectibility::Substandard);
    });

    it('sets Doubtful for dpd 121-180', function (): void {
        $this->loanAccount->update(['dpd' => 150]);
        $this->action->execute($this->loanAccount);

        expect($this->loanAccount->fresh()->collectibility)->toBe(Collectibility::Doubtful);
    });

    it('sets Loss for dpd above 180', function (): void {
        $this->loanAccount->update(['dpd' => 200]);
        $this->action->execute($this->loanAccount);

        $account = $this->loanAccount->fresh();
        expect($account->collectibility)->toBe(Collectibility::Loss)
            ->and((float) $account->ckpn_amount)->toBeGreaterThan(0);
    });

    it('calculates CKPN amount based on collectibility rate', function (): void {
        $this->loanAccount->update(['dpd' => 200, 'outstanding_principal' => 10000000]);
        $this->action->execute($this->loanAccount);

        $account = $this->loanAccount->fresh();
        // Loss = 100% CKPN
        expect((float) $account->ckpn_amount)->toBe(10000000.00);
    });
});
