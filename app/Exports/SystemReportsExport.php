<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\Expense;
use App\Models\Hostel;
use App\Models\Payment;
use App\Models\SickbayVisit;
use App\Models\Staff;
use App\Models\BookLoan;
use App\Models\Book;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SystemReportsExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new AcademicSummarySheet($this->filters),
            new FinancialPaymentsSheet($this->filters),
            new ExpensesSheet($this->filters),
            new HRStaffSheet($this->filters),
            new HostelOccupancySheet($this->filters),
            new SickbayLogSheet($this->filters),
        ];
    }
}

// 1. ACADEMIC SUMMARY SHEET
class AcademicSummarySheet implements FromCollection, WithHeadings, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $deptQuery = Department::with(['faculty', 'students']);
        if (!empty($this->filters['faculty_id']) && $this->filters['faculty_id'] !== 'all') {
            $deptQuery->where('faculty_id', $this->filters['faculty_id']);
        }
        if (!empty($this->filters['department_id']) && $this->filters['department_id'] !== 'all') {
            $deptQuery->where('id', $this->filters['department_id']);
        }

        return $deptQuery->get()
            ->map(function ($dept) {
                $students = $dept->students;

                if (!empty($this->filters['program_id']) && $this->filters['program_id'] !== 'all') {
                    $students = $students->where('program_id', $this->filters['program_id']);
                }
                if (!empty($this->filters['session_id']) && $this->filters['session_id'] !== 'all') {
                    $students = $students->where('admitted_session_id', $this->filters['session_id']);
                }
                if (!empty($this->filters['level']) && $this->filters['level'] !== 'all') {
                    $students = $students->where('current_level', $this->filters['level']);
                }
                if (!empty($this->filters['gender']) && $this->filters['gender'] !== 'all') {
                    $students = $students->where('gender', $this->filters['gender']);
                }
                if (!empty($this->filters['entry_mode']) && $this->filters['entry_mode'] !== 'all') {
                    $students = $students->where('entry_mode', $this->filters['entry_mode']);
                }
                if (!empty($this->filters['start_date'])) {
                    $students = $students->where('created_at', '>=', $this->filters['start_date']);
                }
                if (!empty($this->filters['end_date'])) {
                    $students = $students->where('created_at', '<=', $this->filters['end_date'] . ' 23:59:59');
                }

                return [
                    'department' => $dept->name,
                    'faculty' => $dept->faculty?->name ?? 'N/A',
                    'total_students' => $students->count(),
                    'male_students' => $students->where('gender', 'male')->count(),
                    'female_students' => $students->where('gender', 'female')->count(),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Department',
            'Faculty',
            'Total Students',
            'Male Students',
            'Female Students',
        ];
    }

    public function title(): string
    {
        return 'Academics Summary';
    }
}

