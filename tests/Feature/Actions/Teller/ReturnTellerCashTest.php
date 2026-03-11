<?php

use App\Actions\Teller\OpenTellerSession;
use App\Actions\Teller\ReturnTellerCash;
use App\DTOs\Teller\OpenTellerSessionData;
use App\Enums\TellerTransactionType;
use App\Exceptions\Teller\InsufficientTellerCashException;
use App\Models\Branch;
use App\Models\User;
use App\Models\Vault;

describe('ReturnTellerCash', function (): void {
    beforeEach(function (): void {
        $this->action = app(ReturnTellerCash::class);

        $this->branch = Branch::create([
            'code' => '001',
            'name' => 'Cabang Utama',
            'is_head_office' => true,
            'is_active' => true,
        ]);

        $this->teller = User::factory()->create(['branch_id' => $this->branch->id]);

        $this->vault = Vault::factory()->create([
            'branch_id' => $this->branch->id,
            'balance' => 100000000,
        ]);
    });

    it('creates vault and teller transactions for cash return', function (): void {
        $session = app(OpenTellerSession::class)->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));
        $vaultBalanceBefore = (float) $this->vault->fresh()->balance;

        $tx = $this->action->execute($session, $this->vault, 2000000, $this->teller);

        expect($tx->transaction_type)->toBe(TellerTransactionType::CashReturn)
            ->and($tx->direction)->toBe('out')
            ->and((float) $tx->amount)->toBe(2000000.00);

        $session->refresh();
        expect((float) $session->current_balance)->toBe(3000000.00);

        $this->vault->refresh();
        expect((float) $this->vault->balance)->toBe($vaultBalanceBefore + 2000000);
    });

    it('throws when amount exceeds session current balance', function (): void {
        $session = app(OpenTellerSession::class)->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));

        $this->action->execute($session, $this->vault, 6000000, $this->teller);
    })->throws(InsufficientTellerCashException::class, 'Saldo kas teller tidak mencukupi');
});
