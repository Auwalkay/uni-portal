<?php

namespace Tests\Feature\Admin;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;
    protected ExpenseCategory $category1;
    protected ExpenseCategory $category2;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('access_admin_dashboard');
        Permission::findOrCreate('view_expenses');
        Permission::findOrCreate('approve_expenses');
        Permission::findOrCreate('request_expenses_for_others');

        $adminRole = Role::findOrCreate('admin');
        $adminRole->givePermissionTo([
            'access_admin_dashboard',
            'view_expenses',
            'approve_expenses',
            'request_expenses_for_others',
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Finance Director',
            'email' => 'finance_dir@portal.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->staffUser = User::create([
            'name' => 'Staff Beneficiary',
            'email' => 'staff_beneficiary@portal.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->category1 = ExpenseCategory::create([
            'name' => 'Office Supplies',
            'description' => 'Stationery and office items',
        ]);

        $this->category2 = ExpenseCategory::create([
            'name' => 'Laboratory Equipment',
            'description' => 'Science lab chemicals and tools',
        ]);
    }

    public function test_admin_can_view_expenses_with_stats_and_pagination(): void
    {
        // Create 20 sample expenses across various statuses
        for ($i = 1; $i <= 10; $i++) {
            Expense::create([
                'title' => "Office Supplies Expense {$i}",
                'amount' => 5000,
                'expense_category_id' => $this->category1->id,
                'date' => now()->toDateString(),
                'status' => 'approved',
                'user_id' => $this->staffUser->id,
                'requested_by' => $this->adminUser->id,
            ]);
        }

        for ($i = 1; $i <= 5; $i++) {
            Expense::create([
                'title' => "Pending Lab Equipment {$i}",
                'amount' => 12000,
                'expense_category_id' => $this->category2->id,
                'date' => now()->toDateString(),
                'status' => 'pending',
                'user_id' => $this->staffUser->id,
                'requested_by' => $this->staffUser->id,
            ]);
        }

        for ($i = 1; $i <= 3; $i++) {
            Expense::create([
                'title' => "Rejected Travel Claim {$i}",
                'amount' => 8000,
                'expense_category_id' => $this->category1->id,
                'date' => now()->toDateString(),
                'status' => 'rejected',
                'rejection_reason' => 'Duplicate submission',
                'user_id' => $this->staffUser->id,
                'requested_by' => $this->staffUser->id,
            ]);
        }

        $response = $this->actingAs($this->adminUser)->get(route('admin.finance.expenses.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Finance/Expenses/Index')
            ->has('expenses.data', 15) // Default 15 per page
            ->where('expenses.total', 18)
            ->where('stats.total_approved_amount', 50000) // 10 * 5000
            ->where('stats.pending_count', 5)
            ->where('stats.pending_amount', 60000) // 5 * 12000
            ->where('stats.rejected_count', 3)
            ->where('stats.status_counts.all', 18)
            ->where('stats.status_counts.pending', 5)
            ->where('stats.status_counts.approved', 10)
            ->where('stats.status_counts.rejected', 3)
            ->has('categories', 2)
        );
    }

    public function test_expenses_can_be_filtered_by_status(): void
    {
        Expense::create([
            'title' => 'Approved Generator Fuel',
            'amount' => 45000,
            'expense_category_id' => $this->category1->id,
            'date' => now()->toDateString(),
            'status' => 'approved',
            'user_id' => $this->staffUser->id,
        ]);

        Expense::create([
            'title' => 'Pending Printer Toner',
            'amount' => 15000,
            'expense_category_id' => $this->category1->id,
            'date' => now()->toDateString(),
            'status' => 'pending',
            'user_id' => $this->staffUser->id,
        ]);

        // Filter: pending
        $response = $this->actingAs($this->adminUser)->get(route('admin.finance.expenses.index', ['status' => 'pending']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('expenses.total', 1)
            ->where('expenses.data.0.title', 'Pending Printer Toner')
        );

        // Filter: all (should return both)
        $responseAll = $this->actingAs($this->adminUser)->get(route('admin.finance.expenses.index', ['status' => 'all']));
        $responseAll->assertStatus(200);
        $responseAll->assertInertia(fn ($page) => $page
            ->where('expenses.total', 2)
        );
    }

    public function test_expenses_can_be_filtered_by_search_keyword(): void
    {
        Expense::create([
            'title' => 'Microbiology Test Tubes',
            'amount' => 30000,
            'expense_category_id' => $this->category2->id,
            'date' => now()->toDateString(),
            'status' => 'pending',
            'user_id' => $this->staffUser->id,
        ]);

        Expense::create([
            'title' => 'Printing Exam Booklets',
            'amount' => 70000,
            'expense_category_id' => $this->category1->id,
            'date' => now()->toDateString(),
            'status' => 'approved',
            'user_id' => $this->staffUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.finance.expenses.index', ['search' => 'Microbiology']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('expenses.total', 1)
            ->where('expenses.data.0.title', 'Microbiology Test Tubes')
        );
    }

    public function test_expenses_can_be_filtered_by_category(): void
    {
        Expense::create([
            'title' => 'Whiteboard Markers',
            'amount' => 5000,
            'expense_category_id' => $this->category1->id,
            'date' => now()->toDateString(),
            'status' => 'approved',
            'user_id' => $this->staffUser->id,
        ]);

        Expense::create([
            'title' => 'Centrifuge Machine Repair',
            'amount' => 85000,
            'expense_category_id' => $this->category2->id,
            'date' => now()->toDateString(),
            'status' => 'approved',
            'user_id' => $this->staffUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.finance.expenses.index', [
            'category_id' => $this->category2->id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('expenses.total', 1)
            ->where('expenses.data.0.title', 'Centrifuge Machine Repair')
        );
    }

    public function test_expenses_can_be_filtered_by_date_range(): void
    {
        Expense::create([
            'title' => 'Past Expense',
            'amount' => 10000,
            'expense_category_id' => $this->category1->id,
            'date' => '2026-01-15',
            'status' => 'approved',
            'user_id' => $this->staffUser->id,
        ]);

        Expense::create([
            'title' => 'Recent Expense',
            'amount' => 20000,
            'expense_category_id' => $this->category1->id,
            'date' => '2026-06-20',
            'status' => 'approved',
            'user_id' => $this->staffUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('admin.finance.expenses.index', [
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('expenses.total', 1)
            ->where('expenses.data.0.title', 'Recent Expense')
        );
    }

    public function test_expenses_support_per_page_customization(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Expense::create([
                'title' => "Bulk Expense {$i}",
                'amount' => 2000,
                'expense_category_id' => $this->category1->id,
                'date' => now()->toDateString(),
                'status' => 'pending',
                'user_id' => $this->staffUser->id,
            ]);
        }

        $response = $this->actingAs($this->adminUser)->get(route('admin.finance.expenses.index', ['per_page' => 25]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('expenses.data', 25)
            ->where('expenses.per_page', 25)
            ->where('expenses.total', 30)
        );
    }

    public function test_authorized_user_can_approve_expense(): void
    {
        $expense = Expense::create([
            'title' => 'Pending Seminar Refreshment',
            'amount' => 25000,
            'expense_category_id' => $this->category1->id,
            'date' => now()->toDateString(),
            'status' => 'pending',
            'user_id' => $this->staffUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.finance.expenses.approve', $expense->id));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Expense approved.');

        $expense->refresh();
        $this->assertEquals('approved', $expense->status);
        $this->assertEquals($this->adminUser->id, $expense->approved_by);
    }

    public function test_authorized_user_can_reject_expense_with_reason(): void
    {
        $expense = Expense::create([
            'title' => 'Pending Luxury Chair Request',
            'amount' => 150000,
            'expense_category_id' => $this->category1->id,
            'date' => now()->toDateString(),
            'status' => 'pending',
            'user_id' => $this->staffUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.finance.expenses.reject', $expense->id), [
                'rejection_reason' => 'Expenditure not budgeted for the current term.',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Expense rejected.');

        $expense->refresh();
        $this->assertEquals('rejected', $expense->status);
        $this->assertEquals('Expenditure not budgeted for the current term.', $expense->rejection_reason);
        $this->assertEquals($this->adminUser->id, $expense->approved_by);
    }

    public function test_authorized_user_can_export_filtered_expenses_to_csv(): void
    {
        Expense::create([
            'title' => 'Exportable Lab Reagents',
            'amount' => 48000,
            'expense_category_id' => $this->category2->id,
            'date' => '2026-03-10',
            'status' => 'approved',
            'user_id' => $this->staffUser->id,
            'approved_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.finance.expenses.export', ['category_id' => $this->category2->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Date,Title,Category', $content);
        $this->assertStringContainsString('Amount (NGN)', $content);
        $this->assertStringContainsString('Exportable Lab Reagents', $content);
        $this->assertStringContainsString('Laboratory Equipment', $content);
        $this->assertStringContainsString('48000.00', $content);
    }
}
