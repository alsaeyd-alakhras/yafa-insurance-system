<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visit_departments', function (Blueprint $table) {
            $table->decimal('applied_max_discount_amount', 10, 2)->nullable()->after('applied_discount_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_departments', function (Blueprint $table) {
            $table->dropColumn('applied_max_discount_amount');
        });
    }
};
