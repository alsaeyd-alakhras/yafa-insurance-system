<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('medical_departments')
            ->where('name', 'radiology')
            ->update(['is_active' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('medical_departments')
            ->where('name', 'radiology')
            ->update(['is_active' => false]);
    }
};
