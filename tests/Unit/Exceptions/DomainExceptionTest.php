<?php

use App\Exceptions\Accounting\InvalidJournalStatusException;
use App\Exceptions\Accounting\UnbalancedJournalException;
use App\Exceptions\Customer\CustomerApprovalException;
use App\Exceptions\Deposit\DepositPledgedException;
use App\Exceptions\Deposit\InvalidDepositAmountException;
use App\Exceptions\Deposit\InvalidDepositStatusException;
use App\Exceptions\DomainException;
use App\Exceptions\Eod\EodAlreadyRunException;
use App\Exceptions\Eod\EodPreCheckFailedException;
use App\Exceptions\Loan\InvalidLoanAmountException;
use App\Exceptions\Loan\InvalidLoanStatusException;
use App\Exceptions\Loan\InvalidLoanTenorException;
use App\Exceptions\Loan\LoanSelfApprovalException;
use App\Exceptions\Savings\InsufficientSavingsBalanceException;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Exceptions\Savings\SavingsBalanceLimitException;
use App\Exceptions\Teller\InsufficientTellerCashException;
use App\Exceptions\Teller\TellerSessionAlreadyOpenException;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\JournalEntry;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\TellerSession;
use App\Models\User;
use Carbon\Carbon;

// ============================================================================
// DomainException base class
// ============================================================================

describe('DomainException base', function (): void {
    it('is abstract and extends RuntimeException', function (): void {
        $reflection = new ReflectionClass(DomainException::class);

        expect($reflection->isAbstract())->toBeTrue()
            ->and($reflection->getParentClass()->getName())->toBe(RuntimeException::class);
    });

    it('supports withContext and getContext', function (): void {
        $exception = CustomerApprovalException::notPending(
            new Customer(['id' => 1, 'approval_status' => null])
        );

        expect($exception->getContext())->toBeArray()
            ->and($exception->getContext())->toHaveKey('customer_id');
    });

    it('returns empty context by default', function (): void {
        $exception = UnbalancedJournalException::lineHasBothDebitAndCredit();
        // This factory does not call withContext, so context should be empty
        expect($exception->getContext())->toBe([]);
    });
});

// ============================================================================
// Customer Exceptions
// ============================================================================

describe('CustomerApprovalException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(CustomerApprovalException::class, DomainException::class))->toBeTrue();
    });

    it('selfApproval returns correct message and context', function (): void {
        $customer = new Customer;
        $customer->id = 10;
        $user = new User;
        $user->id = 5;

        $exception = CustomerApprovalException::selfApproval($customer, $user);

        expect($exception)->toBeInstanceOf(DomainException::class)
            ->and($exception->getMessage())->toBe('Pembuat data tidak dapat menyetujui/menolak data sendiri')
            ->and($exception->getContext())->toBe(['customer_id' => 10, 'user_id' => 5]);
    });

    it('notPending returns correct message and context', function (): void {
        $customer = new Customer;
        $customer->id = 7;

        $exception = CustomerApprovalException::notPending($customer);

        expect($exception->getMessage())->toBe('Nasabah tidak dalam status menunggu persetujuan')
            ->and($exception->getContext())->toHaveKey('customer_id', 7);
    });
});

// ============================================================================
// Savings Exceptions
// ============================================================================

describe('InsufficientSavingsBalanceException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(InsufficientSavingsBalanceException::class, DomainException::class))->toBeTrue();
    });

    it('forWithdrawal returns correct message and context', function (): void {
        $account = new SavingsAccount(['id' => 1, 'available_balance' => 500000]);

        $exception = InsufficientSavingsBalanceException::forWithdrawal($account, 1000000);

        expect($exception->getMessage())->toBe('Saldo tidak mencukupi')
            ->and($exception->getContext())->toHaveKeys(['account_id', 'requested_amount', 'available_balance'])
            ->and($exception->getContext()['requested_amount'])->toBe(1000000.0);
    });

    it('forHold returns correct message', function (): void {
        $account = new SavingsAccount(['id' => 2, 'available_balance' => 300000]);

        $exception = InsufficientSavingsBalanceException::forHold($account, 500000);

        expect($exception->getMessage())->toBe('Saldo tersedia tidak mencukupi untuk pemblokiran');
    });

    it('unholdExceedsHeldAmount returns correct message', function (): void {
        $account = new SavingsAccount(['id' => 3, 'hold_amount' => 100000]);

        $exception = InsufficientSavingsBalanceException::unholdExceedsHeldAmount($account, 200000);

        expect($exception->getMessage())->toBe('Jumlah melebihi saldo yang diblokir');
    });

    it('invalidAmount returns correct message with operation', function (): void {
        $exception = InsufficientSavingsBalanceException::invalidAmount('setoran');

        expect($exception->getMessage())->toBe('Jumlah setoran harus lebih dari 0')
            ->and($exception->getContext())->toBe(['operation' => 'setoran']);
    });
});

