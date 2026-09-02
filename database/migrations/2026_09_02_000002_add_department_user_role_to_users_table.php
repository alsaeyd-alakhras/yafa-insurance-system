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
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','receptionist','department_user') NOT NULL DEFAULT 'receptionist'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')
            ->where('role', 'department_user')
            ->update(['role' => 'receptionist']);

        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','receptionist') NOT NULL DEFAULT 'receptionist'");
    }
};
