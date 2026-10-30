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
        // 1. inventory_items indexes
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->index('sku', 'inv_items_sku_idx');
            $table->index('name', 'inv_items_name_idx');
            $table->index(['available_quantity', 'min_stock_level'], 'inv_items_stock_alert_idx');
            $table->index(['inventory_category_id', 'available_quantity'], 'inv_items_cat_avail_idx');
        });

        // 2. inventory_requisitions indexes
        Schema::table('inventory_requisitions', function (Blueprint $table) {
            $table->index('requisition_number', 'inv_req_number_idx');
            $table->index(['status', 'created_at'], 'inv_req_status_created_idx');
            $table->index('user_id', 'inv_req_user_idx');
            $table->index('department_id', 'inv_req_dept_idx');
        });

        // 3. inventory_stock_logs indexes
        Schema::table('inventory_stock_logs', function (Blueprint $table) {
            $table->index(['type', 'created_at'], 'inv_logs_type_created_idx');
            $table->index(['inventory_item_id', 'type'], 'inv_logs_item_type_idx');
        });

        // 4. inventory_categories indexes
        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->index('name', 'inv_cat_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex('inv_items_sku_idx');
            $table->dropIndex('inv_items_name_idx');
            $table->dropIndex('inv_items_stock_alert_idx');
            $table->dropIndex('inv_items_cat_avail_idx');
        });

        Schema::table('inventory_requisitions', function (Blueprint $table) {
            $table->dropIndex('inv_req_number_idx');
            $table->dropIndex('inv_req_status_created_idx');
            $table->dropIndex('inv_req_user_idx');
            $table->dropIndex('inv_req_dept_idx');
        });

        Schema::table('inventory_stock_logs', function (Blueprint $table) {
            $table->dropIndex('inv_logs_type_created_idx');
            $table->dropIndex('inv_logs_item_type_idx');
        });

        Schema::table('inventory_categories', function (Blueprint $table) {
            $table->dropIndex('inv_cat_name_idx');
        });
    }
};
