<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Scholarship;
use App\Models\FeeType;
use App\Models\FeeConfiguration;
use App\Models\Session;
use App\Models\SystemSetting;
use App\Models\Invoice;
use App\Services\Finance\FeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComprehensiveScholarshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_create_comprehensive_scholarship_with_exclusions()
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@mewar.edu.ng',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin');

        $drugTestType = FeeType::create(['name' => 'Drug Test', 'slug' => 'drug-test']);
        $acceptanceType = FeeType::create(['name' => 'Acceptance Fee', 'slug' => 'acceptance-fee']);

        $response = $this->actingAs($admin)->post(route('admin.scholarships.store'), [
            'name' => 'Presidential Full Ride',
            'type' => 'percentage',
            'percentage' => 100,
            'amount' => 0,
            'covers_admin_charges' => true,
            'covers_hostel_fees' => true,
            'covers_all_expenses' => true,
            'waives_late_fees' => true,
            'excluded_fee_type_ids' => [$drugTestType->id],
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('scholarships', [
            'name' => 'Presidential Full Ride',
            'covers_all_expenses' => true,
            'waives_late_fees' => true,
            'percentage' => 100,
        ]);

        $sch = Scholarship::where('name', 'Presidential Full Ride')->first();
        $this->assertTrue($sch->isFeeTypeExcluded($drugTestType->id));
        $this->assertFalse($sch->isFeeTypeExcluded($acceptanceType->id));
    }

    public function test_all_expenses_scholarship_with_zero_exclusions_auto_settles_invoice()
    {
        $scholarship = Scholarship::create([
            'name' => 'Full Comprehensive Award',
            'type' => 'percentage',
            'percentage' => 100,
            'covers_admin_charges' => true,
            'covers_hostel_fees' => true,
            'covers_all_expenses' => true,
            'waives_late_fees' => true,
            'excluded_fee_type_ids' => [],
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Fatima Scholar',
            'email' => 'fatima@student.mewar.edu.ng',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'STU2026101',
            'scholarship_id' => $scholarship->id,
            'status' => 'active',
            'current_level' => '100',
        ]);

        $session = Session::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-01',
            'is_current' => true,
            'registration_enabled' => true,
        ]);

        $tuitionType = FeeType::create(['name' => 'Tuition Fee', 'slug' => 'tuition-fee', 'is_one_time' => false]);
        $matricType = FeeType::create(['name' => 'Matriculation Fee', 'slug' => 'matriculation-fee', 'is_one_time' => true]);

        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $tuitionType->id,
            'amount' => 150000,
            'level' => '100',
            'is_compulsory' => true,
        ]);

        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $matricType->id,
            'amount' => 25000,
            'level' => '100',
            'is_compulsory' => true,
        ]);

        SystemSetting::set('admin_charge_enabled', 'true');
        SystemSetting::set('admin_charge_amount', '250000');

        $feeService = app(FeeService::class);
        $invoice = $feeService->generateSchoolFeeInvoice($student, $session);

        $this->assertNotNull($invoice);
        $this->assertEquals(0, (float) $invoice->amount);
        $this->assertEquals('paid', $invoice->status);

        // Assert payment record was automatically created
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 0,
            'status' => 'success',
            'channel' => 'scholarship',
        ]);

        // Assert discount line item exists
        $discountItem = $invoice->items()->where('amount', '<', 0)->first();
        $this->assertNotNull($discountItem);
        $this->assertEquals(-(150000 + 25000 + 250000), (float) $discountItem->amount);
        $this->assertStringContainsString('All School Expenses Covered', $discountItem->description);
    }

    public function test_all_expenses_scholarship_with_exclusions_leaves_only_excluded_fees_payable()
    {
        $drugTestType = FeeType::create(['name' => 'Drug Test', 'slug' => 'drug-test', 'is_one_time' => false]);
        $tuitionType = FeeType::create(['name' => 'Tuition Fee', 'slug' => 'tuition-fee', 'is_one_time' => false]);

        $scholarship = Scholarship::create([
            'name' => 'Excellence Minus Drug Test',
            'type' => 'percentage',
            'percentage' => 100,
            'covers_admin_charges' => true,
            'covers_hostel_fees' => false,
            'covers_all_expenses' => true,
            'waives_late_fees' => true,
            'excluded_fee_type_ids' => [$drugTestType->id],
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Aliyu Scholar',
            'email' => 'aliyu@student.mewar.edu.ng',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'STU2026102',
            'scholarship_id' => $scholarship->id,
            'status' => 'active',
            'current_level' => '100',
        ]);

        $session = Session::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-01',
            'is_current' => true,
            'registration_enabled' => true,
        ]);

        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $tuitionType->id,
            'amount' => 100000,
            'level' => '100',
            'is_compulsory' => true,
        ]);

        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $drugTestType->id,
            'amount' => 15000,
            'level' => '100',
            'is_compulsory' => true,
        ]);

        SystemSetting::set('admin_charge_enabled', 'false');

        $feeService = app(FeeService::class);
        $invoice = $feeService->generateSchoolFeeInvoice($student, $session);

        $this->assertNotNull($invoice);
        // Student should ONLY owe the excluded drug test fee (15,000 NGN)
        $this->assertEquals(15000, (float) $invoice->amount);
        $this->assertEquals('pending', $invoice->status);

        $discountItem = $invoice->items()->where('amount', '<', 0)->first();
        $this->assertNotNull($discountItem);
        $this->assertEquals(-100000, (float) $discountItem->amount);
    }

    public function test_returning_student_with_waives_late_fees_incurs_no_late_fee()
    {
        $scholarship = Scholarship::create([
            'name' => 'Waiver Scholar Scheme',
            'type' => 'percentage',
            'percentage' => 50,
            'covers_admin_charges' => false,
            'covers_hostel_fees' => false,
            'covers_all_expenses' => false,
            'waives_late_fees' => true,
            'is_active' => true,
        ]);

        $oldSession = Session::create([
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-01',
            'is_current' => false,
        ]);

        $currentSession = Session::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-01',
            'late_payment_deadline' => now()->subDays(5), // Deadline is passed!
            'late_fee_amount' => 25000,
            'is_current' => true,
            'registration_enabled' => true,
        ]);

        $user = User::create([
            'name' => 'Returning Scholar',
            'email' => 'returning@student.mewar.edu.ng',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'STU2024001',
            'scholarship_id' => $scholarship->id,
            'admitted_session_id' => $oldSession->id, // Returning student
            'status' => 'active',
            'current_level' => '200',
        ]);

        $tuitionType = FeeType::create(['name' => 'Tuition Fee', 'slug' => 'tuition-fee', 'is_one_time' => false]);
        FeeConfiguration::create([
            'session_id' => $currentSession->id,
            'fee_type_id' => $tuitionType->id,
            'amount' => 100000,
            'level' => '200',
            'is_compulsory' => true,
        ]);

        SystemSetting::set('late_fee_enabled', 'true');
        SystemSetting::set('admin_charge_enabled', 'false');

        $feeService = app(FeeService::class);
        $invoice = $feeService->generateSchoolFeeInvoice($student, $currentSession);

        $this->assertNotNull($invoice);
        // Because waives_late_fees = true, late fee of 25,000 must NOT be added
        $this->assertFalse((bool) $invoice->late_fine_applied);
        $this->assertEquals(50000, (float) $invoice->amount);
        $this->assertFalse($invoice->items()->where('description', 'like', '%Late Payment Fine%')->exists());
    }

    public function test_apply_late_payment_fines_console_command_skips_waived_scholars()
    {
        $scholarship = Scholarship::create([
            'name' => 'Late Fee Immune Scheme',
            'type' => 'percentage',
            'percentage' => 0,
            'covers_all_expenses' => false,
            'waives_late_fees' => true,
            'is_active' => true,
        ]);

        $oldSession = Session::create([
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-01',
            'is_current' => false,
        ]);

        $currentSession = Session::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-01',
            'late_payment_deadline' => now()->subDays(5),
            'late_fee_amount' => 20000,
            'is_current' => true,
            'registration_enabled' => true,
        ]);

        $user = User::create([
            'name' => 'Immune Student',
            'email' => 'immune@student.mewar.edu.ng',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'STU2024099',
            'scholarship_id' => $scholarship->id,
            'admitted_session_id' => $oldSession->id,
            'status' => 'active',
            'current_level' => '200',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'session_id' => $currentSession->id,
            'type' => 'school_fee',
            'reference' => 'SCH-TEST-IMMUNE',
            'invoice_number' => 'INV-TEST-IMMUNE',
            'amount' => 50000,
            'status' => 'pending',
            'late_fine_applied' => false,
            'late_fine_waived' => false,
        ]);

        SystemSetting::set('late_fee_enabled', 'true');

        $this->artisan('fees:apply-late-payment-fines')
            ->assertExitCode(0);

        $invoice->refresh();
        $this->assertEquals(50000, (float) $invoice->amount);
        $this->assertFalse((bool) $invoice->late_fine_applied);
        $this->assertFalse($invoice->items()->where('description', 'like', '%Late Payment Fine%')->exists());
    }

    public function test_hostel_booking_with_all_expenses_and_hostel_coverage_auto_confirms()
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureStudentProfileCompleted::class);

        $scholarship = Scholarship::create([
            'name' => 'Full All-Expenses and Hostel',
            'type' => 'percentage',
            'percentage' => 100,
            'covers_all_expenses' => true,
            'covers_hostel_fees' => true,
            'waives_late_fees' => true,
            'is_active' => true,
        ]);

        $session = Session::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-01',
            'is_current' => true,
            'registration_enabled' => true,
        ]);

        $dept = \App\Models\Department::create(['name' => 'Computer Science', 'code' => 'CSC']);

        $user = User::create([
            'name' => 'Hostel Scholar',
            'email' => 'hostelscholar@mewar.edu.ng',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'matriculation_number' => 'STU2025HST',
            'scholarship_id' => $scholarship->id,
            'gender' => 'male',
            'department_id' => $dept->id,
            'status' => 'active',
            'current_level' => '100',
        ]);

        $hostel = \App\Models\Hostel::create([
            'name' => 'Alpha Hall',
            'gender_type' => 'male',
            'description' => 'Test Male Hostel',
        ]);
        $block = \App\Models\HostelBlock::create(['hostel_id' => $hostel->id, 'name' => 'Block 1']);
        $floor = \App\Models\HostelFloor::create(['hostel_block_id' => $block->id, 'name' => 'Floor 1']);
        $room = \App\Models\HostelRoom::create(['hostel_floor_id' => $floor->id, 'room_number' => '101', 'capacity' => 4]);

        \App\Models\HostelFee::create([
            'hostel_id' => $hostel->id,
            'session_id' => $session->id,
            'amount' => 80000.00,
        ]);

        $tuitionType = FeeType::create(['name' => 'Tuition Fee', 'slug' => 'tuition-fee', 'is_one_time' => false]);
        FeeConfiguration::create([
            'session_id' => $session->id,
            'fee_type_id' => $tuitionType->id,
            'amount' => 100000,
            'level' => '100',
            'is_compulsory' => true,
        ]);
        SystemSetting::set('admin_charge_enabled', 'false');

        // Generate school fee invoice which auto-settles to paid
        $feeService = app(FeeService::class);
        $schoolFeeInvoice = $feeService->generateSchoolFeeInvoice($student, $session);
        $this->assertEquals('paid', $schoolFeeInvoice->status);

        $response = $this->actingAs($user)->post(route('student.accommodation.store'), [
            'hostel_room_id' => $room->id,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('student.accommodation.index'));

        // Booking should be confirmed immediately and invoice marked as paid
        $booking = \App\Models\HostelBooking::where('student_id', $student->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals('confirmed', $booking->status);

        $invoice = Invoice::find($booking->invoice_id);
        $this->assertNotNull($invoice);
        $this->assertEquals(0, (float) $invoice->amount);
        $this->assertEquals('paid', $invoice->status);

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'amount' => 0,
            'status' => 'success',
            'channel' => 'scholarship',
        ]);
    }
}
