<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aqsat_contracts', function (Blueprint $table) {
            // Per-installment cash amount, flat whole IQD. Nullable for any
            // legacy rows that pre-date this column; new contracts require it.
            $table->unsignedBigInteger('installment_amount')->nullable()->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('aqsat_contracts', function (Blueprint $table) {
            $table->dropColumn('installment_amount');
        });
    }
};
