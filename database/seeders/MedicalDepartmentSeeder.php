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
                'discount_percentage' => 100.00,
                'max_discount_amount' => null,
                'is_active' => true,
            ],
            [
                'name' => 'pharmacy',
                'discount_percentage' => 25.00,
                'max_discount_amount' => 30.00,
                'is_active' => true,
            ],
            [
                'name' => 'laboratory',
                'discount_percentage' => 25.00,
                'max_discount_amount' => 30.00,
                'is_active' => true,
            ],
            [
                'name' => 'optics',
                'discount_percentage' => 25.00,
                'max_discount_amount' => null,
                'is_active' => true,
            ],
            [
                'name' => 'dental',
                'discount_percentage' => 25.00,
                'max_discount_amount' => null,
                'is_active' => true,
            ],
            [
                'name' => 'radiology',
                'discount_percentage' => 0.00,
                'max_discount_amount' => null,
                'is_active' => false,
            ],
        ]);
    }
}
