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
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['patient_employee_id']);
            $table->dropForeign(['patient_dependent_id']);
            $table->dropUnique(['patient_employee_id', 'visit_date']);
            $table->dropUnique(['patient_dependent_id', 'visit_date']);
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('clinic_id')->nullable()->after('patient_dependent_id')->constrained('clinics')->restrictOnDelete();

            $table->foreign('patient_employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->foreign('patient_dependent_id')->references('id')->on('dependents')->restrictOnDelete();

            $table->unique(['patient_employee_id', 'visit_date', 'clinic_id']);
            $table->unique(['patient_dependent_id', 'visit_date', 'clinic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['patient_employee_id']);
            $table->dropForeign(['patient_dependent_id']);
            $table->dropUnique(['patient_employee_id', 'visit_date', 'clinic_id']);
            $table->dropUnique(['patient_dependent_id', 'visit_date', 'clinic_id']);
            $table->dropConstrainedForeignId('clinic_id');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->foreign('patient_employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->foreign('patient_dependent_id')->references('id')->on('dependents')->restrictOnDelete();

            $table->unique(['patient_employee_id', 'visit_date']);
            $table->unique(['patient_dependent_id', 'visit_date']);
        });
    }
};
