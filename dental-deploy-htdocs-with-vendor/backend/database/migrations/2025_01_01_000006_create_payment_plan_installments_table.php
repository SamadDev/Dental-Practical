<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_plan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained('payment_plans')->cascadeOnDelete();
            $table->unsignedInteger('installment_number');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('amount_paid')->default(0);
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->string('status')->default('pending'); // pending, partial, paid, overdue, waived
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plan_installments');
    }
};