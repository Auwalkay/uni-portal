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

    private function prepareBatchFeeConfigs(?Session $session)
    {
        $sessionIds = \App\Models\FeeConfiguration::distinct()->pluck('session_id')->filter()->toArray();
        if ($session && !in_array($session->id, $sessionIds)) {
            $sessionIds[] = $session->id;
        }

        $allConfigs = \App\Models\FeeConfiguration::whereIn('session_id', $sessionIds)
            ->where('is_compulsory', true)
            ->with('feeType')
            ->get()
            ->groupBy('session_id');

        $adminChargeEnabled = \App\Models\SystemSetting::get('admin_charge_enabled', true);
        $adminChargeAmount = \App\Models\SystemSetting::get('admin_charge_amount', 250000);

        return [$allConfigs, $adminChargeEnabled, $adminChargeAmount];
    }

    public function studentFeesReport(Request $request)
    {
        $sessionId = null;
        $feeType = 'school_fee';
        $query = $this->getFilteredStudentsQuery($request, $sessionId, $feeType);
        $session = Session::find($sessionId) ?? Session::current();
        $feeService = app(\App\Services\Finance\FeeService::class);

        [$allConfigsBySession, $adminChargeEnabled, $adminChargeAmount] = $this->prepareBatchFeeConfigs($session);

        $query->with([
            'user:id,name', 
            'faculty:id,name', 
            'department:id,name', 
            'program:id,name,scholarship_eligible', 
            'academicDepartment:id,name', 
            'scholarship', 
            'invoices' => function($q) use ($sessionId, $feeType) {
                $q->where('session_id', $sessionId)->where('type', $feeType);
            }
        ]);

        $perPage = $request->query('per_page', 20);
        $students = $query->paginate($perPage)->withQueryString();

        // High-performance stats calculation across matching dataset using chunking (5K+ scaling)
        $totalStatsQuery = $this->getFilteredStudentsQuery($request, $sessionId, $feeType);
        
        $stats = [
            'total_billed' => 0,
            'total_paid' => 0,
            'total_balance' => 0,
            'student_count' => 0,
            'paid_count' => 0,
            'partial_count' => 0,
            'unpaid_count' => 0,
            'scholarship_count' => 0,
            'avg_fee_per_student' => 0,
            'avg_outstanding' => 0,
            'collection_rate' => 0,
        ];

        $totalStatsQuery->with([
            'invoices' => function($q) use ($sessionId, $feeType) {
                $q->where('session_id', $sessionId)->where('type', $feeType);
            }, 
            'scholarship', 
            'program:id,name,scholarship_eligible'
        ])->chunkById(1000, function ($studentsChunk) use ($feeType, $session, $feeService, $allConfigsBySession, $adminChargeEnabled, $adminChargeAmount, &$stats) {
            foreach ($studentsChunk as $student) {
                $stats['student_count']++;

                $invoice = $student->invoices->first();

                if ($invoice) {
                    $billed = (float)$invoice->amount;
                    $paid = (float)$invoice->paid_amount;
                    $status = $invoice->status;
                } else {
                    $billed = ($feeType === 'school_fee' && $session) 
                        ? $feeService->calculateExpectedSchoolFeeWithConfigs($student, $session, $allConfigsBySession, $adminChargeEnabled, $adminChargeAmount) 
                        : 0;
                    $paid = 0;
                    $status = 'unpaid';
                }
                
                $stats['total_billed'] += $billed;
                $stats['total_paid'] += $paid;
                $stats['total_balance'] += max(0, $billed - $paid);

                if ($student->scholarship_id) {
                    $stats['scholarship_count']++;
                }

                if ($status === 'paid') $stats['paid_count']++;
                elseif ($status === 'partial') $stats['partial_count']++;
                else $stats['unpaid_count']++;
            }
        }, 'students.id', 'id');

        $stats['collection_rate'] = $stats['total_billed'] > 0 
            ? round(($stats['total_paid'] / $stats['total_billed']) * 100, 1) 
            : 0;
        $stats['avg_fee_per_student'] = $stats['student_count'] > 0 
            ? round($stats['total_billed'] / $stats['student_count'], 2) 
            : 0;
        $unpaidOrPartialCount = $stats['unpaid_count'] + $stats['partial_count'];
        $stats['avg_outstanding'] = $unpaidOrPartialCount > 0 
            ? round($stats['total_balance'] / $unpaidOrPartialCount, 2) 
            : 0;

        // Load invoices for the selected session with their payments to avoid N+1 for paginated list (1 single query)
        $invoiceIds = $students->pluck('invoices')->flatten()->pluck('id')->filter()->values();
        $latestPaymentByInvoice = Payment::whereIn('invoice_id', $invoiceIds)
            ->where('status', 'success')
            ->orderByDesc('paid_at')
            ->get()
            ->groupBy('invoice_id')
            ->map(fn ($payments) => $payments->first());

        $students->getCollection()->transform(function ($student) use ($feeType, $session, $feeService, $allConfigsBySession, $adminChargeEnabled, $adminChargeAmount, $latestPaymentByInvoice) {
            $invoice = $student->invoices->first();
            $lastPayment = $invoice ? $latestPaymentByInvoice->get($invoice->id) : null;

            if ($invoice) {
                $billed = (float)$invoice->amount;
                $paid = (float)$invoice->paid_amount;
                $status = $invoice->status;
            } else {
                $billed = ($feeType === 'school_fee' && $session) 
                    ? $feeService->calculateExpectedSchoolFeeWithConfigs($student, $session, $allConfigsBySession, $adminChargeEnabled, $adminChargeAmount) 
                    : 0;
                $paid = 0;
                $status = 'unpaid';
            }

            $student->fee_status = $status;
            $student->fee_type = $feeType;
            $student->total_billed = $billed;
            $student->total_paid = $paid;
            $student->balance = max(0, $billed - $paid);
            $student->last_payment_date = $lastPayment ? $lastPayment->paid_at : null;

            return $student;
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
        $session = Session::find($sessionId) ?? Session::current();
        $feeService = app(\App\Services\Finance\FeeService::class);

        [$allConfigsBySession, $adminChargeEnabled, $adminChargeAmount] = $this->prepareBatchFeeConfigs($session);

        $students = $query->with([
            'user:id,name', 
            'faculty:id,name', 
            'department:id,name', 
            'program:id,name,scholarship_eligible', 
            'scholarship',
            'invoices' => function($q) use ($sessionId, $feeType) {
                $q->where('session_id', $sessionId)->where('type', $feeType);
            }
        ])->get();

        $students->transform(function ($student) use ($feeType, $session, $feeService, $allConfigsBySession, $adminChargeEnabled, $adminChargeAmount) {
            $invoice = $student->invoices->first();
            if ($invoice) {
                $billed = (float)$invoice->amount;
                $paid = (float)$invoice->paid_amount;
                $status = $invoice->status;
            } else {
                $billed = ($feeType === 'school_fee' && $session) 
                    ? $feeService->calculateExpectedSchoolFeeWithConfigs($student, $session, $allConfigsBySession, $adminChargeEnabled, $adminChargeAmount) 
                    : 0;
                $paid = 0;
                $status = 'unpaid';
            }

            $student->fee_status = $status;
            $student->fee_type = $feeType;
            $student->total_billed = $billed;
            $student->total_paid = $paid;
            $student->balance = max(0, $billed - $paid);
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
        $session = Session::find($sessionId) ?? Session::current();
        $feeService = app(\App\Services\Finance\FeeService::class);

        [$allConfigsBySession, $adminChargeEnabled, $adminChargeAmount] = $this->prepareBatchFeeConfigs($session);

        $students = $query->with([
            'user:id,name', 
            'faculty:id,name', 
            'department:id,name', 
            'program:id,name,scholarship_eligible', 
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

        $students->transform(function ($student) use ($latestPaymentByInvoice, $feeType, $session, $feeService, $allConfigsBySession, $adminChargeEnabled, $adminChargeAmount) {
            $invoice = $student->invoices->first();
            $lastPayment = $invoice ? $latestPaymentByInvoice->get($invoice->id) : null;

            if ($invoice) {
                $billed = (float)$invoice->amount;
                $paid = (float)$invoice->paid_amount;
                $status = $invoice->status;
            } else {
                $billed = ($feeType === 'school_fee' && $session) 
                    ? $feeService->calculateExpectedSchoolFeeWithConfigs($student, $session, $allConfigsBySession, $adminChargeEnabled, $adminChargeAmount) 
                    : 0;
                $paid = 0;
                $status = 'unpaid';
            }

            $student->fee_status = $status;
            $student->fee_type = $feeType;
            $student->total_billed = $billed;
            $student->total_paid = $paid;
            $student->balance = max(0, $billed - $paid);
            $student->last_payment_date = $lastPayment ? $lastPayment->paid_at : null;

            return $student;
        });

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StudentFeesExport($students), 
            'student_fees_report_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
