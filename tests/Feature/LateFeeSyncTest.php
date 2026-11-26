<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Session;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LateFeeSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_session_late_fee_syncs_pending_returning_student_invoices_only(): void
    {
        $accessPerm = Permission::firstOrCreate(['name' => 'access_admin_dashboard', 'guard_name' => 'web']);
        $sessionsPerm = Permission::firstOrCreate(['name' => 'manage_academic_sessions', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([$accessPerm, $sessionsPerm]);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole($adminRole);

        $oldSession = Session::create([
            'name' => '2025/2026',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_current' => false,
        ]);

        $session = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
            'late_payment_deadline' => now()->subDay(),
            'late_fee_amount' => 50000,
        ]);

        // 1. Returning student with pending invoice already having old 50,000 fine (base 500,000 + 50,000 = 550,000)
        $retUser1 = User::factory()->create();
        Student::create([
            'user_id' => $retUser1->id,
            'matriculation_number' => 'MIURET001',
            'admitted_session_id' => $oldSession->id,
            'current_level' => 200,
            'status' => 'active',
        ]);
        $inv1 = Invoice::create([
            'user_id' => $retUser1->id,
            'session_id' => $session->id,
            'type' => 'school_fee',
            'reference' => 'REF-RET-1',
            'invoice_number' => 'INV-RET-1',
            'amount' => 550000,
            'paid_amount' => 0,
            'status' => 'pending',
            'late_fine_applied' => true,
            'late_fine_waived' => false,
            'due_date' => now()->addDays(7),
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'description' => 'Tuition Fee',
            'amount' => 500000,
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'description' => 'Late Payment Fine (2026/2027)',
            'amount' => 50000,
        ]);

        // 2. Returning student with pending invoice WITHOUT late fine yet (500,000)
        $retUser2 = User::factory()->create();
        Student::create([
            'user_id' => $retUser2->id,
            'matriculation_number' => 'MIURET002',
            'admitted_session_id' => $oldSession->id,
            'current_level' => 200,
            'status' => 'active',
        ]);
        $inv2 = Invoice::create([
            'user_id' => $retUser2->id,
            'session_id' => $session->id,
            'type' => 'school_fee',
            'reference' => 'REF-RET-2',
            'invoice_number' => 'INV-RET-2',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'pending',
            'late_fine_applied' => false,
            'late_fine_waived' => false,
            'due_date' => now()->addDays(7),
        ]);

        // 3. Returning student with PAID invoice (must NOT change)
        $paidUser = User::factory()->create();
        Student::create([
            'user_id' => $paidUser->id,
            'matriculation_number' => 'MIUPAID1',
            'admitted_session_id' => $oldSession->id,
            'current_level' => 200,
            'status' => 'active',
        ]);
        $paidInv = Invoice::create([
            'user_id' => $paidUser->id,
            'session_id' => $session->id,
            'type' => 'school_fee',
            'reference' => 'REF-PAID-1',
            'invoice_number' => 'INV-PAID-1',
            'amount' => 550000,
            'paid_amount' => 550000,
            'status' => 'paid',
            'late_fine_applied' => true,
            'due_date' => now()->addDays(7),
        ]);

        // 4. Fresh student (must NOT get late fine)
        $freshUser = User::factory()->create();
        Student::create([
            'user_id' => $freshUser->id,
            'matriculation_number' => 'MIUFRESH1',
            'admitted_session_id' => $session->id,
            'current_level' => 100,
            'status' => 'active',
        ]);
        $freshInv = Invoice::create([
            'user_id' => $freshUser->id,
            'session_id' => $session->id,
            'type' => 'school_fee',
            'reference' => 'REF-FRESH-1',
            'invoice_number' => 'INV-FRESH-1',
            'amount' => 500000,
            'paid_amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addDays(7),
        ]);

        // Update fine to 125,000 with sync_unpaid_invoices = true
        $res = $this->actingAs($admin)->put(route('admin.sessions.settings', $session->id), [
            'registration_enabled' => true,
            'applications_enabled' => true,
            'admissions_enabled' => true,
            'school_fee_payment_enabled' => true,
            'late_payment_deadline' => now()->subDay()->format('Y-m-d\TH:i'),
            'late_fee_amount' => 125000,
            'sync_unpaid_invoices' => true,
        ]);

        $res->assertRedirect();

        $this->assertEquals(625000, (float) $inv1->fresh()->amount);
        $this->assertEquals(125000, (float) $inv1->items()->where('description', 'like', '%Late Payment Fine%')->first()->amount);

        $this->assertEquals(625000, (float) $inv2->fresh()->amount);
        $this->assertTrue((bool) $inv2->fresh()->late_fine_applied);
        $this->assertEquals(125000, (float) $inv2->items()->where('description', 'like', '%Late Payment Fine%')->first()->amount);

        // Paid and fresh invoices untouched
        $this->assertEquals(550000, (float) $paidInv->fresh()->amount);
        $this->assertEquals(500000, (float) $freshInv->fresh()->amount);

        // Setting late_fee_amount to 0 must NOT change existing invoices
        $this->actingAs($admin)->put(route('admin.sessions.settings', $session->id), [
            'registration_enabled' => true,
            'applications_enabled' => true,
            'admissions_enabled' => true,
            'school_fee_payment_enabled' => true,
            'late_payment_deadline' => now()->subDay()->format('Y-m-d\TH:i'),
            'late_fee_amount' => 0,
            'sync_unpaid_invoices' => true,
        ])->assertRedirect();

        $this->assertEquals(625000, (float) $inv1->fresh()->amount);
    }

    public function test_sync_late_fees_dispatches_unique_bulk_job(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $accessPerm = Permission::firstOrCreate(['name' => 'access_admin_dashboard', 'guard_name' => 'web']);
        $sessionsPerm = Permission::firstOrCreate(['name' => 'manage_academic_sessions', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo([$accessPerm, $sessionsPerm]);

        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole($adminRole);

        $session = Session::create([
            'name' => '2026/2027',
            'academic_stream' => 'UG',
            'calendar_type' => 'semester',
            'start_date' => '2026-09-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
            'late_payment_deadline' => now()->subDay(),
            'late_fee_amount' => 125000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.sessions.sync_late_fees', $session->id))
            ->assertRedirect();

        \Illuminate\Support\Facades\Queue::assertPushedOn('bulk', \App\Jobs\Finance\SyncLateFeeInvoicesJob::class, function ($job) use ($session) {
            return $job->sessionId === $session->id;
        });
    }
}
