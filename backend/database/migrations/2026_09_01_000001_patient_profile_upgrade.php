<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Unambiguous alphabet — no 0/O, 1/I/L confusion on printed charts. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('age');        // male | female
            $table->string('patient_code')->nullable()->after('name'); // public short code
        });

        // Backfill a unique code for every existing patient. The unique index
        // is created afterwards (plain ADD COLUMN keeps SQLite happy).
        $taken = DB::table('patients')->whereNotNull('patient_code')->pluck('patient_code')->all();
        DB::table('patients')->whereNull('patient_code')->select('id')
            ->chunkById(100, function ($rows) use (&$taken) {
                foreach ($rows as $row) {
                    $code       = $this->uniqueCode($taken);
                    $taken[]    = $code;
                    DB::table('patients')->where('id', $row->id)->update(['patient_code' => $code]);
                }
            });

        Schema::table('patients', function (Blueprint $table) {
            $table->unique('patient_code', 'patients_patient_code_unique');
        });

        // Structured medical alerts — replaces guessing from free-text notes.
        Schema::create('patient_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('type');                        // allergy | condition
            $table->string('name');                        // e.g. Amoxicillin, Diabetes
            $table->string('severity')->default('mild');   // mild | moderate | severe
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_conditions');

        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique('patients_patient_code_unique');
            $table->dropColumn(['gender', 'patient_code']);
        });
    }

    private function uniqueCode(array $taken): string
    {
        do {
            $code = 'PT';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (in_array($code, $taken, true));

        return $code;
    }
};