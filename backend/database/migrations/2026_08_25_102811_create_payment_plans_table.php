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
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // e.g., "Root Canal Treatment Plan"
            $table->integer('total_amount'); // whole IQD
            $table->integer('down_payment')->default(0);
            $table->integer('installment_amount');
            $table->integer('frequency_days')->default(30); // monthly by default
            $table->integer('installment_count');
            $table->date('start_date');
            $table->enum('status', ['active', 'completed', 'defaulted', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index('start_date');
        });

        Schema::create('payment_plan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_plan_id')->constrained()->cascadeOnDelete();
            $table->integer('installment_number');
            $table->date('due_date');
            $table->integer('amount');
            $table->integer('amount_paid')->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'waived'])->default('pending');
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payment_plan_id', 'installment_number']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_plan_installments');
        Schema::dropIfExists('payment_plans');
    }
};