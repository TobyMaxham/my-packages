<?php

namespace Database\Factories;

use App\Enums\Ecosystem;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->userName(),
            'ecosystem' => $this->faker->randomElement(Ecosystem::cases()),
        ];
    }

    public function composer(): static
    {
        return $this->state(['ecosystem' => Ecosystem::Composer]);
    }

    public function npm(): static
    {
        return $this->state(['ecosystem' => Ecosystem::Npm]);
    }
}
