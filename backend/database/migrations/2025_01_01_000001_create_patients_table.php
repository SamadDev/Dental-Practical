<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('appointment_date')->nullable();
            $table->unsignedInteger('age')->nullable();
            $table->boolean('is_smoker')->default(false);
            $table->text('medical_notes')->nullable();

            $table->timestamps();

            $table->index('name');
            $table->index('phone');
            $table->index('is_smoker');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
