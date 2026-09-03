<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'unit_of_measure')) {
                $table->string('unit_of_measure')->default('pieces')->after('condition');
            }
            if (!Schema::hasColumn('inventory_items', 'min_stock_level')) {
                $table->integer('min_stock_level')->default(5)->after('unit_of_measure');
            }
            if (!Schema::hasColumn('inventory_items', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->nullable()->after('min_stock_level');
            }
            if (!Schema::hasColumn('inventory_items', 'item_type')) {
                $table->enum('item_type', ['consumable', 'reusable'])->default('consumable')->after('unit_cost');
            }
            if (!Schema::hasColumn('inventory_items', 'supplier_name')) {
                $table->string('supplier_name')->nullable()->after('item_type');
            }
            if (!Schema::hasColumn('inventory_items', 'location')) {
                $table->string('location')->nullable()->after('supplier_name');
            }
            if (!Schema::hasColumn('inventory_items', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (!Schema::hasTable('inventory_requisitions')) {
            Schema::create('inventory_requisitions', function (Blueprint $table) {
                $table->id();
                $table->string('requisition_number')->unique();
                $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignUuid('department_id')->nullable()->constrained('departments')->onDelete('set null');
                $table->enum('status', ['pending', 'approved', 'issued', 'rejected'])->default('pending');
                $table->text('notes')->nullable();
                $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('issued_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_requisition_items')) {
            Schema::create('inventory_requisition_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_requisition_id')->constrained('inventory_requisitions')->onDelete('cascade');
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
                $table->integer('requested_quantity');
                $table->integer('approved_quantity')->default(0);
                $table->string('unit_of_measure')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_stock_logs')) {
            Schema::create('inventory_stock_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
                $table->enum('type', ['restock', 'issue', 'return', 'adjustment', 'damage']);
                $table->integer('quantity');
                $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_logs');
        Schema::dropIfExists('inventory_requisition_items');
        Schema::dropIfExists('inventory_requisitions');

        Schema::table('inventory_items', function (Blueprint $table) {
            $cols = array_filter(['unit_of_measure', 'min_stock_level', 'unit_cost', 'item_type', 'supplier_name', 'location'], fn($col) => Schema::hasColumn('inventory_items', $col));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