describe('InvalidSavingsAccountStatusException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(InvalidSavingsAccountStatusException::class, DomainException::class))->toBeTrue();
    });

    it('notActive returns correct message', function (): void {
        $account = new SavingsAccount(['id' => 1, 'status' => 'frozen']);

        $exception = InvalidSavingsAccountStatusException::notActive($account);

        expect($exception->getMessage())->toBe('Rekening tidak aktif');
    });

    it('notFrozen returns correct message', function (): void {
        $account = new SavingsAccount(['id' => 2, 'status' => 'active']);

        $exception = InvalidSavingsAccountStatusException::notFrozen($account);

        expect($exception->getMessage())->toBe('Rekening tidak dalam status dibekukan');
    });

    it('cannotClose returns correct message', function (): void {
        $account = new SavingsAccount(['id' => 3, 'status' => 'dormant']);

        $exception = InvalidSavingsAccountStatusException::cannotClose($account);

        expect($exception->getMessage())->toBe('Rekening tidak dapat ditutup');
    });

    it('hasHoldAmount returns correct message and context', function (): void {
        $account = new SavingsAccount(['id' => 4, 'hold_amount' => 500000]);

        $exception = InvalidSavingsAccountStatusException::hasHoldAmount($account);

        expect($exception->getMessage())->toBe('Rekening masih memiliki saldo diblokir')
            ->and($exception->getContext())->toHaveKey('hold_amount', 500000.0);
    });
});

describe('SavingsBalanceLimitException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(SavingsBalanceLimitException::class, DomainException::class))->toBeTrue();
    });

    it('belowMinimumOpeningBalance returns correct message', function (): void {
        $product = new SavingsProduct;
        $product->id = 1;
        $product->min_opening_balance = 50000;

        $exception = SavingsBalanceLimitException::belowMinimumOpeningBalance($product);

        expect($exception->getMessage())->toContain('Setoran awal minimal')
            ->and($exception->getContext())->toHaveKey('product_id', 1);
    });

    it('exceedsMaximumBalance returns correct message', function (): void {
        $account = new SavingsAccount(['id' => 1]);
        $account->setRelation('savingsProduct', new SavingsProduct(['max_balance' => 100000000]));

        $exception = SavingsBalanceLimitException::exceedsMaximumBalance($account, 150000000);

        expect($exception->getMessage())->toBe('Saldo melebihi batas maksimal')
            ->and($exception->getContext())->toHaveKeys(['account_id', 'projected_balance', 'max_balance']);
    });
});

// ============================================================================
// Loan Exceptions
// ============================================================================

describe('InvalidLoanAmountException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(InvalidLoanAmountException::class, DomainException::class))->toBeTrue();
    });

    it('belowMinimum returns correct message and context', function (): void {
        $product = new LoanProduct(['id' => 1, 'min_amount' => 5000000]);

        $exception = InvalidLoanAmountException::belowMinimum($product, 1000000);

        expect($exception->getMessage())->toBe('Jumlah pinjaman kurang dari minimum')
            ->and($exception->getContext())->toHaveKeys(['product_id', 'min_amount', 'requested_amount']);
    });

    it('aboveMaximum returns correct message and context', function (): void {
        $product = new LoanProduct(['id' => 2, 'max_amount' => 100000000]);

        $exception = InvalidLoanAmountException::aboveMaximum($product, 200000000);

        expect($exception->getMessage())->toBe('Jumlah pinjaman melebihi maksimum')
            ->and($exception->getContext())->toHaveKeys(['product_id', 'max_amount', 'requested_amount']);
    });
});

