<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aqsat_contracts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('patient_id');
            $table->string('treatment_name');
            // IQD whole-number money. bigInteger guarantees no decimals.
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('remaining_balance');
            $table->string('status')->default('active'); // active | completed | cancelled
            $table->timestamps();

            $table->foreign('patient_id')
                ->references('id')->on('patients')
                ->onDelete('cascade');

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aqsat_contracts');
    }
};
