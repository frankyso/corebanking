<?php

use App\Actions\Teller\OpenTellerSession;
use App\Actions\Teller\RequestTellerCash;
use App\DTOs\Teller\OpenTellerSessionData;
use App\Enums\TellerTransactionType;
use App\Models\Branch;
use App\Models\User;
use App\Models\Vault;

describe('RequestTellerCash', function (): void {
    beforeEach(function (): void {
        $this->action = app(RequestTellerCash::class);

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

    it('creates vault and teller transactions for cash request', function (): void {
        $session = app(OpenTellerSession::class)->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));
        $vaultBalanceBefore = (float) $this->vault->fresh()->balance;

        $tx = $this->action->execute($session, $this->vault, 3000000, $this->teller);

        expect($tx->transaction_type)->toBe(TellerTransactionType::CashRequest)
            ->and($tx->direction)->toBe('in')
            ->and((float) $tx->amount)->toBe(3000000.00);

        $session->refresh();
        expect((float) $session->current_balance)->toBe(8000000.00);

        $this->vault->refresh();
        expect((float) $this->vault->balance)->toBe($vaultBalanceBefore - 3000000);
    });
});
