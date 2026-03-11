<?php

use App\Actions\Teller\CloseTellerSession;
use App\Actions\Teller\OpenTellerSession;
use App\DTOs\Teller\OpenTellerSessionData;
use App\Enums\TellerSessionStatus;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\Branch;
use App\Models\User;
use App\Models\Vault;

describe('CloseTellerSession', function (): void {
    beforeEach(function (): void {
        $this->action = app(CloseTellerSession::class);
        $this->openAction = app(OpenTellerSession::class);

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

    it('closes session and returns cash to vault', function (): void {
        $session = $this->openAction->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));
        $vaultBalanceBefore = (float) $this->vault->fresh()->balance;

        $closed = $this->action->execute($session, $this->teller, 'Tutup shift');

        expect($closed->status)->toBe(TellerSessionStatus::Closed)
            ->and((float) $closed->closing_balance)->toBe(5000000.00)
            ->and($closed->closed_at)->not->toBeNull()
            ->and($closed->closing_notes)->toBe('Tutup shift');

        $this->vault->refresh();
        expect((float) $this->vault->balance)->toBe($vaultBalanceBefore + 5000000);
    });

    it('throws when session is already closed', function (): void {
        $session = $this->openAction->execute(new OpenTellerSessionData(
            teller: $this->teller,
            vault: $this->vault,
            openingBalance: 5000000,
        ));
        $this->action->execute($session, $this->teller);

        $this->action->execute($session->fresh(), $this->teller);
    })->throws(TellerSessionClosedException::class, 'Sesi sudah ditutup');
});
