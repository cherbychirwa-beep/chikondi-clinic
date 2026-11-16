<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('patient_number', 20)->unique();
            $table->string('full_name', 150);
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('address', 255);
            $table->string('phone', 20);
            $table->string('next_of_kin_name', 150);
            $table->string('next_of_kin_phone', 20);
            $table->foreignId('primary_doctor_id')->nullable()->constrained('doctors')->restrictOnDelete();
            $table->date('registered_on');
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // Duplicate-registration guard (same person, same DOB, same phone).
            $table->unique(['full_name', 'date_of_birth', 'phone'], 'patients_identity_unique');
            $table->index('full_name');
            $table->index('date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
