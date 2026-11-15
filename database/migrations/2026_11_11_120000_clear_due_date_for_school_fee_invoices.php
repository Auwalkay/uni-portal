<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('invoices')
            ->where('type', 'school_fee')
            ->update(['due_date' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nullable column; no rollback needed
    }
};

