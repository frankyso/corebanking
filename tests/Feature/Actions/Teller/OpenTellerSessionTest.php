<?php

use App\Actions\Teller\OpenTellerSession;
use App\DTOs\Teller\OpenTellerSessionData;
use App\Enums\TellerSessionStatus;
use App\Enums\VaultTransactionType;
use App\Exceptions\Teller\TellerSessionAlreadyOpenException;
use App\Models\Branch;
use App\Models\TellerSession;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;

describe('OpenTellerSession', function (): void {
    beforeEach(function (): void {
        $this->action = app(OpenTellerSession::class);

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

    it('creates an open teller session with correct opening balance', function (): void {
        $session = $this->action->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));

        expect($session)->toBeInstanceOf(TellerSession::class)
            ->and($session->status)->toBe(TellerSessionStatus::Open)
            ->and((float) $session->opening_balance)->toBe(5000000.00)
            ->and((float) $session->current_balance)->toBe(5000000.00)
            ->and((float) $session->total_cash_in)->toBe(0.00)
            ->and((float) $session->total_cash_out)->toBe(0.00)
            ->and($session->transaction_count)->toBe(0)
            ->and($session->user_id)->toBe($this->teller->id);
    });

    it('creates a vault transaction for initial cash draw', function (): void {
        $vaultBalanceBefore = (float) $this->vault->balance;
        $this->action->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));

        $this->vault->refresh();
        expect((float) $this->vault->balance)->toBe($vaultBalanceBefore - 5000000);

        $vaultTx = VaultTransaction::where('vault_id', $this->vault->id)->first();
        expect($vaultTx->transaction_type)->toBe(VaultTransactionType::TellerRequest);
    });

    it('throws when teller already has an active session', function (): void {
        $this->action->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));

        $this->action->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 3000000,
        ));
    })->throws(TellerSessionAlreadyOpenException::class, 'Teller sudah memiliki sesi aktif');
});
