<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('aqsat_contract_id')->nullable();

            $table->string('queue_status')->default('pending'); // pending | active | completed
            $table->string('visit_type')->default('walk_in');   // walk_in | phone | whatsapp

            $table->text('treatment_notes')->nullable();
            $table->string('xray_path')->nullable();

            // All money in whole IQD — strictly bigInteger, never decimal.
            $table->unsignedBigInteger('total_cost')->default(0);
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->unsignedBigInteger('short_term_debt')->default(0);

            $table->timestamps();

            $table->foreign('patient_id')
                ->references('id')->on('patients')
                ->onDelete('cascade');

            $table->foreign('aqsat_contract_id')
                ->references('id')->on('aqsat_contracts')
                ->onDelete('set null');

            $table->index('queue_status');
            $table->index('visit_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
