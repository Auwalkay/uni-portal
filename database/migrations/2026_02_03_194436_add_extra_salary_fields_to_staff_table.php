<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->decimal('allowances', 12, 2)->default(0)->after('basic_salary');
            $table->decimal('deductions', 12, 2)->default(0)->after('allowances');
            $table->decimal('bonuses', 12, 2)->default(0)->after('deductions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['allowances', 'deductions', 'bonuses']);
        });
    }
};
