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
            $table->foreignId('radiology_exam_id')
                ->nullable()
                ->after('medical_department_id')
                ->constrained('radiology_exams')
                ->restrictOnDelete();
            $table->decimal('applied_price', 10, 2)->nullable()->after('radiology_exam_id');
            $table->decimal('applied_discount_amount', 10, 2)->nullable()->after('applied_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_departments', function (Blueprint $table) {
            $table->dropForeign(['radiology_exam_id']);
            $table->dropColumn(['radiology_exam_id', 'applied_price', 'applied_discount_amount']);
        });
    }
};
