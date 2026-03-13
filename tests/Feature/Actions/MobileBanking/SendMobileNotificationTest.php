<?php

use App\Actions\MobileBanking\SendMobileNotification;
use App\Enums\NotificationType;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\MobileNotification;
use App\Models\MobileUser;
use App\Models\User;

describe('SendMobileNotification', function (): void {
    beforeEach(function (): void {
        $this->action = app(SendMobileNotification::class);

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

        $this->mobileUser = MobileUser::factory()->create([
            'customer_id' => $this->customer->id,
        ]);
    });

    it('creates a MobileNotification record', function (): void {
        $notification = $this->action->execute(
            mobileUser: $this->mobileUser,
            title: 'Transfer Berhasil',
            body: 'Transfer sebesar Rp 100.000 telah berhasil.',
        );

        expect($notification)->toBeInstanceOf(MobileNotification::class)
            ->and($notification->exists)->toBeTrue()
            ->and($notification->mobile_user_id)->toBe($this->mobileUser->id)
            ->and($notification->title)->toBe('Transfer Berhasil')
            ->and($notification->body)->toBe('Transfer sebesar Rp 100.000 telah berhasil.');
    });

    it('defaults to Transaction notification type', function (): void {
        $notification = $this->action->execute(
            mobileUser: $this->mobileUser,
            title: 'Transfer Berhasil',
            body: 'Transfer berhasil dilakukan.',
        );

        expect($notification->type)->toBe(NotificationType::Transaction);
    });

    it('accepts custom notification type', function (): void {
        $notification = $this->action->execute(
            mobileUser: $this->mobileUser,
            title: 'Peringatan Keamanan',
            body: 'Login dari perangkat baru terdeteksi.',
            type: NotificationType::Security,
        );

        expect($notification->type)->toBe(NotificationType::Security);
    });

    it('is unread by default', function (): void {
        $notification = $this->action->execute(
            mobileUser: $this->mobileUser,
            title: 'Notifikasi',
            body: 'Ini adalah notifikasi.',
        );

        $notification->refresh();

        expect($notification->is_read)->toBeFalse()
            ->and($notification->read_at)->toBeNull();
    });

    it('stores additional data when provided', function (): void {
        $data = [
            'transaction_id' => 123,
            'amount' => 500000,
            'reference' => 'TRF20260313001',
        ];

        $notification = $this->action->execute(
            mobileUser: $this->mobileUser,
            title: 'Transfer Berhasil',
            body: 'Transfer berhasil.',
            data: $data,
        );

        expect($notification->data)->toBe($data)
            ->and($notification->data['transaction_id'])->toBe(123)
            ->and($notification->data['amount'])->toBe(500000)
            ->and($notification->data['reference'])->toBe('TRF20260313001');
    });

    it('stores null data when not provided', function (): void {
        $notification = $this->action->execute(
            mobileUser: $this->mobileUser,
            title: 'Promo',
            body: 'Ada promo baru!',
            type: NotificationType::Promo,
        );

        expect($notification->data)->toBeNull();
    });
});
