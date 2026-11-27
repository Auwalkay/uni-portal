<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programmes', function (Blueprint $table) {
            $table->decimal('duration', 4, 1)->default(4.0)->change();
        });

        DB::table('programmes')
            ->where('type', 'PGD')
            ->update(['duration' => 1.0]);

        DB::table('programmes')
            ->whereIn('type', ['MSC', 'MBA', 'MASTERS', 'MIM', 'MPHIL', 'PG_PROFESSIONAL'])
            ->update(['duration' => 1.5]);

        DB::table('programmes')
            ->where('type', 'PHD')
            ->update(['duration' => 3.0]);
    }

    public function down(): void
    {
        DB::table('programmes')
            ->whereIn('type', ['MSC', 'MBA', 'MASTERS', 'MIM', 'MPHIL', 'PG_PROFESSIONAL'])
            ->update(['duration' => 2]);

        Schema::table('programmes', function (Blueprint $table) {
            $table->integer('duration')->default(4)->change();
        });
    }
};
