<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Row-locked counters used to generate patient, appointment, receipt and doctor numbers. */
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->string('name', 40)->primary();
            $table->unsignedBigInteger('last_value')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
