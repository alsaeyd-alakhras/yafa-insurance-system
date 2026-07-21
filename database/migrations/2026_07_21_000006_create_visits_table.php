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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('patient_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->foreignId('patient_dependent_id')->nullable()->constrained('dependents')->restrictOnDelete();
            $table->date('visit_date');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_before_discount', 10, 2)->nullable();
            $table->decimal('total_after_discount', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['patient_employee_id', 'visit_date']);
            $table->unique(['patient_dependent_id', 'visit_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
