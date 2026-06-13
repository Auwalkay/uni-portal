<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StudentAdmitted;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Programme;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Applicant::with(['user', 'programme']);

        // Stats
        $stats = [
            'total' => Applicant::count(),
            'admitted' => Applicant::where('status', 'admitted')->count(),
            'rejected' => Applicant::where('status', 'rejected')->count(),
            'pending' => Applicant::where('status', 'pending')->count(),
        ];

        // Filters
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%');
            })->orWhere('jamb_registration_number', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }

        $applicants = $query->latest()->paginate(10)->withQueryString();

        $programmes = Programme::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Admissions/Index', [
            'applicants' => $applicants,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'programme_id']),
            'programmes' => $programmes,
        ]);
    }

    public function show(Applicant $applicant)
    {
        $applicant->load([
            'user',
            'documents',
            'state',
            'lga',
            'programme.department.faculty',
        ]);

        $paymentInfo = Invoice::where('user_id', $applicant->user_id)
            ->where('type', 'application_fee')
            ->with('payments') // Load successful payments check?
            ->first();

        return Inertia::render('Admin/Admissions/Show', [
            'applicant' => $applicant,
            'payment_info' => $paymentInfo,
        ]);
    }

    public function update(Request $request, Applicant $applicant)
    {
        $request->validate([
            'status' => 'required|string|in:draft,submitted,screening,admitted,rejected',
        ]);

        $applicant->update([
            'status' => $request->status,
        ]);

        // Optional: acceptance fee generation
        $chargeAcceptanceFee = false; // Set to true/config to enable

        if ($request->status === 'admitted' && $chargeAcceptanceFee) {
            Invoice::firstOrCreate(
                [
                    'user_id' => $applicant->user_id,
                    'type' => 'acceptance_fee',
                ],
                [
                    'reference' => 'ACC-'.strtoupper(uniqid()),
                    'amount' => 50000.00,
                    'status' => 'pending',
                    'due_date' => now()->addWeeks(2),
                ]
            );

            // Send Admission Email
            Mail::to($applicant->user->email)->send(new StudentAdmitted($applicant));
            Log::info("Admission email queued for applicant: {$applicant->jamb_registration_number}");
        }

        return back()->with('success', 'Applicant status updated successfully.');
    }

    public function downloadLetter(Applicant $applicant)
    {
        if ($applicant->status !== 'admitted') {
            abort(403, 'Admission letter is only available for admitted applicants.');
        }

        $identifer = $applicant->jamb_registration_number ?? $applicant->application_number ?? 'Letter';
        $fileName = "Admission_Letter_{$identifer}.pdf";
        $filePath = "admission_letters/{$applicant->user_id}.pdf";

        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
            return \Illuminate\Support\Facades\Storage::disk('local')->download($filePath, $fileName, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);
        }

        $applicant->load(['user', 'programme.department.faculty', 'state', 'lga']);
        $currentSession = \App\Models\Session::current();
        
        // Calculate Fees for the Letter
        $feesData = $this->calculateEstimatedFeesForApplicant($applicant, $currentSession);

        $pdf = Pdf::loadView('documents.admission_letter', [
            'applicant' => $applicant,
            'faculty_name' => $applicant->programme?->department->faculty->name ?? 'N/A',
            'programme_name' => $applicant->programme?->name ?? 'N/A',
            'session_name' => $currentSession->name ?? '2025/2026',
            'fees' => $feesData,
        ])->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put($filePath, $pdf->output());

        return $pdf->download($fileName);
    }

    private function calculateEstimatedFeesForApplicant($applicant, $session)
    {
        if (!$session) return null;
        
        $program = $applicant->programme;
        $deptId = $program?->department_id;
        $facultyId = $program?->department?->faculty_id;

        $allConfigs = \App\Models\FeeConfiguration::where('session_id', $session->id)
            ->where(function ($q) {
                $q->where('level', '100')->orWhereNull('level');
            })
            ->where(function ($q) use ($applicant) {
                $q->where('entry_mode', $applicant->application_mode)->orWhereNull('entry_mode');
            })
            ->where('is_compulsory', true)
            ->with('feeType')
            ->get();

        $tuition = 0;
        $oneTimeFeesTotal = 0;
        $oneTimeFeesList = [];

        $grouped = $allConfigs->groupBy('fee_type_id');
        foreach ($grouped as $feeTypeId => $configs) {
            $resolved = $configs->where('program_id', $applicant->programme_id)->first()
                ?? $configs->where('department_id', $deptId)->whereNull('program_id')->first()
                ?? $configs->where('faculty_id', $facultyId)->whereNull('department_id')->whereNull('program_id')->first()
                ?? $configs->whereNull('faculty_id')->whereNull('department_id')->whereNull('program_id')->first();
            
            if ($resolved) {
                if ($resolved->feeType && $resolved->feeType->is_one_time) {
                    $oneTimeFeesTotal += $resolved->amount;
                    $oneTimeFeesList[] = [
                        'name' => $resolved->feeType->name,
                        'amount' => $resolved->amount
                    ];
                } else {
                    $tuition += $resolved->amount;
                }
            }
        }

        $adminCharge = \App\Models\SystemSetting::get('admin_charge_enabled', true) 
            ? \App\Models\SystemSetting::get('admin_charge_amount', 250000) : 0;
            
        // Calculate Discount based on Scholarship Coverage
        $discount = 0;
        $scholarship = $applicant->scholarship;
        if ($scholarship && ($applicant->programme?->scholarship_eligible ?? true)) {
            $baseForDiscount = $tuition;
            if ($adminCharge > 0 && $scholarship->covers_admin_charges) {
                $baseForDiscount += $adminCharge;
            }
            $discount = $baseForDiscount * ($scholarship->percentage / 100);
        }

        $total = $tuition + $adminCharge + $oneTimeFeesTotal;

        return [
            'tuition' => $tuition,
            'admin_charge' => $adminCharge,
            'one_time_fees' => $oneTimeFeesTotal,
            'one_time_fees_list' => $oneTimeFeesList,
            'discount' => $discount,
            'total' => $total - $discount,
            'scholarship_name' => $scholarship?->name
        ];
    }
}
