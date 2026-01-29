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
        Schema::table('students', function (Blueprint $table) {
            $table->foreignUuid('department_id')->nullable()->after('department')->constrained('departments')->nullOnDelete();
        });

        // Migrate existing data
        $students = \Illuminate\Support\Facades\DB::table('students')->get();
        foreach ($students as $student) {
            if ($student->department) {
                $dept = \Illuminate\Support\Facades\DB::table('departments')->where('name', $student->department)->first();
                if ($dept) {
                    \Illuminate\Support\Facades\DB::table('students')
                        ->where('id', $student->id)
                        ->update(['department_id' => $dept->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