describe('InvalidLoanTenorException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(InvalidLoanTenorException::class, DomainException::class))->toBeTrue();
    });

    it('outOfRange returns correct message with range values', function (): void {
        $product = new LoanProduct(['id' => 1, 'min_tenor_months' => 6, 'max_tenor_months' => 60]);

        $exception = InvalidLoanTenorException::outOfRange($product, 3);

        expect($exception->getMessage())->toContain('6')
            ->and($exception->getMessage())->toContain('60')
            ->and($exception->getContext())->toHaveKeys(['product_id', 'min_tenor', 'max_tenor', 'requested_tenor']);
    });
});

describe('InvalidLoanStatusException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(InvalidLoanStatusException::class, DomainException::class))->toBeTrue();
    });

    it('notApprovable returns correct message', function (): void {
        $application = new LoanApplication(['id' => 1, 'status' => 'draft']);

        $exception = InvalidLoanStatusException::notApprovable($application);

        expect($exception->getMessage())->toBe('Permohonan tidak dalam status yang dapat disetujui');
    });

    it('notRejectable returns correct message', function (): void {
        $application = new LoanApplication(['id' => 2, 'status' => 'approved']);

        $exception = InvalidLoanStatusException::notRejectable($application);

        expect($exception->getMessage())->toBe('Permohonan tidak dalam status yang dapat ditolak');
    });

    it('notApproved returns correct message', function (): void {
        $application = new LoanApplication(['id' => 3, 'status' => 'draft']);

        $exception = InvalidLoanStatusException::notApproved($application);

        expect($exception->getMessage())->toBe('Permohonan belum disetujui');
    });

    it('notActive returns correct message', function (): void {
        $account = new LoanAccount(['id' => 1, 'status' => 'closed']);

        $exception = InvalidLoanStatusException::notActive($account);

        expect($exception->getMessage())->toBe('Pinjaman tidak dalam status aktif');
    });

    it('invalidPaymentAmount returns correct message and context', function (): void {
        $exception = InvalidLoanStatusException::invalidPaymentAmount(-100);

        expect($exception->getMessage())->toBe('Jumlah pembayaran harus lebih dari 0')
            ->and($exception->getContext())->toBe(['amount' => -100.0]);
    });
});

describe('LoanSelfApprovalException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(LoanSelfApprovalException::class, DomainException::class))->toBeTrue();
    });

    it('cannotApproveSelf returns correct message and context', function (): void {
        $application = new LoanApplication;
        $application->id = 5;
        $user = new User;
        $user->id = 3;

        $exception = LoanSelfApprovalException::cannotApproveSelf($application, $user);

        expect($exception->getMessage())->toBe('Tidak dapat menyetujui permohonan yang Anda buat sendiri')
            ->and($exception->getContext())->toBe(['application_id' => 5, 'user_id' => 3]);
    });
});

// ============================================================================
// Accounting Exceptions
// ============================================================================

describe('UnbalancedJournalException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(UnbalancedJournalException::class, DomainException::class))->toBeTrue();
    });

    it('debitCreditMismatch returns correct message with amounts', function (): void {
        $exception = UnbalancedJournalException::debitCreditMismatch('1000000.00', '900000.00');

        expect($exception->getMessage())->toContain('1000000.00')
            ->and($exception->getMessage())->toContain('900000.00')
            ->and($exception->getContext())->toBe([
                'total_debit' => '1000000.00',
                'total_credit' => '900000.00',
            ]);
    });

    it('lineHasBothDebitAndCredit returns correct message', function (): void {
        $exception = UnbalancedJournalException::lineHasBothDebitAndCredit();

        expect($exception->getMessage())->toBe('Baris jurnal tidak boleh memiliki debit dan kredit sekaligus');
    });

    it('lineHasNoAmount returns correct message', function (): void {
        $exception = UnbalancedJournalException::lineHasNoAmount();

        expect($exception->getMessage())->toBe('Baris jurnal harus memiliki debit atau kredit');
    });

    it('tooFewLines returns correct message and context', function (): void {
        $exception = UnbalancedJournalException::tooFewLines(1);

        expect($exception->getMessage())->toBe('Jurnal harus memiliki minimal 2 baris')
            ->and($exception->getContext())->toBe(['line_count' => 1]);
    });

    it('invalidAccount returns correct message and context', function (): void {
        $exception = UnbalancedJournalException::invalidAccount(99);

        expect($exception->getMessage())->toBe('Akun tidak valid atau merupakan akun header')
            ->and($exception->getContext())->toBe(['account_id' => 99]);
    });
});

