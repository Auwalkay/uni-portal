<?php

use App\Models\User;
use App\Models\Student;
use App\Models\Session;
use Spatie\Permission\Models\Permission;
use App\Models\StudentSession;

beforeEach(function () {
    // Setup roles/permissions
    Permission::firstOrCreate(['name' => 'access_student_portal']);
    
    $this->studentUser = User::factory()->create();
    $this->studentUser->assignRole('student');
    $this->studentUser->givePermissionTo('access_student_portal');

    // Create session, faculty, department, programme for student creation
    $this->session = Session::create([
        'name' => '2024/2025',
        'start_date' => now(),
        'end_date' => now()->addYear(),
        'is_current' => true,
    ]);

    $faculty = \App\Models\Faculty::create(['name' => 'Engineering']);
    $department = \App\Models\Department::create(['name' => 'Computer Engineering', 'code' => 'CPE', 'faculty_id' => $faculty->id]);
    $programme = \App\Models\Programme::create(['name' => 'Computer Engineering B.Eng', 'code' => 'CPE-BENG', 'department_id' => $department->id, 'duration' => 5]);

    $this->student = Student::create([
        'user_id' => $this->studentUser->id,
        'matriculation_number' => 'ENG2024001',
        'faculty_id' => $faculty->id,
        'department_id' => $department->id,
        'program_id' => $programme->id,
        'admitted_session_id' => $this->session->id,
        'current_level' => '100',
        'phone_number' => '08012345678',
        'address' => 'Test Address',
        'entry_mode' => 'UTME',
        // Start with null fields to test setup
        'gender' => null,
        'state_id' => null,
        'lga_id' => null,
        'jamb_registration_number' => null,
    ]);

    // Create student session record
    StudentSession::create([
        'student_id' => $this->student->id,
        'session_id' => $this->session->id,
        'level' => '100',
        'semester' => 'First Semester',
        'status' => 'active',
    ]);
});

test('incomplete profile student is redirected to profile edit page', function () {
    $this->actingAs($this->studentUser);

    // Attempt to visit dashboard
    $response = $this->get(route('student.dashboard'));

    // Should redirect to profile edit
    $response->assertRedirect(route('student.profile.edit'));
    $response->assertSessionHas('warning');
});

test('student can complete their profile and is no longer redirected', function () {
    $this->actingAs($this->studentUser);

    $state = \App\Models\State::create(['name' => 'Lagos']);
    $lga = \App\Models\Lga::create(['name' => 'Ikeja', 'state_id' => $state->id]);

    // Save profile details
    $response = $this->patch(route('student.profile.update'), [
        'phone_number' => '08012345678',
        'address' => '123 New Road',
        'next_of_kin_name' => 'Jane Kin',
        'next_of_kin_phone' => '08098765432',
        'gender' => 'male',
        'state_id' => $state->id,
        'lga_id' => $lga->id,
        'jamb_registration_number' => '202412345678AB',
        'passport_photograph' => \Illuminate\Http\UploadedFile::fake()->image('passport.jpg'),
        'indigene_letter' => \Illuminate\Http\UploadedFile::fake()->create('indigene.pdf', 500),
        'o_level_sittings' => [
            [
                'exam_type' => 'WAEC',
                'exam_year' => '2022',
                'exam_number' => '4123456789',
                'subjects' => [
                    ['subject' => 'Mathematics', 'grade' => 'A1'],
                    ['subject' => 'English Language', 'grade' => 'C6'],
                ],
                'scanned_copy' => \Illuminate\Http\UploadedFile::fake()->create('result.pdf', 500),
            ]
        ]
    ]);

    $response->assertRedirect(route('student.dashboard'));
    
    // Check fields in database
    $student = $this->student->fresh();
    expect($student->gender)->toBe('male');
    expect($student->state_id)->toBe($state->id);
    expect($student->lga_id)->toBe($lga->id);
    expect($student->jamb_registration_number)->toBe('202412345678AB');
    expect($student->passport_photo_path)->not->toBeNull();
    expect($student->indigene_letter_path)->not->toBeNull();
    expect($student->oLevelResults)->toHaveCount(1);

    // Try to visit dashboard again - should be allowed now
    $dashboardResponse = $this->get(route('student.dashboard'));
    $dashboardResponse->assertOk();
});

test('immutable fields cannot be edited once set', function () {
    $state = \App\Models\State::create(['name' => 'Lagos']);
    $lga = \App\Models\Lga::create(['name' => 'Ikeja', 'state_id' => $state->id]);
    
    // Set initial values in database
    $this->student->update([
        'gender' => 'female',
        'state_id' => $state->id,
        'lga_id' => $lga->id,
        'jamb_registration_number' => 'JAMB123456',
        'passport_photo_path' => 'profile-photos/test.jpg',
        'indigene_letter_path' => 'documents/indigene/test.pdf',
    ]);
    
    $this->student->oLevelResults()->create([
        'exam_type' => 'WAEC',
        'exam_year' => '2022',
        'exam_number' => '4123456789',
        'subjects' => [],
    ]);

    $this->actingAs($this->studentUser);

    $state2 = \App\Models\State::create(['name' => 'Abia']);
    $lga2 = \App\Models\Lga::create(['name' => 'Aba', 'state_id' => $state2->id]);

    // Submit patch request to try to change these immutable values
    $response = $this->patch(route('student.profile.update'), [
        'phone_number' => '08011111111',
        'address' => '456 Alternate Road',
        'next_of_kin_name' => 'New Kin',
        'next_of_kin_phone' => '08011111111',
        // Attempt changes
        'gender' => 'male',
        'state_id' => $state2->id,
        'lga_id' => $lga2->id,
        'jamb_registration_number' => 'NEWJAMB999',
    ]);

    $response->assertRedirect(route('student.profile.edit'));
    
    // Check fields in database - mutable fields should change, immutable should remain the same
    $student = $this->student->fresh();
    expect($student->phone_number)->toBe('08011111111'); // Mutable
    expect($student->address)->toBe('456 Alternate Road'); // Mutable
    
    expect($student->gender)->toBe('female'); // Immutable - unchanged
    expect($student->state_id)->toBe($state->id); // Immutable - unchanged
    expect($student->lga_id)->toBe($lga->id); // Immutable - unchanged
    expect($student->jamb_registration_number)->toBe('JAMB123456'); // Immutable - unchanged
});
