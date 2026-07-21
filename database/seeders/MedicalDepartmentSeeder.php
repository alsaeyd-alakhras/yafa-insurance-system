<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('medical_departments')->insert([
            [
                'name' => 'clinics',
                'discount_percentage' => 0.00,
                'is_active' => true,
            ],
            [
                'name' => 'laboratory',
                'discount_percentage' => 0.00,
                'is_active' => true,
            ],
            [
                'name' => 'pharmacy',
                'discount_percentage' => 0.00,
                'is_active' => true,
            ],
            [
                'name' => 'radiology',
                'discount_percentage' => 0.00,
                'is_active' => true,
            ],
        ]);
    }
}