describe('InvalidJournalStatusException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(InvalidJournalStatusException::class, DomainException::class))->toBeTrue();
    });

    it('notDraft returns correct message', function (): void {
        $journal = new JournalEntry(['id' => 1, 'status' => 'posted']);

        $exception = InvalidJournalStatusException::notDraft($journal);

        expect($exception->getMessage())->toBe('Jurnal harus berstatus Draft untuk diposting');
    });

    it('notPosted returns correct message', function (): void {
        $journal = new JournalEntry(['id' => 2, 'status' => 'draft']);

        $exception = InvalidJournalStatusException::notPosted($journal);

        expect($exception->getMessage())->toBe('Hanya jurnal yang sudah diposting yang dapat dibatalkan');
    });

    it('notBalanced returns correct message', function (): void {
        $journal = new JournalEntry(['id' => 3, 'total_debit' => 1000, 'total_credit' => 900]);

        $exception = InvalidJournalStatusException::notBalanced($journal);

        expect($exception->getMessage())->toBe('Total debit dan kredit tidak seimbang');
    });

    it('selfApproval returns correct message and context', function (): void {
        $journal = new JournalEntry;
        $journal->id = 4;
        $user = new User;
        $user->id = 10;

        $exception = InvalidJournalStatusException::selfApproval($journal, $user);

        expect($exception->getMessage())->toBe('Anda tidak dapat menyetujui jurnal yang Anda buat sendiri')
            ->and($exception->getContext())->toBe(['journal_id' => 4, 'user_id' => 10]);
    });
});

// ============================================================================
// Deposit Exceptions
// ============================================================================

describe('InvalidDepositAmountException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(InvalidDepositAmountException::class, DomainException::class))->toBeTrue();
    });

    it('belowMinimum returns correct message', function (): void {
        $product = new DepositProduct(['id' => 1, 'min_amount' => 1000000]);

        $exception = InvalidDepositAmountException::belowMinimum($product);

        expect($exception->getMessage())->toContain('Nominal minimal deposito');
    });

    it('aboveMaximum returns correct message', function (): void {
        $product = new DepositProduct(['id' => 2, 'max_amount' => 2000000000]);

        $exception = InvalidDepositAmountException::aboveMaximum($product);

        expect($exception->getMessage())->toContain('Nominal maksimal deposito');
    });

    it('noRateAvailable returns correct message with tenor', function (): void {
        $exception = InvalidDepositAmountException::noRateAvailable(6);

        expect($exception->getMessage())->toContain('6 bulan')
            ->and($exception->getContext())->toBe(['tenor_months' => 6]);
    });
});

describe('InvalidDepositStatusException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(InvalidDepositStatusException::class, DomainException::class))->toBeTrue();
    });

    it('notActive returns correct message', function (): void {
        $account = new DepositAccount(['id' => 1, 'status' => 'matured']);

        $exception = InvalidDepositStatusException::notActive($account);

        expect($exception->getMessage())->toBe('Deposito tidak dalam status aktif');
    });

    it('notMatured returns correct message', function (): void {
        $account = new DepositAccount(['id' => 2, 'maturity_date' => now()->addMonth()]);

        $exception = InvalidDepositStatusException::notMatured($account);

        expect($exception->getMessage())->toBe('Deposito belum jatuh tempo');
    });

    it('alreadyPledged returns correct message', function (): void {
        $account = new DepositAccount(['id' => 3, 'pledge_reference' => 'PLG-001']);

        $exception = InvalidDepositStatusException::alreadyPledged($account);

        expect($exception->getMessage())->toBe('Deposito sudah dijaminkan');
    });

    it('notPledged returns correct message', function (): void {
        $account = new DepositAccount(['id' => 4]);

        $exception = InvalidDepositStatusException::notPledged($account);

        expect($exception->getMessage())->toBe('Deposito tidak sedang dijaminkan');
    });
});

