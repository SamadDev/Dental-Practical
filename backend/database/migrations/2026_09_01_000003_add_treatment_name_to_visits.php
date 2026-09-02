<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Short treatment label shown as a chip on queue rows and the archive,
        // e.g. "Dental Implant" — treatment_notes stays the free-text detail.
        Schema::table('visits', function (Blueprint $table) {
            $table->string('treatment_name')->nullable()->after('visit_type');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('treatment_name');
        });
    }
};
