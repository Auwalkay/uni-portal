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
        Schema::table('staff', function (Blueprint $table) {
            // General Employment Data
            $table->date('date_joined')->nullable()->after('is_academic');
            $table->string('highest_qualification')->nullable()->after('date_joined');
            
            // Personal Data
            $table->string('phone_number')->nullable()->after('highest_qualification');
            $table->string('gender')->nullable()->after('phone_number');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('marital_status')->nullable()->after('date_of_birth');
            $table->text('address')->nullable()->after('marital_status');
            $table->string('nationality')->nullable()->after('address');
            $table->string('state')->nullable()->after('nationality');
            $table->string('lga')->nullable()->after('state');
            
            // Academic Specific Data
            $table->string('specialization')->nullable()->after('lga');
            $table->text('research_interests')->nullable()->after('specialization');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn([
                'date_joined',
                'highest_qualification',
                'phone_number',
                'gender',
                'date_of_birth',
                'marital_status',
                'address',
                'nationality',
                'state',
                'lga',
                'specialization',
                'research_interests'
            ]);
        });
    }
};
