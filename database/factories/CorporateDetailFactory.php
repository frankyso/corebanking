<?php

namespace Database\Factories;

use App\Models\CorporateDetail;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorporateDetail>
 */
class CorporateDetailFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory()->corporate(),
            'company_name' => 'PT '.fake()->company(),
            'legal_type' => fake()->randomElement(['PT', 'CV', 'Firma', 'Koperasi']),
            'nib' => fake()->unique()->numerify('#############'),
            'npwp_company' => fake()->numerify('##.###.###.#-###.###'),
            'deed_number' => 'AHU-'.fake()->numerify('#####').'.AH.01.01.'.fake()->year(),
            'deed_date' => fake()->dateTimeBetween('-10 years', 'now'),
            'business_sector' => fake()->randomElement(['Perdagangan', 'Jasa', 'Manufaktur', 'Pertanian', 'Konstruksi']),
            'address_company' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->numerify('#####'),
            'phone_office' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'annual_revenue' => fake()->randomFloat(2, 500_000_000, 10_000_000_000),
            'total_employees' => fake()->numberBetween(5, 500),
            'contact_person_name' => fake()->name(),
            'contact_person_phone' => fake()->phoneNumber(),
            'contact_person_position' => fake()->randomElement(['Direktur', 'Komisaris', 'Manager']),
        ];
    }
}
