<?php

namespace Database\Factories;

use App\Models\MobileUser;
use App\Models\SavingsAccount;
use App\Models\TransferFavorite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransferFavorite>
 */
class TransferFavoriteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mobile_user_id' => MobileUser::factory(),
            'savings_account_id' => SavingsAccount::factory(),
            'alias' => fake()->name(),
        ];
    }
}
