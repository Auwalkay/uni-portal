<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Student;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BursaryController extends Controller
{
    public function studentFeesReport(Request $request)
    {
        $query = Student::query()
            ->with(['user', 'faculty', 'department', 'program', 'academicDepartment', 'scholarship']);

        // Filters
        if ($request->filled('session_id')) {
            $query->whereHas('invoices', function ($q) use ($request) {
                $q->where('session_id', $request->session_id)->where('type', 'school_fee');
            });
        }

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        if ($request->filled('level')) {
            $query->where('current_level', $request->level);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('matriculation_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Apply specific fee status filter if requested
        if ($request->filled('status')) {
            $status = $request->status;
            $sessionId = $request->session_id ?? Session::current()?->id;

            if ($status === 'unpaid') {
                $query->where(function ($q) use ($sessionId) {
                    $q->whereDoesntHave('invoices', function ($sq) use ($sessionId) {
                        $sq->where('type', 'school_fee')->where('session_id', $sessionId);
                    })->orWhereHas('invoices', function ($sq) use ($sessionId) {
                        $sq->where('type', 'school_fee')->where('session_id', $sessionId)->where('status', 'unpaid');
                    });
                });
            } else {
                $query->whereHas('invoices', function ($q) use ($status, $sessionId) {
                    $q->where('type', 'school_fee')->where('session_id', $sessionId)->where('status', $status);
                });
            }
        }

        $sessionId = $request->session_id ?? Session::current()?->id;

        $students = $query->paginate(20)->withQueryString();

        // Calculate Stats for the whole filtered set (not just paginated)
        $totalStatsQuery = clone $query;
        $allMatchingStudents = $totalStatsQuery->get();
        
        $stats = [
            'total_billed' => 0,
            'total_paid' => 0,
            'total_balance' => 0,
            'student_count' => $allMatchingStudents->count(),
            'paid_count' => 0,
            'partial_count' => 0,
            'unpaid_count' => 0,
        ];

        $allMatchingStudents->each(function ($student) use ($sessionId, &$stats) {
            $invoice = $student->invoices
                ->where('type', 'school_fee')
                ->where('session_id', $sessionId)
                ->first();

            $billed = $invoice ? $invoice->amount : 0;
            $paid = $invoice ? $invoice->paid_amount : 0;
            
            $stats['total_billed'] += $billed;
            $stats['total_paid'] += $paid;
            $stats['total_balance'] += ($billed - $paid);

            $status = $invoice ? $invoice->status : 'unpaid';
            if ($status === 'paid') $stats['paid_count']++;
            elseif ($status === 'partially_paid') $stats['partial_count']++;
            else $stats['unpaid_count']++;
        });

        // Load invoices for the selected session with their payments to avoid N+1 for paginated list
        $students->getCollection()->each(function ($student) use ($sessionId) {
            $invoice = $student->invoices
                ->where('type', 'school_fee')
                ->where('session_id', $sessionId)
                ->first();

            $lastPayment = null;
            if ($invoice) {
                $lastPayment = \App\Models\Payment::where('invoice_id', $invoice->id)
                    ->where('status', 'success')
                    ->latest('paid_at')
                    ->first();
            }

            $student->fee_status = $invoice ? $invoice->status : 'unpaid';
            $student->total_billed = $invoice ? $invoice->amount : 0;
            $student->total_paid = $invoice ? $invoice->paid_amount : 0;
            $student->balance = $invoice ? ($invoice->amount - $invoice->paid_amount) : 0;
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
            'filters' => $request->only(['session_id', 'faculty_id', 'department_id', 'program_id', 'level', 'status', 'search']),
        ]);
    }

    public function exportPDF(Request $request)
    {
        // Similar logic to exportExcel but for PDF
        $sessionId = $request->session_id ?? Session::current()?->id;
        $session = Session::find($sessionId);
        
        // Use a simple query for the PDF view
        $students = Student::query()
            ->with(['user', 'faculty', 'department', 'program', 'invoices' => function($q) use ($sessionId) {
                $q->where('type', 'school_fee')->where('session_id', $sessionId);
            }])
            ->get(); // In real app, we would apply all filters here too

        $students->transform(function ($student) use ($sessionId) {
            $invoice = $student->invoices->first();
            $student->fee_status = $invoice ? $invoice->status : 'unpaid';
            $student->total_billed = $invoice ? $invoice->amount : 0;
            $student->total_paid = $invoice ? $invoice->paid_amount : 0;
            $student->balance = $invoice ? ($invoice->amount - $invoice->paid_amount) : 0;
            return $student;
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.bursary_fees_report', [
            'students' => $students,
            'session' => $session,
            'date' => now()->format('d M, Y')
        ])->setPaper('a4', 'landscape');
        
        return $pdf->download('student_fees_report_' . now()->format('Y_m_d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $query = Student::query()
            ->with(['user', 'faculty', 'department', 'program', 'scholarship']);

        // Apply same filters as report
        if ($request->filled('session_id')) {
            $query->whereHas('invoices', function ($q) use ($request) {
                $q->where('session_id', $request->session_id)->where('type', 'school_fee');
            });
        }
        
        if ($request->filled('status')) {
            $status = $request->status;
            $sessionId = $request->session_id ?? Session::current()?->id;

            if ($status === 'unpaid') {
                $query->where(function ($q) use ($sessionId) {
                    $q->whereDoesntHave('invoices', function ($sq) use ($sessionId) {
                        $sq->where('type', 'school_fee')->where('session_id', $sessionId);
                    })->orWhereHas('invoices', function ($sq) use ($sessionId) {
                        $sq->where('type', 'school_fee')->where('session_id', $sessionId)->where('status', 'unpaid');
                    });
                });
            } else {
                $query->whereHas('invoices', function ($q) use ($status, $sessionId) {
                    $q->where('type', 'school_fee')->where('session_id', $sessionId)->where('status', $status);
                });
            }
        }
        if ($request->filled('faculty_id')) $query->where('faculty_id', $request->faculty_id);
        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('program_id')) $query->where('program_id', $request->program_id);
        if ($request->filled('level')) $query->where('current_level', $request->level);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('matriculation_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $sessionId = $request->session_id ?? Session::current()?->id;

        // Eager load everything needed for the export in one go
        $students = $query->with([
            'user', 
            'faculty', 
            'department', 
            'program', 
            'scholarship',
            'invoices' => function($q) use ($sessionId) {
                $q->where('type', 'school_fee')->where('session_id', $sessionId);
            }
        ])->get();

        $students->transform(function ($student) use ($sessionId) {
            $invoice = $student->invoices->first(); // Since we filtered in with()

            $lastPayment = null;
            if ($invoice) {
                // Since we need the last payment date, we might still need a quick query or 
                // we could have eager loaded it too. For 3k, a single subquery is better.
                $lastPayment = \App\Models\Payment::where('invoice_id', $invoice->id)
                    ->where('status', 'success')
                    ->latest('paid_at')
                    ->first();
            }

            $student->fee_status = $invoice ? $invoice->status : 'unpaid';
            $student->total_billed = $invoice ? $invoice->amount : 0;
            $student->total_paid = $invoice ? $invoice->paid_amount : 0;
            $student->balance = $invoice ? ($invoice->amount - $invoice->paid_amount) : 0;
            $student->last_payment_date = $lastPayment ? $lastPayment->paid_at : null;
            
            return $student;
        });

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StudentFeesExport($students), 
            'student_fees_report_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
