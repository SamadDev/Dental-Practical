<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Medicines the doctor prescribes during a visit. Kept as its own table
        // (rather than a text blob on the patient) so the record stays auditable:
        // who prescribed what, when, and against which visit.
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('medication');
            $table->string('dose');                 // free text: "500mg", "1 tablet"
            $table->string('frequency');            // od|bid|tid|qid|prn
            $table->string('duration')->nullable(); // free text: "5 days"
            $table->string('instructions')->nullable(); // before_meal|after_meal|with_meal
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('patient_id');
            $table->index('doctor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
