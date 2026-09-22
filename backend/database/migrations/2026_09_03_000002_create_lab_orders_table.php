<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Work sent out of the clinic. Two kinds live here side by side because
        // they follow the exact same pending -> in_lab -> ready -> delivered
        // hand-off and the same printable request sheet:
        //   - prosthetic : crowns/bridges/dentures made by an outside dental lab
        //   - diagnostic : blood tests, X-rays, cultures the lab performs
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();  // LAB-000123, shown on the printed sheet

            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete(); // requesting doctor
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('category')->default('prosthetic'); // prosthetic|diagnostic
            $table->string('lab_type');                        // crown|bridge|... or blood_test|xray|...
            $table->string('test_name')->nullable();           // free text, e.g. "CBC + ESR"
            $table->string('tooth_number')->nullable();
            $table->string('material')->nullable();
            $table->string('shade')->nullable();
            $table->string('assigned_lab')->nullable();        // outside lab / department name

            $table->date('due_date')->nullable();
            $table->unsignedBigInteger('cost')->default(0);    // IQD, integer only
            $table->text('notes')->nullable();                 // doctor's instructions

            $table->string('status')->default('pending');      // pending|in_lab|ready|delivered|cancelled
            $table->text('result_notes')->nullable();          // lab writes the findings here
            $table->string('result_file_path')->nullable();    // optional scanned report

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_orders');
    }
};
