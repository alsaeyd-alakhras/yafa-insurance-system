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
        Schema::create('visit_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->foreignId('medical_department_id')->constrained('medical_departments')->restrictOnDelete();
            $table->decimal('applied_discount_percentage', 5, 2);
            $table->decimal('amount_before_discount', 10, 2)->nullable();
            $table->decimal('amount_after_discount', 10, 2)->nullable();
            $table->timestamp('added_at')->useCurrent();
            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();

            $table->unique(['visit_id', 'medical_department_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_departments');
    }
};
