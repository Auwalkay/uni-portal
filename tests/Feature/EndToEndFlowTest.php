<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Invoice;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed necessary data
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\AcademicRecordsSeeder::class);
        // Note: AcademicRecordsSeeder might depend on Depts existing.
        // Let's manually create Faculty/Dept/Programme if needed or use AcademicSeeder if it does that.
        // Checking AcademicRecordsSeeder: it assumes Dept 'CSC' exists.

        $faculty = Faculty::create(['name' => 'Sciences', 'code' => 'SCI']);
        $dept = Department::create(['name' => 'Computer Science', 'code' => 'CSC', 'faculty_id' => $faculty->id]);
        $prog = Programme::create(['name' => 'Computer Science', 'type' => 'UG', 'department_id' => $dept->id]);

        // Re-run AcademicRecordsSeeder to ensure courses link to this dept
        $this->seed(\Database\Seeders\AcademicRecordsSeeder::class);
    }

    public function test_full_application_to_result_flow()
    {
        // 1. Applicant Registration & Application
        $user = User::create([
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('applicant');

        $this->actingAs($user);

        // Submit Application
        $response = $this->post(route('applicant.apply.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '2000-01-01',
            'gender' => 'Male',
            'programme_id' => Programme::first()->id,
            'mode' => 'UTME',
            'phone' => '08012345678',
            'nin' => '12345678901',
            'address' => '123 Street',
            'state_of_origin' => 'Lagos',
            'lga_of_origin' => 'Ikeja',
            'jamb_number' => '2025JAMB001',
            'jamb_score' => 250,
        ]);

        // Check if Applicant record created
        $this->assertDatabaseHas('applicants', ['user_id' => $user->id]);
        $applicant = Applicant::where('user_id', $user->id)->first();

        // Simulating Status Update to Submitted (usually done via form wizard steps)
        $applicant->update(['status' => 'submitted']);

        // 2. Admin Admission
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@portal.com', 'password' => Hash::make('password')]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        // Update Status to Admitted
        $response = $this->put(route('admin.admissions.update', $applicant->id), [
            'status' => 'admitted'
        ]);

        // Check Invoice Generation
        $this->assertDatabaseHas('invoices', [
            'user_id' => $user->id,
            'type' => 'acceptance_fee',
            'amount' => 50000.00
        ]);

        // 3. Payment & Matriculation
        $this->actingAs($user);
        $invoice = Invoice::where('user_id', $user->id)->first();

        // Mock Paystack Service
        $this->mock(PaystackService::class, function ($mock) {
            $mock->shouldReceive('initializeTransaction')->andReturn(['authorization_url' => 'https://paystack.com/pay/xxx']);
            $mock->shouldReceive('verifyTransaction')->andReturn([
                'status' => 'success',
                'channel' => 'card',
                'amount' => 5000000 // kobo
            ]);
        });

        // Init Payment
        $this->post(route('student.payments.pay', $invoice->id));

        // Verify Callback (Manually triggering what PaymentController does)
        // We need the payment record created in `pay` method, but `pay` redirects.
        // In test, creating payment manually or relying on controller logic:
        // The controller creates payment before redirect.

        // Let's create the payment record manually to simulate the state before callback
        $payment = \App\Models\Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'gateway_reference' => 'TEST-REF',
            'amount' => $invoice->amount,
            'status' => 'pending'
        ]);

        $response = $this->get(route('student.payments.callback', ['reference' => 'TEST-REF']));
        $response->assertRedirect(route('student.payments.index'));

        // Assert Invoice Paid
        $this->assertEquals('paid', $invoice->fresh()->status);

        // Assert Student Record Created (Matriculation)
        $this->assertDatabaseHas('students', ['user_id' => $user->id]);
        $student = Student::where('user_id', $user->id)->first();
        $this->assertNotNull($student->matriculation_number);

        // 4. Course Registration
        $session = Session::where('is_current', true)->first();
        $semester = Semester::where('is_current', true)->first();
        $courses = Course::take(3)->get();

        $response = $this->post(route('student.courses.store'), [
            'courses' => $courses->pluck('id')->toArray()
        ]);

        $this->assertDatabaseCount('course_registrations', 3);

        // 5. Result Entry (Admin)
        $this->actingAs($admin);
        $course = $courses->first();
        $reg = CourseRegistration::where('course_id', $course->id)->where('student_id', $student->id)->first();

        $response = $this->post(route('admin.results.update', $course->id), [
            'scores' => [
                [
                    'id' => $reg->id,
                    'ca_score' => 30,
                    'exam_score' => 45
                ]
            ]
        ]);

        $reg->refresh();
        $this->assertEquals(75, $reg->score);
        $this->assertEquals('A', $reg->grade);
        $this->assertEquals(5.0, $reg->grade_point);

        // 6. Student Result View
        $this->actingAs($user);
        $response = $this->get(route('student.results.index'));
        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/Results/Index')
                ->has('results')
                ->has('cgpa')
        );
    }
}