// 2. FINANCIAL PAYMENTS SHEET
class FinancialPaymentsSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Payment::with(['invoice.user'])
            ->where('status', 'success');

        if (!empty($this->filters['session_id']) && $this->filters['session_id'] !== 'all') {
            $query->whereHas('invoice', function ($q) {
                $q->where('session_id', $this->filters['session_id']);
            });
        }

        if (
            (!empty($this->filters['faculty_id']) && $this->filters['faculty_id'] !== 'all') ||
            (!empty($this->filters['department_id']) && $this->filters['department_id'] !== 'all') ||
            (!empty($this->filters['program_id']) && $this->filters['program_id'] !== 'all') ||
            (!empty($this->filters['level']) && $this->filters['level'] !== 'all') ||
            (!empty($this->filters['gender']) && $this->filters['gender'] !== 'all') ||
            (!empty($this->filters['entry_mode']) && $this->filters['entry_mode'] !== 'all')
        ) {
            $query->whereHas('invoice.user.student', function ($q) {
                if (!empty($this->filters['faculty_id']) && $this->filters['faculty_id'] !== 'all') $q->where('faculty_id', $this->filters['faculty_id']);
                if (!empty($this->filters['department_id']) && $this->filters['department_id'] !== 'all') $q->where('department_id', $this->filters['department_id']);
                if (!empty($this->filters['program_id']) && $this->filters['program_id'] !== 'all') $q->where('program_id', $this->filters['program_id']);
                if (!empty($this->filters['level']) && $this->filters['level'] !== 'all') $q->where('current_level', $this->filters['level']);
                if (!empty($this->filters['gender']) && $this->filters['gender'] !== 'all') $q->where('gender', $this->filters['gender']);
                if (!empty($this->filters['entry_mode']) && $this->filters['entry_mode'] !== 'all') $q->where('entry_mode', $this->filters['entry_mode']);
            });
        }

        if (!empty($this->filters['start_date'])) {
            $query->where('paid_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->where('paid_at', '<=', $this->filters['end_date'] . ' 23:59:59');
        }

        return $query->latest('paid_at')
            ->take(500)
            ->get()
            ->map(function ($payment) {
                return [
                    'date' => $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : 'N/A',
                    'reference' => $payment->invoice?->reference ?? 'N/A',
                    'student_name' => $payment->invoice?->user?->name ?? 'N/A',
                    'amount' => (double) $payment->amount,
                    'status' => strtoupper($payment->status),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Payment Date',
            'Invoice Reference',
            'Student Name',
            'Amount (NGN)',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Recent Payments';
    }
}

// 3. EXPENSES SHEET
class ExpensesSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Expense::with('category')
            ->where('status', 'approved');

        if (!empty($this->filters['start_date'])) {
            $query->where('date', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->where('date', '<=', $this->filters['end_date']);
        }

        return $query->latest('date')
            ->take(500)
            ->get()
            ->map(function ($expense) {
                return [
                    'date' => $expense->date ? $expense->date->format('Y-m-d') : 'N/A',
                    'description' => $expense->description,
                    'category' => $expense->category?->name ?? 'N/A',
                    'amount' => (double) $expense->amount,
                    'status' => strtoupper($expense->status),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Expense Date',
            'Description',
            'Category',
            'Amount (NGN)',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Approved Expenses';
    }
}

// 4. HR STAFF SHEET
class HRStaffSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Staff::with(['user', 'department', 'designation']);

        if (!empty($this->filters['department_id']) && $this->filters['department_id'] !== 'all') {
            $query->where('department_id', $this->filters['department_id']);
        } elseif (!empty($this->filters['faculty_id']) && $this->filters['faculty_id'] !== 'all') {
            $query->whereHas('department', function ($q) {
                $q->where('faculty_id', $this->filters['faculty_id']);
            });
        }

        return $query->get()
            ->map(function ($staff) {
                return [
                    'name' => $staff->user?->name ?? 'N/A',
                    'email' => $staff->user?->email ?? 'N/A',
                    'department' => $staff->department?->name ?? 'N/A',
                    'designation' => $staff->designation?->name ?? 'N/A',
                    'type' => $staff->is_academic ? 'Academic' : 'Non-Academic',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Staff Name',
            'Email',
            'Department',
            'Designation',
            'Role Type',
        ];
    }

    public function title(): string
    {
        return 'Staff Directory';
    }
}

// 5. HOSTEL OCCUPANCY SHEET
class HostelOccupancySheet implements FromCollection, WithHeadings, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return Hostel::all()->map(function ($hostel) {
            // Calculate rooms and capacities
            $rooms = \App\Models\HostelRoom::whereHas('floor', function ($q) use ($hostel) {
                $q->where('hostel_id', $hostel->id);
            })->get();

            $capacity = $rooms->sum('capacity');
            
            $activeBookings = \App\Models\HostelBooking::where('status', 'active')
                ->whereHas('room.floor', function ($q) use ($hostel) {
                    $q->where('hostel_id', $hostel->id);
                })->count();

            return [
                'hostel' => $hostel->name,
                'total_rooms' => $rooms->count(),
                'capacity' => $capacity,
                'occupied' => $activeBookings,
                'occupancy_rate' => $capacity > 0 ? round(($activeBookings / $capacity) * 100, 1) . '%' : '0%',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Hostel Name',
            'Total Rooms',
            'Bed Capacity',
            'Occupied Beds',
            'Occupancy Rate',
        ];
    }

    public function title(): string
    {
        return 'Hostel Occupancy';
    }
}

// 6. SICKBAY LOG SHEET
class SickbayLogSheet implements FromCollection, WithHeadings, WithTitle
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = SickbayVisit::with(['patient', 'attendant']);

        if (!empty($this->filters['start_date'])) {
            $query->where('check_in_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->where('check_in_at', '<=', $this->filters['end_date'] . ' 23:59:59');
        }

        return $query->latest('check_in_at')
            ->take(500)
            ->get()
            ->map(function ($visit) {
                return [
                    'date' => $visit->check_in_at ? $visit->check_in_at->format('Y-m-d H:i') : 'N/A',
                    'patient' => $visit->patient?->name ?? 'N/A',
                    'type' => ucfirst($visit->visit_type),
                    'symptoms' => $visit->symptoms,
                    'bed' => $visit->bed_number ?? 'N/A',
                    'status' => strtoupper($visit->status),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Visit Date',
            'Patient Name',
            'Visit Type',
            'Symptoms / Notes',
            'Bed Number',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Sickbay Logs';
    }
}
