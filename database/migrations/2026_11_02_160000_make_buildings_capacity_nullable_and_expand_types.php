<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('building_type', 50)->default('classroom')->change();
            $table->integer('capacity')->nullable()->default(null)->change();
        });

        // Backfill any buildings with empty or null codes
        $buildings = DB::table('buildings')->whereNull('code')->orWhere('code', '')->get();
        foreach ($buildings as $b) {
            $baseSlug = mb_strtoupper(Str::slug($b->name ?: 'BUILDING'));
            $candidate = $baseSlug;
            $counter = 2;
            while (DB::table('buildings')->where('code', $candidate)->where('id', '!=', $b->id)->exists()) {
                $candidate = "{$baseSlug}-{$counter}";
                $counter++;
            }
            DB::table('buildings')->where('id', $b->id)->update(['code' => $candidate]);
        }
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->integer('capacity')->default(100)->change();
        });
    }
};

