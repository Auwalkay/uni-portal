<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_requisitions', function (Blueprint $table) {
            // Modify user_id, department_id, approved_by to char(36) / uuid
            DB::statement("ALTER TABLE inventory_requisitions MODIFY user_id CHAR(36) NOT NULL");
            DB::statement("ALTER TABLE inventory_requisitions MODIFY department_id CHAR(36) NULL");
            DB::statement("ALTER TABLE inventory_requisitions MODIFY approved_by CHAR(36) NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_requisitions', function (Blueprint $table) {
            DB::statement("ALTER TABLE inventory_requisitions MODIFY user_id BIGINT UNSIGNED NOT NULL");
            DB::statement("ALTER TABLE inventory_requisitions MODIFY department_id BIGINT UNSIGNED NULL");
            DB::statement("ALTER TABLE inventory_requisitions MODIFY approved_by BIGINT UNSIGNED NULL");
        });
    }
};
