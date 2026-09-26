<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Suggestion lists behind the patient form's autocomplete fields:
 * allergies, diseases and visit reasons. Values are shared clinic-wide —
 * adding one from the form persists it for every future patient.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_lists', function (Blueprint $table) {
            $table->id();
            $table->string('type');   // allergy | disease | visit_reason
            $table->string('name');
            $table->timestamps();

            $table->unique(['type', 'name']);
            $table->index('type');
        });

        // Starter suggestions — plain, standard entries a dental clinic
        // extends over time from the patient form itself.
        $now = now();
        $seed = [
            ['type' => 'allergy',      'name' => 'Penicillin'],
            ['type' => 'allergy',      'name' => 'Amoxicillin'],
            ['type' => 'allergy',      'name' => 'Latex'],
            ['type' => 'allergy',      'name' => 'Aspirin'],
            ['type' => 'allergy',      'name' => 'Iodine'],
            ['type' => 'disease',      'name' => 'Diabetes'],
            ['type' => 'disease',      'name' => 'Hypertension'],
            ['type' => 'disease',      'name' => 'Heart disease'],
            ['type' => 'disease',      'name' => 'Asthma'],
            ['type' => 'disease',      'name' => 'Kidney disease'],
            ['type' => 'visit_reason', 'name' => 'Check-up'],
            ['type' => 'visit_reason', 'name' => 'Tooth pain'],
            ['type' => 'visit_reason', 'name' => 'Cleaning'],
            ['type' => 'visit_reason', 'name' => 'Consultation'],
        ];

        foreach ($seed as $row) {
            DB::table('master_lists')->insertOrIgnore([...$row, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_lists');
    }
};
