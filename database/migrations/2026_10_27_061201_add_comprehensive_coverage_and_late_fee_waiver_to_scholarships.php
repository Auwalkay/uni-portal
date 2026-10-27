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
        Schema::table('scholarships', function (Blueprint $table) {
            $table->boolean('covers_all_expenses')->default(false)->after('covers_hostel_fees');
            $table->boolean('waives_late_fees')->default(false)->after('covers_all_expenses');
            $table->json('excluded_fee_type_ids')->nullable()->after('waives_late_fees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table) {
            $table->dropColumn(['covers_all_expenses', 'waives_late_fees', 'excluded_fee_type_ids']);
        });
    }
};
