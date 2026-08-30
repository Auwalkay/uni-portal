<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BursaryController extends Controller
{
    private function getFilteredStudentsQuery(Request $request, &$sessionId = null, &$feeType = 'school_fee')
    {
        $sessionId = $request->session_id ?? Session::current()?->id;
        $feeType = $request->input('fee_type', $request->input('type', 'school_fee'));
        if (empty($feeType) || !in_array($feeType, ['school_fee', 'hostel_fee', 'acceptance_fee', 'application_fee'])) {
            $feeType = 'school_fee';
        }

        $query = Student::query()
            ->select('students.*', 'users.name as user_name')
            ->distinct()
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('invoices', function ($join) use ($sessionId, $feeType) {
                $join->on('invoices.user_id', '=', 'students.user_id')
                    ->where('invoices.session_id', '=', $sessionId)
                    ->where('invoices.type', '=', $feeType);
            });

        // Faculty filter
        if ($request->filled('faculty_id') && $request->faculty_id !== 'ALL') {
            $query->where('students.faculty_id', $request->faculty_id);
        }

        // Department filter
        if ($request->filled('department_id') && $request->department_id !== 'ALL') {
            $query->where('students.department_id', $request->department_id);
        }

        // Program filter
        if ($request->filled('program_id') && $request->program_id !== 'ALL') {
            $query->where('students.program_id', $request->program_id);
        }

        // Level filter
        if ($request->filled('level') && $request->level !== 'ALL') {
            $query->where('students.current_level', $request->level);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('students.matriculation_number', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        // Fee Status Filter (unpaid / pending / partial / paid)
        if ($request->filled('status') && $request->status !== 'ALL') {
            $status = strtolower($request->status);

            if ($status === 'unpaid' || $status === 'pending') {
                $query->where(function ($q) use ($sessionId, $feeType) {
                    $q->whereDoesntHave('invoices', function ($sq) use ($sessionId, $feeType) {
                        $sq->where('session_id', $sessionId)->where('type', $feeType);
                    })->orWhereHas('invoices', function ($sq) use ($sessionId, $feeType) {
                        $sq->where('session_id', $sessionId)->where('type', $feeType)->whereIn('status', ['unpaid', 'pending']);
                    });
                });
            } else {
                $query->whereHas('invoices', function ($q) use ($status, $sessionId, $feeType) {
                    $q->where('session_id', $sessionId)->where('type', $feeType)->where('status', $status);
                });
            }
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'name');
        $sortOrder = $request->query('sort_order', 'asc');

        if ($sortBy === 'reg_number') {
            $query->orderBy('students.matriculation_number', $sortOrder);
        } elseif ($sortBy === 'status') {
            $query->orderByRaw("COALESCE(invoices.status, 'unpaid') " . $sortOrder);
        } elseif ($sortBy === 'balance') {
            $query->orderByRaw("(COALESCE(invoices.amount, 0) - COALESCE(invoices.paid_amount, 0)) " . $sortOrder);
        } else {
            $query->orderBy('users.name', $sortOrder);
        }

        return $query;
    }

    public function studentFeesReport(Request $request)
    {
        $sessionId = null;
        $feeType = 'school_fee';
        $query = $this->getFilteredStudentsQuery($request, $sessionId, $feeType);

        $query->with(['user', 'faculty', 'department', 'program', 'academicDepartment', 'scholarship', 'invoices']);

        $perPage = $request->query('per_page', 20);
        $students = $query->paginate($perPage)->withQueryString();

        // Calculate Stats for the whole filtered set (not just paginated)
        $totalStatsQuery = $this->getFilteredStudentsQuery($request, $sessionId, $feeType);
        $allMatchingStudents = $totalStatsQuery->with(['invoices'])->get();
        
        $stats = [
            'total_billed' => 0,
            'total_paid' => 0,
            'total_balance' => 0,
            'student_count' => $allMatchingStudents->count(),
            'paid_count' => 0,
            'partial_count' => 0,
            'unpaid_count' => 0,
        ];

        $allMatchingStudents->each(function ($student) use ($sessionId, $feeType, &$stats) {
            $invoice = $student->invoices
                ->where('session_id', $sessionId)
                ->where('type', $feeType)
                ->first();

            $billed = $invoice ? (float)$invoice->amount : 0;
            $paid = $invoice ? (float)$invoice->paid_amount : 0;
            
            $stats['total_billed'] += $billed;
            $stats['total_paid'] += $paid;
            $stats['total_balance'] += ($billed - $paid);

            $status = $invoice ? $invoice->status : 'unpaid';
            if ($status === 'paid') $stats['paid_count']++;
            elseif ($status === 'partial') $stats['partial_count']++;
            else $stats['unpaid_count']++;
        });

        // Load invoices for the selected session with their payments to avoid N+1 for paginated list
        $students->getCollection()->each(function ($student) use ($sessionId, $feeType) {
            $invoice = $student->invoices
                ->where('session_id', $sessionId)
                ->where('type', $feeType)
                ->first();

            $lastPayment = null;
            if ($invoice) {
                $lastPayment = Payment::where('invoice_id', $invoice->id)
                    ->where('status', 'success')
                    ->latest('paid_at')
                    ->first();
            }

            $student->fee_status = $invoice ? $invoice->status : 'unpaid';
            $student->fee_type = $feeType;
            $student->total_billed = $invoice ? (float)$invoice->amount : 0;
            $student->total_paid = $invoice ? (float)$invoice->paid_amount : 0;
            $student->balance = $invoice ? ((float)$invoice->amount - (float)$invoice->paid_amount) : 0;
            $student->last_payment_date = $lastPayment ? $lastPayment->paid_at : null;
        });

        return Inertia::render('Admin/Finance/StudentFees', [
            'students' => $students,
            'summaryStats' => $stats,
            'sessions' => \App\Services\AcademicCacheService::getSessions(),
            'currentSession' => Session::find($sessionId),
            'faculties' => \App\Services\AcademicCacheService::getAllFaculties(),
            'departments' => \App\Services\AcademicCacheService::getAllDepartments(),
            'programs' => \App\Services\AcademicCacheService::getAllProgrammes(),
            'filters' => [
                'session_id' => $request->query('session_id'),
                'fee_type' => $feeType,
                'faculty_id' => $request->query('faculty_id'),
                'department_id' => $request->query('department_id'),
                'program_id' => $request->query('program_id'),
                'level' => $request->query('level'),
                'status' => $request->query('status'),
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'name'),
                'sort_order' => $request->query('sort_order', 'asc'),
                'per_page' => (int)$perPage,
            ],
        ]);
    }

    public function exportPDF(Request $request)
    {
        $sessionId = null;
        $feeType = 'school_fee';
        $query = $this->getFilteredStudentsQuery($request, $sessionId, $feeType);
        $session = Session::find($sessionId);

        $students = $query->with([
            'user', 
            'faculty', 
            'department', 
            'program', 
            'invoices' => function($q) use ($sessionId, $feeType) {
                $q->where('session_id', $sessionId)->where('type', $feeType);
            }
        ])->get();

        $students->transform(function ($student) use ($feeType) {
            $invoice = $student->invoices->first();
            $student->fee_status = $invoice ? $invoice->status : 'unpaid';
            $student->fee_type = $feeType;
            $student->total_billed = $invoice ? (float)$invoice->amount : 0;
            $student->total_paid = $invoice ? (float)$invoice->paid_amount : 0;
            $student->balance = $invoice ? ((float)$invoice->amount - (float)$invoice->paid_amount) : 0;
            return $student;
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.bursary_fees_report', [
            'students' => $students,
            'session' => $session,
            'feeType' => $feeType,
            'date' => now()->format('d M, Y')
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('student_fees_report_' . now()->format('Y_m_d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $sessionId = null;
        $feeType = 'school_fee';
        $query = $this->getFilteredStudentsQuery($request, $sessionId, $feeType);

        $students = $query->with([
            'user', 
            'faculty', 
            'department', 
            'program', 
            'scholarship',
            'invoices' => function($q) use ($sessionId, $feeType) {
                $q->where('session_id', $sessionId)->where('type', $feeType);
            }
        ])->get();

        $invoiceIds = $students->pluck('invoices')->flatten()->pluck('id')->filter()->values();

        $latestPaymentByInvoice = Payment::whereIn('invoice_id', $invoiceIds)
            ->where('status', 'success')
            ->orderByDesc('paid_at')
            ->get()
            ->groupBy('invoice_id')
            ->map(fn ($payments) => $payments->first());

        $students->transform(function ($student) use ($latestPaymentByInvoice, $feeType) {
            $invoice = $student->invoices->first();
            $lastPayment = $invoice ? $latestPaymentByInvoice->get($invoice->id) : null;

            $student->fee_status = $invoice ? $invoice->status : 'unpaid';
            $student->fee_type = $feeType;
            $student->total_billed = $invoice ? (float)$invoice->amount : 0;
            $student->total_paid = $invoice ? (float)$invoice->paid_amount : 0;
            $student->balance = $invoice ? ((float)$invoice->amount - (float)$invoice->paid_amount) : 0;
            $student->last_payment_date = $lastPayment ? $lastPayment->paid_at : null;

            return $student;
        });

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StudentFeesExport($students), 
            'student_fees_report_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
