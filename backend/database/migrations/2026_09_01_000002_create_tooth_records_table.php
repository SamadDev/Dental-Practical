<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-tooth dental chart. A missing row means the tooth is healthy —
        // only deviations from healthy are stored.
        Schema::create('tooth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->unsignedTinyInteger('tooth_number'); // 1–32, universal numbering
            $table->string('status');                    // cavity|filled|crown|root_canal|missing|implant|previous_visit
            $table->text('note')->nullable();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->timestamps();

            $table->unique(['patient_id', 'tooth_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_records');
    }
};
