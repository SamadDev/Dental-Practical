<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `users.role` was created as an enum of four roles while `User::ROLES`, the
 * permissions seeder and the admin UI already know `lab` — so creating a user
 * with the laboratory role (or switching an existing one) failed with an
 * integrity-constraint violation and returned a 500.
 *
 * The list must stay identical to `App\Models\User::ROLES`; the alignment is
 * pinned by Tests\Feature\RoleSchemaAlignmentTest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'doctor', 'receptionist', 'lab', 'hygienist'])
                ->default('receptionist')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'doctor', 'receptionist', 'hygienist'])
                ->default('receptionist')
                ->change();
        });
    }
};
