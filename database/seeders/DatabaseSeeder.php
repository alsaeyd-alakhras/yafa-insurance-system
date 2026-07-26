<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MedicalDepartmentSeeder::class);
        $this->call(OrganizationUnitSeeder::class);

        User::create([
            'name' => 'Alsaeyd J Alakhras',
            'email' => 'alsaeydjalkhras@gmail.com',
            'password'  => '20051118Jamal',
            'username'  => 'saeyd_jamal',
            'last_activity'  => now(),
            'avatar'  => null,
            'super_admin'  => 1,
        ]);
    }
}