<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('total_amount');
            $table->unsignedInteger('down_payment')->default(0);
            $table->unsignedInteger('installment_amount');
            $table->unsignedInteger('installment_count');
            $table->unsignedInteger('frequency_days')->default(30);
            $table->date('start_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};