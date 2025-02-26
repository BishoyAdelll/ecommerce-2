<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Enums\VendorStatusEnum;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'User',
            'email' => 'user@example.com',

        ])->assignRole(RolesEnum::User->value);
        $user = User::factory()->create([
            'name' => 'Vendor',
            'email' => 'vendor@example.com',

        ]);
        Vendor::factory()->create([
            'user_id' => $user->id,
            'status' => VendorStatusEnum::Approved,
            'store_name' => 'Vendor Store',
            'address' => fake()->address(),
        ]);
            $user->assignRole(RolesEnum::Vendor->value);
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',

        ])->assignRole(RolesEnum::Admin->value);
    }
}
