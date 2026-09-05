<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the single V1 admin user from the ADMIN_EMAIL / ADMIN_PASSWORD
     * environment variables. There is no registration flow, so this is the
     * only way to obtain an account.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            throw new RuntimeException(
                'ADMIN_EMAIL and ADMIN_PASSWORD must be set in .env before seeding the admin user.'
            );
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => $password, 'email_verified_at' => now()],
        );
    }
}
