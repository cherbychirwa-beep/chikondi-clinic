<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('generic_name', 150)->nullable();
            $table->string('strength', 50)->nullable();
            $table->string('dosage_form', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['name', 'strength']);
        });

        Schema::create('fee_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2);
            $table->date('effective_from');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index('effective_from');
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained('appointments')->restrictOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->date('consultation_date');
            $table->text('diagnosis');
            $table->text('observations')->nullable();
            $table->text('notes')->nullable();
            // Fee snapshot at the time of consultation: later fee changes never alter history.
            $table->decimal('fee_charged', 12, 2);
            $table->timestamps();
            $table->index('consultation_date');
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->restrictOnDelete();
            $table->foreignId('medicine_id')->constrained('medicines')->restrictOnDelete();
            $table->string('dosage', 100);
            $table->string('frequency', 100);
            $table->string('duration', 100);
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 20)->unique();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('consultation_id')->constrained('consultations')->restrictOnDelete();
            $table->decimal('consultation_fee', 12, 2);   // snapshot
            $table->decimal('amount_paid', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->enum('payment_method', ['cash', 'mobile_money', 'bank_transfer', 'other']);
            $table->enum('payment_status', ['partial', 'paid']);
            $table->dateTime('paid_at');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            // One-time form token: a re-submitted form can never create a second payment.
            $table->string('submission_token', 64)->nullable()->unique();
            $table->timestamps();
            $table->index('paid_at');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('fee_settings');
        Schema::dropIfExists('medicines');
    }
};
