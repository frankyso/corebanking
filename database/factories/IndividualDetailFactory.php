<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Models\Customer;
use App\Models\IndividualDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndividualDetail>
 */
class IndividualDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory()->individual(),
            'nik' => fake()->unique()->numerify('################'),
            'full_name' => fake()->name(),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years'),
            'gender' => fake()->randomElement(Gender::cases()),
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'mother_maiden_name' => fake()->firstName('female'),
            'religion' => fake()->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']),
            'education' => fake()->randomElement(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3']),
            'nationality' => 'IDN',
            'phone_mobile' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'occupation' => fake()->jobTitle(),
            'monthly_income' => fake()->randomFloat(2, 3_000_000, 50_000_000),
            'source_of_fund' => fake()->randomElement(['Gaji', 'Usaha', 'Warisan', 'Investasi']),
            'transaction_purpose' => fake()->randomElement(['Tabungan', 'Pinjaman', 'Deposito']),
        ];
    }
}
