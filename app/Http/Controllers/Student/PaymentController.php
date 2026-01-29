<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected $paystack;

    public function __construct(PaystackService $paystack)
    {
        $this->paystack = $paystack;
    }

    public function index()
    {
        $invoices = Invoice::where('user_id', Auth::id())
            ->with([
                'items',
                'session',
                'payments' => function ($query) {
                    $query->where('status', 'success');
                }
            ])
            ->latest()
            ->get();

        $currentSession = \App\Models\Session::current();
        $canGenerateInvoice = false;

        if ($currentSession) {
            $hasInvoice = Invoice::where('user_id', Auth::id())
                ->where('type', 'school_fee')
                ->where('session_id', $currentSession->id)
                ->exists();

            $canGenerateInvoice = !$hasInvoice;
        }

        return Inertia::render('Student/Finance/Index', [
            'invoices' => $invoices,
            'canGenerateInvoice' => $canGenerateInvoice
        ]);
    }

    public function pay(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Invoice already paid.');
        }

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => Auth::id(),
            'gateway_reference' => 'TEMP-' . uniqid(), // Temporary ref
            'amount' => $invoice->amount,
            'status' => 'pending',
        ]);

        // We actually use the Paystack Reference as gateway_reference if initializing.
        // Paystack generates one or acts on ours.
        // Let's generate ours: "PAY-" . uniqid()
        $reference = 'PAY-' . strtoupper(uniqid());
        $payment->update(['gateway_reference' => $reference]);

        $data = $this->paystack->initializeTransaction(
            Auth::user()->email,
            $invoice->amount,
            $reference,
            route('student.payments.callback')
        );

        if ($data && isset($data['authorization_url'])) {
            return Inertia::location($data['authorization_url']);
        }

        return back()->with('error', 'Payment initialization failed.');
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return redirect()->route('student.payments.index')->with('error', 'No reference supplied.');
        }

        $data = $this->paystack->verifyTransaction($reference);

        if ($data && $data['status'] === 'success') {
            $payment = Payment::where('gateway_reference', $reference)->first();

            if ($payment && $payment->status !== 'success') {
                $payment->update([
                    'status' => 'success',
                    'channel' => $data['channel'],
                    'paid_at' => now(),
                ]);

                $payment->invoice->update(['status' => 'paid']);

                if ($payment->invoice->type === 'acceptance_fee') {
                    $applicant = \App\Models\Applicant::where('user_id', $payment->user_id)
                        ->with('programme.department.faculty')
                        ->first();

                    if ($applicant) {
                        app(\App\Services\EnrollmentService::class)->enroll($applicant, $payment->user_id);
                    }
                }
            }

            return redirect()->route('student.payments.index')->with('success', 'Payment successful! Welcome to the University.');
        }

        return redirect()->route('student.payments.index')->with('error', 'Payment verification failed.');
    }
    public function createSchoolFeeInvoice()
    {
        $user = Auth::user();
        $student = \App\Models\Student::where('user_id', $user->id)->firstOrFail();

        // Check for pending SCHOOL FEE invoice
        $pendingInvoice = Invoice::where('user_id', $user->id)
            ->where('type', 'school_fee')
            ->where('status', 'pending')
            ->first();

        if ($pendingInvoice) {
            return redirect()->route('student.payments.index')->with('info', 'You already have a pending invoice.');
        }

        // Get current session
        $currentSession = \App\Models\Session::current();
        if (!$currentSession) {
            return back()->with('error', 'No active academic session found.');
        }

        // Resolve IDs for Student Fields (assuming stored as strings or potential future IDs)
        // We try to find matching Faculty, Dept, Program by Name if UUID not found
        // Since we haven't migrated Student to use UUIDs yet, we do best effort lookup.
        $facultyId = null;
        if ($student->faculty) {
            $faculty = \App\Models\Faculty::where('name', $student->faculty)->orWhere('id', $student->faculty)->first();
            $facultyId = $faculty?->id;
        }

        $departmentId = null;
        if ($student->department) {
            $department = \App\Models\Department::where('name', $student->department)->orWhere('id', $student->department)->first();
            $departmentId = $department?->id;
        }

        $programId = null;
        if ($student->program) {
            $program = \App\Models\Programme::where('name', $student->program)->orWhere('id', $student->program)->first();
            $programId = $program?->id;
        }

        // Fetch Applicable Fees
        $configs = \App\Models\FeeConfiguration::where('session_id', $currentSession->id)
            ->where(function ($query) use ($facultyId, $departmentId, $programId, $student) {
                // Global Fees
                $query->where(function ($q) {
                    $q->whereNull('faculty_id')
                        ->whereNull('department_id')
                        ->whereNull('program_id');
                });

                // Faculty Fees
                if ($facultyId) {
                    $query->orWhere(function ($q) use ($facultyId) {
                        $q->where('faculty_id', $facultyId)
                            ->whereNull('department_id')
                            ->whereNull('program_id');
                    });
                }

                // Department Fees
                if ($departmentId) {
                    $query->orWhere(function ($q) use ($departmentId) {
                        $q->where('department_id', $departmentId)
                            ->whereNull('program_id');
                    });
                }

                // Program Fees
                if ($programId) {
                    $query->orWhere(function ($q) use ($programId) {
                        $q->where('program_id', $programId);
                    });
                }
            })
            // Filter by Level (Exact match or Null/Global level?) 
            // Usually level fees are specific. If level is null, it applies to all levels.
            ->where(function ($q) use ($student) {
                $q->where('level', $student->current_level)
                    ->orWhereNull('level');
            })
            ->with('feeType')
            ->get();


        if ($configs->isEmpty()) {
            // Fallback or Error? 
            // If no fees configured, maybe error out to avoid zero-invoices?
            // Or allow zero invoice? 
            return back()->with('error', 'No fee configuration found for your level/department. Please contact support.');
        }

        $totalAmount = $configs->sum('amount');

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'session_id' => $currentSession->id,
            'type' => 'school_fee',
            'reference' => 'SCH-' . strtoupper(uniqid()),
            'amount' => $totalAmount,
            'status' => 'pending',
            'due_date' => now()->addWeeks(4),
        ]);

        // Create Invoice Items
        foreach ($configs as $config) {
            \App\Models\InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_type_id' => $config->fee_type_id,
                'description' => $config->feeType->name ?? 'Fee',
                'amount' => $config->amount,
            ]);
        }

        return redirect()->route('student.payments.index')->with('success', 'School Fee invoice generated successfully.');
    }
}
