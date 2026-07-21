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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('national_id', 9)->unique();
            $table->enum('gender', ['male', 'female']);
            $table->enum('marital_status', ['single', 'married', 'polygamous', 'widowed', 'divorced']);
            $table->foreignId('organization_unit_id')->constrained('organization_units')->restrictOnDelete();
            $table->enum('status', ['pending', 'active', 'inactive'])->default('active');
            $table->enum('source', ['survey', 'admin']);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