describe('DepositPledgedException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(DepositPledgedException::class, DomainException::class))->toBeTrue();
    });

    it('cannotWithdraw returns correct message and context', function (): void {
        $account = new DepositAccount(['id' => 5, 'pledge_reference' => 'PLG-002']);

        $exception = DepositPledgedException::cannotWithdraw($account);

        expect($exception->getMessage())->toBe('Deposito sedang dijaminkan, tidak dapat dicairkan')
            ->and($exception->getContext())->toHaveKeys(['account_id', 'pledge_reference']);
    });
});

// ============================================================================
// Teller Exceptions
// ============================================================================

describe('TellerSessionAlreadyOpenException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(TellerSessionAlreadyOpenException::class, DomainException::class))->toBeTrue();
    });

    it('alreadyOpen returns correct message and context', function (): void {
        $user = new User;
        $user->id = 7;
        $user->name = 'Teller A';

        $exception = TellerSessionAlreadyOpenException::alreadyOpen($user);

        expect($exception->getMessage())->toBe('Teller sudah memiliki sesi aktif')
            ->and($exception->getContext())->toBe(['teller_id' => 7, 'teller_name' => 'Teller A']);
    });
});

describe('TellerSessionClosedException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(TellerSessionClosedException::class, DomainException::class))->toBeTrue();
    });

    it('notOpen returns correct message', function (): void {
        $session = new TellerSession(['id' => 1, 'status' => 'closed']);

        $exception = TellerSessionClosedException::notOpen($session);

        expect($exception->getMessage())->toBe('Sesi teller tidak aktif');
    });

    it('alreadyClosed returns correct message', function (): void {
        $session = new TellerSession(['id' => 2, 'closed_at' => now()]);

        $exception = TellerSessionClosedException::alreadyClosed($session);

        expect($exception->getMessage())->toBe('Sesi sudah ditutup');
    });
});

describe('InsufficientTellerCashException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(InsufficientTellerCashException::class, DomainException::class))->toBeTrue();
    });

    it('insufficientBalance returns correct message and context', function (): void {
        $session = new TellerSession(['id' => 3, 'current_balance' => 500000]);

        $exception = InsufficientTellerCashException::insufficientBalance($session, 1000000);

        expect($exception->getMessage())->toBe('Saldo kas teller tidak mencukupi')
            ->and($exception->getContext())->toHaveKeys(['session_id', 'current_balance', 'requested_amount']);
    });
});

// ============================================================================
// EOD Exceptions
// ============================================================================

describe('EodPreCheckFailedException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(EodPreCheckFailedException::class, DomainException::class))->toBeTrue();
    });

    it('openTellerSessions returns correct message with count', function (): void {
        $exception = EodPreCheckFailedException::openTellerSessions(3);

        expect($exception->getMessage())->toContain('3 sesi teller')
            ->and($exception->getContext())->toBe(['open_sessions' => 3]);
    });
});

describe('EodAlreadyRunException', function (): void {
    it('extends DomainException', function (): void {
        expect(is_subclass_of(EodAlreadyRunException::class, DomainException::class))->toBeTrue();
    });

    it('alreadyCompleted returns correct message with formatted date', function (): void {
        $date = Carbon::create(2026, 3, 12);

        $exception = EodAlreadyRunException::alreadyCompleted($date);

        expect($exception->getMessage())->toContain('12/03/2026')
            ->and($exception->getContext())->toBe(['date' => '2026-03-12']);
    });

    it('alreadyRunning returns correct message', function (): void {
        $exception = EodAlreadyRunException::alreadyRunning();

        expect($exception->getMessage())->toBe('EOD sedang berjalan');
    });
});
