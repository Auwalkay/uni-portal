<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->uuid('pending_promotion_session_id')->nullable();
            
            $table->foreign('pending_promotion_session_id')
                ->references('id')
                ->on('academic_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['pending_promotion_session_id']);
            $table->dropColumn('pending_promotion_session_id');
        });
    }
};
