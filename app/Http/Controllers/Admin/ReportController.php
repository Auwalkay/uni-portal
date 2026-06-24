<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Expense;
use App\Models\Faculty;
use App\Models\Hostel;
use App\Models\HostelBooking;
use App\Models\HostelRoom;
use App\Models\InventoryAssignment;
use App\Models\InventoryComplaint;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payroll;
use App\Models\Programme;
use App\Models\Scholarship;
use App\Models\Session;
use App\Models\SickbayBed;
use App\Models\SickbayVisit;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Exports\SystemReportsExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Active filters
        $filters = [
            'session_id' => $request->query('session_id'),
            'faculty_id' => $request->query('faculty_id'),
            'department_id' => $request->query('department_id'),
            'program_id' => $request->query('program_id'),
            'level' => $request->query('level'),
            'gender' => $request->query('gender'),
            'entry_mode' => $request->query('entry_mode'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
        ];

        // 1. DYNAMIC QUERIES WITH FILTERS
        $studentQuery = Student::query();
        $applicantQuery = Applicant::query();

        if ($request->filled('faculty_id')) {
            $studentQuery->where('faculty_id', $request->faculty_id);
            $applicantQuery->whereHas('programme.department', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        if ($request->filled('department_id')) {
            $studentQuery->where('department_id', $request->department_id);
            $applicantQuery->whereHas('programme', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('program_id')) {
            $studentQuery->where('program_id', $request->program_id);
            $applicantQuery->where('program_choice_1', $request->program_id);
        }

        if ($request->filled('level')) {
            $studentQuery->where('current_level', $request->level);
        }

        if ($request->filled('gender')) {
            $studentQuery->where('gender', $request->gender);
            $applicantQuery->where('gender', $request->gender);
        }

        if ($request->filled('entry_mode')) {
            $studentQuery->where('entry_mode', $request->entry_mode);
            $applicantQuery->where('application_mode', $request->entry_mode);
        }

        // Apply session_id if provided
        if ($request->filled('session_id')) {
            $studentQuery->where('admitted_session_id', $request->session_id);
        }

        // Apply date range filters if provided
        if ($request->filled('start_date')) {
            $studentQuery->where('created_at', '>=', $request->start_date);
            $applicantQuery->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $studentQuery->where('created_at', '<=', $request->end_date . ' 23:59:59');
            $applicantQuery->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        // ACADEMICS & ADMISSIONS
        $academicStats = [
            'total_students' => (clone $studentQuery)->count(),
            'students_by_level' => (clone $studentQuery)->select('current_level as label', DB::raw('count(*) as value'))
                ->groupBy('current_level')
                ->orderBy('current_level')
                ->get(),
            'students_by_gender' => (clone $studentQuery)->select('gender as label', DB::raw('count(*) as value'))
                ->groupBy('gender')
                ->get(),
            'total_faculties' => Faculty::count(),
            'total_departments' => Department::count(),
            'total_programmes' => Programme::count(),
            'total_courses' => Course::count(),
            'total_registrations' => CourseRegistration::whereHas('student', function ($q) use ($request) {
                if ($request->filled('faculty_id')) $q->where('faculty_id', $request->faculty_id);
                if ($request->filled('department_id')) $q->where('department_id', $request->department_id);
                if ($request->filled('program_id')) $q->where('program_id', $request->program_id);
                if ($request->filled('level')) $q->where('current_level', $request->level);
                if ($request->filled('gender')) $q->where('gender', $request->gender);
                if ($request->filled('entry_mode')) $q->where('entry_mode', $request->entry_mode);
                if ($request->filled('session_id')) $q->where('admitted_session_id', $request->session_id);
            })
            ->when($request->filled('start_date'), function ($q) use ($request) {
                $q->where('created_at', '>=', $request->start_date);
            })
            ->when($request->filled('end_date'), function ($q) use ($request) {
                $q->where('created_at', '<=', $request->end_date . ' 23:59:59');
            })
            ->count(),
            'total_applicants' => (clone $applicantQuery)->count(),
            'applicants_by_status' => (clone $applicantQuery)->select('status as label', DB::raw('count(*) as value'))
                ->groupBy('status')
                ->get(),
            'applicants_by_mode' => (clone $applicantQuery)->select('application_mode as label', DB::raw('count(*) as value'))
                ->groupBy('application_mode')
                ->get(),
        ];

        // 2. DETAILED BREAKDOWNS (TABLES)
        // Students per Faculty
        $facQuery = Faculty::query();
        if ($request->filled('faculty_id')) {
            $facQuery->where('faculties.id', $request->faculty_id);
        }
        $studentsByFaculty = $facQuery->select('faculties.name as label', DB::raw('count(students.id) as value'))
            ->leftJoin('students', function ($join) use ($request) {
                $join->on('faculties.id', '=', 'students.faculty_id');
                if ($request->filled('department_id')) $join->where('students.department_id', $request->department_id);
                if ($request->filled('program_id')) $join->where('students.program_id', $request->program_id);
                if ($request->filled('level')) $join->where('students.current_level', $request->level);
                if ($request->filled('gender')) $join->where('students.gender', $request->gender);
                if ($request->filled('entry_mode')) $join->where('students.entry_mode', $request->entry_mode);
                if ($request->filled('session_id')) $join->where('students.admitted_session_id', $request->session_id);
            })
            ->groupBy('faculties.id', 'faculties.name')
            ->orderBy('value', 'desc')
            ->get();

        // Students per Department
        $deptQuery = Department::query();
        if ($request->filled('faculty_id')) {
            $deptQuery->where('departments.faculty_id', $request->faculty_id);
        }
        if ($request->filled('department_id')) {
            $deptQuery->where('departments.id', $request->department_id);
        }
        $studentsByDepartment = $deptQuery->select('departments.name as label', 'faculties.name as faculty', DB::raw('count(students.id) as value'))
            ->join('faculties', 'departments.faculty_id', '=', 'faculties.id')
            ->leftJoin('students', function ($join) use ($request) {
                $join->on('departments.id', '=', 'students.department_id');
                if ($request->filled('program_id')) $join->where('students.program_id', $request->program_id);
                if ($request->filled('level')) $join->where('students.current_level', $request->level);
                if ($request->filled('gender')) $join->where('students.gender', $request->gender);
                if ($request->filled('entry_mode')) $join->where('students.entry_mode', $request->entry_mode);
                if ($request->filled('session_id')) $join->where('students.admitted_session_id', $request->session_id);
            })
            ->groupBy('departments.id', 'departments.name', 'faculties.name')
            ->orderBy('value', 'desc')
            ->get();

        // Students per Programme
        $progQuery = Programme::query();
        if ($request->filled('department_id')) {
            $progQuery->where('programmes.department_id', $request->department_id);
        } elseif ($request->filled('faculty_id')) {
            $progQuery->whereHas('department', function($q) use ($request) {
                $q->where('departments.faculty_id', $request->faculty_id);
            });
        }
        if ($request->filled('program_id')) {
            $progQuery->where('programmes.id', $request->program_id);
        }
        $studentsByProgramme = $progQuery->select('programmes.name as label', 'departments.name as department', DB::raw('count(students.id) as value'))
            ->join('departments', 'programmes.department_id', '=', 'departments.id')
            ->leftJoin('students', function ($join) use ($request) {
                $join->on('programmes.id', '=', 'students.program_id');
                if ($request->filled('level')) $join->where('students.current_level', $request->level);
                if ($request->filled('gender')) $join->where('students.gender', $request->gender);
                if ($request->filled('entry_mode')) $join->where('students.entry_mode', $request->entry_mode);
                if ($request->filled('session_id')) $join->where('students.admitted_session_id', $request->session_id);
            })
            ->groupBy('programmes.id', 'programmes.name', 'departments.name')
            ->orderBy('value', 'desc')
            ->get();

        // 3. FINANCE & BURSARY (SCOPED BY FILTERS)
        $invoiceQuery = Invoice::query();
        $paymentQuery = Payment::query();

        if ($request->filled('session_id')) {
            $invoiceQuery->where('session_id', $request->session_id);
            $paymentQuery->whereHas('invoice', function ($q) use ($request) {
                $q->where('session_id', $request->session_id);
            });
        }

        if ($request->hasAny(['faculty_id', 'department_id', 'program_id', 'level', 'gender', 'entry_mode'])) {
            $studentFilter = function ($q) use ($request) {
                if ($request->filled('faculty_id')) $q->where('faculty_id', $request->faculty_id);
                if ($request->filled('department_id')) $q->where('department_id', $request->department_id);
                if ($request->filled('program_id')) $q->where('program_id', $request->program_id);
                if ($request->filled('level')) $q->where('current_level', $request->level);
                if ($request->filled('gender')) $q->where('gender', $request->gender);
                if ($request->filled('entry_mode')) $q->where('entry_mode', $request->entry_mode);
            };
            $invoiceQuery->whereHas('user.student', $studentFilter);
            $paymentQuery->whereHas('invoice.user.student', $studentFilter);
        }

        // Apply date range filters if provided
        if ($request->filled('start_date')) {
            $invoiceQuery->where('created_at', '>=', $request->start_date);
            $paymentQuery->where('paid_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $invoiceQuery->where('created_at', '<=', $request->end_date . ' 23:59:59');
            $paymentQuery->where('paid_at', '<=', $request->end_date . ' 23:59:59');
        }

        $totalInvoiced = (double) $invoiceQuery->sum('amount');
        $totalCollected = (double) (clone $paymentQuery)->where('status', 'success')->sum('amount');
        $outstandingBalance = max($totalInvoiced - $totalCollected, 0);

        $monthlyRevenueQuery = (clone $paymentQuery)->where('status', 'success');
        if (!$request->filled('start_date')) {
            $monthlyRevenueQuery->where('paid_at', '>=', now()->subMonths(6));
        }
        $monthlyRevenue = $monthlyRevenueQuery
            ->select(DB::raw("DATE_FORMAT(paid_at, '%b %Y') as label"), DB::raw('sum(amount) as value'))
            ->groupBy('label')
            ->orderBy(DB::raw("min(paid_at)"), 'asc')
            ->get();

        $expenseQuery = Expense::where('status', 'approved');
        if ($request->filled('start_date')) {
            $expenseQuery->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $expenseQuery->where('date', '<=', $request->end_date);
        }

        $expenseStats = [
            'total_expenses' => (double) (clone $expenseQuery)->sum('amount'),
            'expenses_by_category' => (clone $expenseQuery)
                ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
                ->select('expense_categories.name as label', DB::raw('sum(expenses.amount) as value'))
                ->groupBy('expense_categories.name')
                ->get(),
        ];

        $payrollQuery = Payroll::query();
        if ($request->filled('start_date')) {
            $payrollQuery->where('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $payrollQuery->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        $payrollStats = [
            'total_payroll_cost' => (double) (clone $payrollQuery)->sum('total_amount'),
            'total_payrolls_run' => $payrollQuery->count(),
        ];

        $scholarshipStats = [
            'total_scholarship_students' => (clone $studentQuery)->whereNotNull('scholarship_id')->count(),
            'total_scholarships' => Scholarship::count(),
        ];

        $financeStats = [
            'total_invoiced' => $totalInvoiced,
            'total_collected' => $totalCollected,
            'outstanding_balance' => $outstandingBalance,
            'collection_rate' => $totalInvoiced > 0 ? round(($totalCollected / $totalInvoiced) * 100, 1) : 0,
            'monthly_revenue' => $monthlyRevenue,
            'expenses' => $expenseStats,
            'payroll' => $payrollStats,
            'scholarship' => $scholarshipStats,
        ];

        // 4. HR & ATTENDANCE
        $totalStaff = Staff::count();
        $academicStaff = Staff::where('is_academic', true)->count();
        $nonAcademicStaff = Staff::where('is_academic', false)->count();

        // Calculate average attendance rates (last 30 days or custom range)
        $attendanceQuery = Attendance::query();
        if ($request->filled('start_date')) {
            $attendanceQuery->where('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $attendanceQuery->where('date', '<=', $request->end_date);
        }
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $attendanceQuery->where('date', '>=', now()->subDays(30));
        }

        $attendanceStats = [
            'total_staff' => $totalStaff,
            'academic_staff' => $academicStaff,
            'non_academic_staff' => $nonAcademicStaff,
            'attendance_rates' => $attendanceQuery
                ->select('status as label', DB::raw('count(*) as value'))
                ->groupBy('status')
                ->get(),
        ];

        // 5. HOSTELS & ACCOMMODATION
        $hostelCapacity = (int) HostelRoom::sum('capacity');
        $activeBookings = HostelBooking::where('status', 'active')->count();
        $hostelStats = [
            'total_hostels' => Hostel::count(),
            'total_rooms' => HostelRoom::count(),
            'bed_capacity' => $hostelCapacity,
            'occupied_beds' => $activeBookings,
            'vacant_beds' => max($hostelCapacity - $activeBookings, 0),
            'occupancy_rate' => $hostelCapacity > 0 ? round(($activeBookings / $hostelCapacity) * 100, 1) : 0,
        ];

        // 6. LIBRARY
        $bookLoanQuery = BookLoan::query();
        if ($request->filled('start_date')) {
            $bookLoanQuery->where('borrowed_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $bookLoanQuery->where('borrowed_at', '<=', $request->end_date . ' 23:59:59');
        }

        $libraryStats = [
            'total_books' => Book::count(),
            'total_loans' => (clone $bookLoanQuery)->count(),
            'active_loans' => (clone $bookLoanQuery)->where(function ($q) {
                $q->where('status', 'borrowed')->orWhereNull('returned_at');
            })->count(),
            'overdue_loans' => (clone $bookLoanQuery)->where(function ($q) {
                $q->where('status', 'overdue')
                    ->orWhere(function ($sq) {
                        $sq->whereNull('returned_at')->where('due_at', '<', now());
                    });
            })->count(),
        ];

        // 7. SICKBAY
        $sickbayVisitQuery = SickbayVisit::query();
        if ($request->filled('start_date')) {
            $sickbayVisitQuery->where('check_in_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $sickbayVisitQuery->where('check_in_at', '<=', $request->end_date . ' 23:59:59');
        }

        $sickbayStats = [
            'total_visits' => (clone $sickbayVisitQuery)->count(),
            'active_bed_occupancy' => SickbayVisit::where('status', 'under_observation')->whereNotNull('bed_number')->count(),
            'total_beds' => SickbayBed::count(),
            'visits_by_type' => $sickbayVisitQuery->select('visit_type as label', DB::raw('count(*) as value'))
                ->groupBy('visit_type')
                ->get(),
        ];

        // 8. INVENTORY
        $totalItemsCount = InventoryItem::count();
        $totalQty = (int) InventoryItem::sum('total_quantity');
        $availableQty = (int) InventoryItem::sum('available_quantity');
        $assignedQty = max($totalQty - $availableQty, 0);

        $inventoryStats = [
            'total_unique_items' => $totalItemsCount,
            'total_quantity' => $totalQty,
            'assigned_quantity' => $assignedQty,
            'available_quantity' => $availableQty,
            'total_complaints' => InventoryComplaint::count(),
            'pending_complaints' => InventoryComplaint::where('status', 'pending')->count(),
        ];

        return Inertia::render('Admin/Reports/Index', [
            'academicStats' => $academicStats,
            'studentsByFaculty' => $studentsByFaculty,
            'studentsByDepartment' => $studentsByDepartment,
            'studentsByProgramme' => $studentsByProgramme,
            'financeStats' => $financeStats,
            'attendanceStats' => $attendanceStats,
            'hostelStats' => $hostelStats,
            'libraryStats' => $libraryStats,
            'sickbayStats' => $sickbayStats,
            'inventoryStats' => $inventoryStats,
            
            // Lookups for filters
            'sessions' => \App\Services\AcademicCacheService::getSessions(),
            'faculties' => \App\Services\AcademicCacheService::getAllFaculties(),
            'departments' => \App\Services\AcademicCacheService::getAllDepartments(),
            'programmes' => \App\Services\AcademicCacheService::getProgrammes(),
            'entryModes' => ['UTME', 'Direct Entry', 'Transfer', 'Postgraduate'],
            'filters' => $filters,
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(new SystemReportsExport($request->all()), 'system_master_report_' . now()->format('Y_m_d_His') . '.xlsx');
    }
}
