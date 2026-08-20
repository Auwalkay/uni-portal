<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fetch all courses
        $courses = DB::table('courses')->get();

        $processed = [];

        foreach ($courses as $course) {
            $normalizedCode = $this->normalizeCourseCode($course->code);

            if (isset($processed[$normalizedCode])) {
                $masterId = $processed[$normalizedCode];
                $duplicateId = $course->id;

                // Merge duplicate references into master and delete the duplicate
                $this->mergeCourses($masterId, $duplicateId);
            } else {
                // Update this course to have the normalized code
                DB::table('courses')->where('id', $course->id)->update([
                    'code' => $normalizedCode
                ]);
                $processed[$normalizedCode] = $course->id;
            }
        }
    }

    /**
     * Normalize Course Code to standard format with hyphens supported: "PREFIX NUMBER" (e.g. MIU-IRS 101)
     */
    private function normalizeCourseCode(string $code): string
    {
        $cleaned = str_replace(' ', '', $code);
        $cleaned = strtoupper($cleaned);

        if (preg_match('/^([A-Z\-]+)(\d+.*)$/', $cleaned, $matches)) {
            return $matches[1] . ' ' . $matches[2];
        }

        return $cleaned;
    }

    /**
     * Merge duplicate course references and delete duplicate.
     */
    private function mergeCourses(string $masterId, string $duplicateId): void
    {
        // Merge course_programme (pivot overrides)
        $configs = DB::table('course_programme')->where('course_id', $duplicateId)->get();
        foreach ($configs as $config) {
            $exists = DB::table('course_programme')
                ->where('course_id', $masterId)
                ->where('programme_id', $config->programme_id)
                ->exists();

            if ($exists) {
                DB::table('course_programme')->where('id', $config->id)->delete();
            } else {
                DB::table('course_programme')->where('id', $config->id)->update([
                    'course_id' => $masterId
                ]);
            }
        }

        // Merge course_registrations
        $registrations = DB::table('course_registrations')->where('course_id', $duplicateId)->get();
        foreach ($registrations as $reg) {
            $exists = DB::table('course_registrations')
                ->where('course_id', $masterId)
                ->where('student_id', $reg->student_id)
                ->where('session_id', $reg->session_id)
                ->where('semester_id', $reg->semester_id)
                ->first();

            if ($exists) {
                // If duplicate has a score but master doesn't, copy it over
                if ($reg->score !== null && $exists->score === null) {
                    DB::table('course_registrations')->where('id', $exists->id)->update([
                        'score' => $reg->score,
                        'grade' => $reg->grade,
                    ]);
                }
                DB::table('course_registrations')->where('id', $reg->id)->delete();
            } else {
                DB::table('course_registrations')->where('id', $reg->id)->update([
                    'course_id' => $masterId
                ]);
            }
        }

        // Merge course_allocations
        $allocations = DB::table('course_allocations')->where('course_id', $duplicateId)->get();
        foreach ($allocations as $alloc) {
            $exists = DB::table('course_allocations')
                ->where('course_id', $masterId)
                ->where('staff_id', $alloc->staff_id)
                ->where('session_id', $alloc->session_id)
                ->exists();

            if ($exists) {
                DB::table('course_allocations')->where('id', $alloc->id)->delete();
            } else {
                DB::table('course_allocations')->where('id', $alloc->id)->update([
                    'course_id' => $masterId
                ]);
            }
        }

        // Merge timetables
        $timetables = DB::table('timetables')->where('course_id', $duplicateId)->get();
        foreach ($timetables as $timetable) {
            $exists = DB::table('timetables')
                ->where('course_id', $masterId)
                ->where('session_id', $timetable->session_id)
                ->where('day', $timetable->day)
                ->where('start_time', $timetable->start_time)
                ->exists();

            if ($exists) {
                DB::table('timetables')->where('id', $timetable->id)->delete();
            } else {
                DB::table('timetables')->where('id', $timetable->id)->update([
                    'course_id' => $masterId
                ]);
            }
        }

        // Finally, delete the duplicate course record
        DB::table('courses')->where('id', $duplicateId)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible
    }
};
