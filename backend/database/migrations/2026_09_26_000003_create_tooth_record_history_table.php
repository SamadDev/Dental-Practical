<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only log of tooth chart changes — powers the "treatment history"
 * panel in the dental chart. One row per meaningful state change; healthy
 * teeth have no current record, but the history keeps what happened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tooth_record_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->unsignedTinyInteger('tooth_number'); // 1–32
            $table->string('status');
            $table->text('note')->nullable();
            $table->json('surfaces')->nullable();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'tooth_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_record_history');
    }
};
