<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which surfaces of a tooth carry the cavity/filling (M/O/D/B/L/F).
 * The dental chart has sent and rendered surfaces all along — the column
 * was simply never added, so the selection silently vanished on reload.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tooth_records', function (Blueprint $table) {
            if (! Schema::hasColumn('tooth_records', 'surfaces')) {
                $table->json('surfaces')->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tooth_records', function (Blueprint $table) {
            $table->dropColumn('surfaces');
        });
    }
};
