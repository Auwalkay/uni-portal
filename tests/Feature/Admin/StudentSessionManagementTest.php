<?php

use App\Models\User;
use App\Models\Student;
use App\Models\Session;
use App\Models\StudentSession;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Setup roles/permissions
    Permission::firstOrCreate(['name' => 'edit_students']);
    
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('edit_students');

    $this->studentUser = User::factory()->create();
    
    // Check if faculty and department are required
    $faculty = \App\Models\Faculty::create(['name' => 'Engineering']);
    $department = \App\Models\Department::create(['name' => 'Computer Engineering', 'code' => 'CPE', 'faculty_id' => $faculty->id]);
    $programme = \App\Models\Programme::create(['name' => 'Computer Engineering B.Eng', 'code' => 'CPE-BENG', 'department_id' => $department->id, 'duration' => 5]);
    
    $this->session1 = Session::create([
        'name' => '2024/2025',
        'start_date' => now(),
        'end_date' => now()->addYear(),
        'is_current' => true,
    ]);

    $this->session2 = Session::create([
        'name' => '2025/2026',
        'start_date' => now()->addYear(),
        'end_date' => now()->addYears(2),
        'is_current' => false,
    ]);

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'matriculation_number' => 'ENG2024001',
        'faculty_id' => $faculty->id,
        'department_id' => $department->id,
        'program_id' => $programme->id,
        'admitted_session_id' => $this->session1->id,
        'current_level' => '100',
        'gender' => 'male',
        'dob' => '2000-01-01',
        'phone_number' => '08012345678',
        'address' => 'Test Address',
        'entry_mode' => 'UTME',
    ]);
});

test('admin can view student sessions on show page', function () {
    $studentSession = StudentSession::create([
        'student_id' => $this->student->id,
        'session_id' => $this->session1->id,
        'level' => '100',
        'semester' => 'First Semester',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin);

    $response = $this->get(route('admin.students.show', $this->student->id));
    
    $response->assertOk();
    // Verify sessions relation was loaded
    $studentData = $response->original->getData()['page']['props']['student'];
    expect($studentData['sessions'])->toHaveCount(1);
    expect($studentData['sessions'][0]['id'])->toBe($studentSession->id);
});

test('admin can add student session history', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('admin.students.sessions.store', $this->student->id), [
        'session_id' => $this->session1->id,
        'level' => '100',
        'semester' => 'First Semester',
        'status' => 'active',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_sessions', [
        'student_id' => $this->student->id,
        'session_id' => $this->session1->id,
        'level' => '100',
        'semester' => 'First Semester',
        'status' => 'active',
    ]);
});

test('admin cannot add duplicate student session history', function () {
    StudentSession::create([
        'student_id' => $this->student->id,
        'session_id' => $this->session1->id,
        'level' => '100',
        'semester' => 'First Semester',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin);

    $response = $this->post(route('admin.students.sessions.store', $this->student->id), [
        'session_id' => $this->session1->id,
        'level' => '200',
        'semester' => 'Second Semester',
        'status' => 'active',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect(StudentSession::where('student_id', $this->student->id)->count())->toBe(1);
});

test('admin can update student session history', function () {
    $studentSession = StudentSession::create([
        'student_id' => $this->student->id,
        'session_id' => $this->session1->id,
        'level' => '100',
        'semester' => 'First Semester',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin);

    $response = $this->put(route('admin.students.sessions.update', [$this->student->id, $studentSession->id]), [
        'session_id' => $this->session2->id,
        'level' => '200',
        'semester' => 'Second Semester',
        'status' => 'suspended',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_sessions', [
        'id' => $studentSession->id,
        'session_id' => $this->session2->id,
        'level' => '200',
        'semester' => 'Second Semester',
        'status' => 'suspended',
    ]);
});

test('only one active session can exist at a time', function () {
    // Create an initial active session
    $initialSession = StudentSession::create([
        'student_id' => $this->student->id,
        'session_id' => $this->session1->id,
        'level' => '100',
        'semester' => 'First Semester',
        'status' => 'active',
    ]);

    $this->actingAs($this->admin);

    // Create a new active session
    $response = $this->post(route('admin.students.sessions.store', $this->student->id), [
        'session_id' => $this->session2->id,
        'level' => '200',
        'semester' => 'First Semester',
        'status' => 'active',
    ]);

    $response->assertRedirect();
    
    // Verify the initial session is now completed
    $this->assertDatabaseHas('student_sessions', [
        'id' => $initialSession->id,
        'status' => 'completed',
    ]);
    
    // Verify the new session is active
    $this->assertDatabaseHas('student_sessions', [
        'student_id' => $this->student->id,
        'session_id' => $this->session2->id,
        'status' => 'active',
    ]);
});
