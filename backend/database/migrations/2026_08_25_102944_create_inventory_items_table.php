<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('name');
            $table->string('sku')->unique(); // e.g., "IMPL-STRAUMANN-4.1"
            $table->string('category'); // implants, crowns, materials, medications, instruments, etc.
            $table->string('unit')->default('pcs'); // pcs, box, bottle, ml, g
            $table->integer('unit_cost'); // cost per unit in IQD
            $table->integer('sale_price')->nullable(); // optional selling price
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('reorder_level')->default(10);
            $table->integer('reorder_quantity')->default(50);
            $table->string('location')->nullable(); // shelf/bin location
            $table->date('expiry_date')->nullable();
            $table->boolean('track_expiry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index('quantity_on_hand');
            $table->index('expiry_date');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'waste', 'expired']);
            $table->integer('quantity'); // positive for in, negative for out
            $table->integer('unit_cost_at_time')->nullable();
            $table->foreignId('reference_id')->nullable(); // visit_id, expense_id, etc.
            $table->string('reference_type')->nullable(); // 'visit', 'expense', 'manual'
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_item_id', 'type']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
    }
};