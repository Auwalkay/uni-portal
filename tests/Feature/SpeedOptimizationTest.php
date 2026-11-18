<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureStudentProfileCompleted;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeeType;
use App\Models\Programme;
use App\Models\Student;
use App\Models\User;
use App\Services\AcademicCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SpeedOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_inertia_requests_caches_student_auth_context(): void
    {
        $user = User::factory()->create();
        $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'faculty_id' => $faculty->id,
            'is_academic' => true,
            'is_active' => true,
        ]);
        $programme = Programme::create([
            'name' => 'B.Sc Computer Science',
            'department_id' => $department->id,
            'type' => 'UG',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'is_active' => true,
        ]);

        Student::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'program_id' => $programme->id,
            'matriculation_number' => 'MIU260001',
            'current_level' => 100,
            'status' => 'active',
        ]);

        $middleware = new HandleInertiaRequests();
        $request = Request::create('/student/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session.store'));

        // First call populates cache
        $firstShare = $middleware->share($request);
        $this->assertEquals('MIU260001', $firstShare['auth']['user']['student']['matriculation_number']);
        $this->assertTrue(Cache::has("user_auth_student_{$user->id}"));

        // Second call should hit cache without querying students table
        DB::enableQueryLog();
        $secondShare = $middleware->share($request);
        $queries = collect(DB::getQueryLog())->pluck('query')->implode(' ');
        DB::disableQueryLog();

        $this->assertEquals('MIU260001', $secondShare['auth']['user']['student']['matriculation_number']);
        $this->assertStringNotContainsString('from "students"', $queries);
        $ziggyResolved = ($secondShare['ziggy'])();
        $this->assertArrayNotHasKey('routes', $ziggyResolved);
    }

    public function test_ensure_student_profile_completed_caches_completion_flag(): void
    {
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'MIU260002',
            'current_level' => 700,
            'gender' => 'male',
            'entry_mode' => 'postgraduate',
            'status' => 'active',
        ]);

        $middleware = new EnsureStudentProfileCompleted();
        $request = Request::create('/student/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, fn () => response('OK'));
        $this->assertEquals('OK', $response->getContent());
        $this->assertTrue(Cache::get("student_profile_completed_{$student->id}"));
    }

    public function test_academic_cache_service_caches_fee_types(): void
    {
        FeeType::create(['name' => 'Tuition Fee', 'code' => 'TUITION', 'is_active' => true]);

        $feeTypes = AcademicCacheService::getFeeTypes();
        $this->assertCount(1, $feeTypes);
        $this->assertTrue(Cache::has('all_fee_types_list'));

        AcademicCacheService::clearAll();
        $this->assertFalse(Cache::has('all_fee_types_list'));
    }

    public function test_student_auto_syncs_academic_stream_and_stream_sessions_are_isolated(): void
    {
        $ugUser = User::factory()->create();
        $pgUser = User::factory()->create();

        $faculty = Faculty::create(['name' => 'Science', 'code' => 'SCI', 'is_active' => true]);
        $department = Department::create([
            'name' => 'Computer Science',
            'code' => 'CSC',
            'faculty_id' => $faculty->id,
            'is_academic' => true,
            'is_active' => true,
        ]);
        $pgProgramme = Programme::create([
            'name' => 'M.Sc Computer Science',
            'department_id' => $department->id,
            'type' => 'MSC',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'is_active' => true,
        ]);

        $ugStudent = Student::create([
            'user_id' => $ugUser->id,
            'department_id' => $department->id,
            'matriculation_number' => 'MIU260010',
            'current_level' => 100,
            'status' => 'active',
        ]);

        $pgStudent = Student::create([
            'user_id' => $pgUser->id,
            'department_id' => $department->id,
            'program_id' => $pgProgramme->id,
            'matriculation_number' => 'MIUPG260010',
            'current_level' => 700,
            'status' => 'active',
        ]);

        $this->assertEquals('UG', $ugStudent->academic_stream);
        $this->assertEquals('PG', $pgStudent->academic_stream);

        $ugSession = \App\Models\Session::create([
            'name' => '2025/2026',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
        ]);
        $pgSession = \App\Models\Session::create([
            'name' => '2025/2026',
            'academic_stream' => 'PG',
            'calendar_type' => 'semester',
            'start_date' => '2025-10-01',
            'end_date' => '2026-08-31',
            'is_current' => true,
        ]);

        AcademicCacheService::clearAll();

        $this->assertEquals($ugSession->id, AcademicCacheService::getCurrentSessionForStream('UG')->id);
        $this->assertEquals($pgSession->id, AcademicCacheService::getCurrentSessionForStream('PG')->id);
        $this->assertCount(1, AcademicCacheService::getSessionsForStream('UG'));
        $this->assertCount(1, AcademicCacheService::getSessionsForStream('PG'));
    }
}
