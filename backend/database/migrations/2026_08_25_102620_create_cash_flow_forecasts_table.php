<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_flow_forecasts', function (Blueprint $table) {
            $table->id();
            $table->date('forecast_date');
            $table->enum('type', ['inflow', 'outflow']);
            $table->string('source'); // 'aqsat', 'visit', 'expense', 'manual'
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description');
            $table->integer('amount'); // whole IQD
            $table->enum('status', ['projected', 'confirmed', 'cancelled'])->default('projected');
            $table->timestamps();

            $table->index(['forecast_date', 'type']);
            $table->index(['source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_flow_forecasts');
    }
};