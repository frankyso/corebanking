<?php

namespace Database\Factories;

use App\Enums\AccountGroup;
use App\Enums\NormalBalance;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChartOfAccount>
 */
class ChartOfAccountFactory extends Factory
{
    public function definition(): array
    {
        $group = fake()->randomElement(AccountGroup::cases());

        return [
            'account_code' => $group->codePrefix().fake()->unique()->numerify('####'),
            'account_name' => fake()->words(3, true),
            'account_group' => $group,
            'parent_id' => null,
            'level' => 1,
            'is_header' => false,
            'is_active' => true,
            'normal_balance' => in_array($group, [AccountGroup::Asset, AccountGroup::Expense])
                ? NormalBalance::Debit
                : NormalBalance::Credit,
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function header(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_header' => true,
        ]);
    }

    public function asset(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_group' => AccountGroup::Asset,
            'account_code' => '1'.fake()->unique()->numerify('####'),
            'normal_balance' => NormalBalance::Debit,
        ]);
    }

    public function liability(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_group' => AccountGroup::Liability,
            'account_code' => '2'.fake()->unique()->numerify('####'),
            'normal_balance' => NormalBalance::Credit,
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_group' => AccountGroup::Revenue,
            'account_code' => '4'.fake()->unique()->numerify('####'),
            'normal_balance' => NormalBalance::Credit,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes): array => [
            'account_group' => AccountGroup::Expense,
            'account_code' => '5'.fake()->unique()->numerify('####'),
            'normal_balance' => NormalBalance::Debit,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function childOf(ChartOfAccount $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
            'account_group' => $parent->account_group,
            'normal_balance' => $parent->normal_balance,
            'level' => $parent->level + 1,
        ]);
    }
}
