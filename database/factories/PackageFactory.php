<?php

namespace Database\Factories;

use App\Enums\Ecosystem;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'ecosystem' => Ecosystem::Composer,
            'name' => $this->faker->unique()->slug(2),
            'description' => $this->faker->sentence(),
            'tags' => $this->faker->randomElements(['laravel', 'http', 'testing', 'cache', 'queue', 'validation'], 2),
            'repository_url' => 'https://github.com/'.$this->faker->userName().'/'.$this->faker->slug(2),
            'abandoned' => false,
            'latest_version' => 'v'.$this->faker->numerify('#.#.#'),
            'php_constraint' => '^8.2',
            'framework_constraint' => '^11.0|^12.0',
            'last_release_at' => $this->faker->dateTimeBetween('-6 months'),
            'status' => PackageStatus::Compatible,
            'is_featured' => false,
            'featured_note' => null,
            'downloads_total' => $this->faker->numberBetween(0, 1_000_000),
            'favers' => $this->faker->numberBetween(0, 5_000),
            'last_checked_at' => now(),
        ];
    }

    public function abandoned(): static
    {
        return $this->state(['abandoned' => true]);
    }

    public function featured(): static
    {
        return $this->state([
            'is_featured' => true,
            'featured_note' => $this->faker->sentence(),
        ]);
    }

    public function stale(): static
    {
        return $this->state([
            'last_release_at' => $this->faker->dateTimeBetween('-3 years', '-13 months'),
        ]);
    }
}
