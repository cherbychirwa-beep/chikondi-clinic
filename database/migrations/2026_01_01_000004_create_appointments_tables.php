<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number', 20)->unique();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->enum('status', ['scheduled', 'checked_in', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            // "doctor|date|time" while the appointment occupies its slot; NULL once cancelled.
            // The UNIQUE index guarantees no two live appointments share a slot (race-condition safe),
            // while cancelled rows (NULL) free the slot for re-booking.
            $table->string('slot_key', 60)->nullable()->unique();
            $table->foreignId('booked_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['appointment_date', 'doctor_id']);
            $table->index('status');
        });

        Schema::create('appointment_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained('appointments')->restrictOnDelete();
            $table->text('reason');
            $table->dateTime('cancelled_at');
            $table->foreignId('cancelled_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_cancellations');
        Schema::dropIfExists('appointments');
    }
};
