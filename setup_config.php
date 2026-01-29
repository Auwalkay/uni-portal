<?php
$student = App\Models\Student::where('user_id', \App\Models\User::where('email', 'student@portal.com')->first()->id)->first();
if ($student) {
    if ($student->program) {
        $program = App\Models\Programme::where('name', $student->program)->first();
        if ($program) {
            $program->update(['max_credit_units' => 30]);
            echo "Updated max units for {$program->name} to 30.\n";

            $course = App\Models\Course::first();
            if ($course) {
                // Remove existing override if any
                DB::table('course_programme')->where('programme_id', $program->id)->where('course_id', $course->id)->delete();

                DB::table('course_programme')->insert([
                    'id' => Str::uuid(),
                    'course_id' => $course->id,
                    'programme_id' => $program->id,
                    'is_compulsory' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                echo "Set course {$course->code} as Compulsory for {$program->name}.\n";
            }
        } else {
            echo "Program not found.\n";
        }
    } else {
        echo "Student has no program string.\n";
    }
} else {
    echo "Student not found.\n";
}
