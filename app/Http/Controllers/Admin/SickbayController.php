<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SickbayItem;
use App\Models\SickbayVisit;
use App\Models\SickbayMedicalLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SickbayController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view_sickbay_portal');

        $activeVisits = SickbayVisit::with(['patient.student', 'patient.staff', 'attendant'])
            ->whereIn('status', ['waiting', 'under_observation'])
            ->latest()
            ->get();

        $stats = [
            'waiting_count' => SickbayVisit::where('status', 'waiting')->count(),
            'observation_count' => SickbayVisit::where('status', 'under_observation')->count(),
            'total_today' => SickbayVisit::whereDate('check_in_at', now()->toDateString())->count(),
            'low_stock_count' => SickbayItem::whereRaw('stock_quantity <= alert_threshold')->count(),
        ];

        $beds = \App\Models\SickbayBed::where('is_active', true)->get();
        $allSupplies = SickbayItem::all();

        return Inertia::render('Admin/Sickbay/Index', [
            'activeVisits' => $activeVisits,
            'stats' => $stats,
            'beds' => $beds,
            'allSupplies' => $allSupplies,
        ]);
    }

    public function bedsIndex(Request $request)
    {
        Gate::authorize('view_sickbay_portal');

        $beds = \App\Models\SickbayBed::where('is_active', true)->get();
        $activeVisits = SickbayVisit::with(['patient.student', 'patient.staff', 'attendant'])
            ->whereIn('status', ['waiting', 'under_observation'])
            ->get();

        return Inertia::render('Admin/Sickbay/Beds', [
            'beds' => $beds,
            'activeVisits' => $activeVisits,
        ]);
    }

    public function logsIndex(Request $request)
    {
        Gate::authorize('view_sickbay_portal');

        $completedVisits = SickbayVisit::with(['patient.student', 'patient.staff', 'attendant', 'medicalLog'])
            ->whereIn('status', ['treated', 'referred', 'discharged'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Sickbay/Logs', [
            'completedVisits' => $completedVisits,
        ]);
    }

    public function suppliesIndex(Request $request)
    {
        Gate::authorize('view_sickbay_portal');

        $supplies = SickbayItem::latest()->paginate(15)->withQueryString();
        $stats = [
            'low_stock_count' => SickbayItem::whereRaw('stock_quantity <= alert_threshold')->count(),
        ];

        return Inertia::render('Admin/Sickbay/Supplies', [
            'supplies' => $supplies,
            'stats' => $stats,
        ]);
    }

    public function patientsIndex(Request $request)
    {
        Gate::authorize('view_sickbay_portal');

        return Inertia::render('Admin/Sickbay/Patients');
    }

    public function reportsIndex(Request $request)
    {
        Gate::authorize('view_sickbay_portal');

        $startDate = $request->query('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $visitsQuery = SickbayVisit::whereBetween('check_in_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalCheckins = (clone $visitsQuery)->count();
        $referralCount = (clone $visitsQuery)->where('status', 'referred')->count();
        $bedAdmissionsCount = (clone $visitsQuery)->whereNotNull('bed_number')->count();

        // Breakdown by patient type (student / staff)
        $studentCount = (clone $visitsQuery)->whereHas('patient', function ($q) {
            $q->has('student');
        })->count();
        $staffCount = (clone $visitsQuery)->whereHas('patient', function ($q) {
            $q->has('staff');
        })->count();

        // Breakdown by visit severity
        $visitTypes = [
            'walk_in' => (clone $visitsQuery)->where('visit_type', 'walk_in')->count(),
            'appointment' => (clone $visitsQuery)->where('visit_type', 'appointment')->count(),
            'emergency' => (clone $visitsQuery)->where('visit_type', 'emergency')->count(),
        ];

        // Top Dispensed Supplies
        $visitsWithMeds = (clone $visitsQuery)->whereHas('medicalLog', function ($q) {
            $q->whereNotNull('medicines_dispensed');
        })->with('medicalLog')->get();

        $medsSummary = [];
        foreach ($visitsWithMeds as $v) {
            $dispensed = $v->medicalLog->medicines_dispensed;
            if (is_array($dispensed)) {
                foreach ($dispensed as $item) {
                    $name = $item['name'] ?? 'Unknown';
                    $qty = intval($item['quantity'] ?? 0);
                    if ($qty > 0) {
                        $medsSummary[$name] = ($medsSummary[$name] ?? 0) + $qty;
                    }
                }
            }
        }
        arsort($medsSummary);
        $topDispensed = [];
        foreach (array_slice($medsSummary, 0, 10, true) as $name => $qty) {
            $topDispensed[] = ['name' => $name, 'quantity' => $qty];
        }

        return Inertia::render('Admin/Sickbay/Reports', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'reportData' => [
                'total_checkins' => $totalCheckins,
                'referrals_count' => $referralCount,
                'bed_admissions_count' => $bedAdmissionsCount,
                'patient_breakdown' => [
                    'students' => $studentCount,
                    'staff' => $staffCount,
                ],
                'visit_types' => $visitTypes,
                'top_dispensed' => $topDispensed,
            ]
        ]);
    }

    public function prescriptionSlip(SickbayVisit $visit)
    {
        Gate::authorize('view_sickbay_portal');

        $visit->load(['patient.student', 'patient.staff', 'attendant', 'medicalLog']);

        return Inertia::render('Admin/Sickbay/Prescription', [
            'visit' => $visit,
        ]);
    }

    public function searchStudents(Request $request)
    {
        Gate::authorize('register_walk_in');

        $search = $request->query('query');
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $users = User::where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('student', function ($sq) use ($search) {
                      $sq->where('matriculation_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('staff', function ($sq) use ($search) {
                      $sq->where('staff_number', 'like', "%{$search}%");
                  });
            })
            ->where(function ($q) {
                $q->has('student')->orHas('staff');
            })
            ->with(['student', 'staff'])
            ->take(20)
            ->get()
            ->map(function ($u) {
                $isStudent = $u->student !== null;
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'type' => $isStudent ? 'Student' : 'Staff',
                    'matriculation_number' => $isStudent ? $u->student->matriculation_number : ($u->staff ? $u->staff->staff_number : ''),
                    'parent_name' => $isStudent ? $u->student->next_of_kin_name : ($u->staff ? $u->staff->next_of_kin_name : ''),
                    'parent_phone' => $isStudent ? $u->student->next_of_kin_phone : ($u->staff ? $u->staff->next_of_kin_phone : ''),
                    'parent_address' => $isStudent ? $u->student->next_of_kin_address : ($u->staff ? $u->staff->next_of_kin_address : ''),
                ];
            });

        return response()->json($users);
    }

    public function registerPatient(Request $request)
    {
        Gate::authorize('register_walk_in');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'symptoms' => 'required|string|max:255',
            'visit_type' => 'required|in:walk_in,appointment,emergency',
            'bed_number' => 'nullable|string|max:50',
        ]);

        $checkIn = [
            'user_id' => $validated['user_id'],
            'attended_by' => auth()->id(),
            'check_in_at' => now(),
            'symptoms' => $validated['symptoms'],
            'visit_type' => $validated['visit_type'],
            'status' => 'waiting',
        ];

        if (!empty($validated['bed_number'])) {
            $checkIn['bed_number'] = $validated['bed_number'];
            $checkIn['admitted_to_bed_at'] = now();
            $checkIn['status'] = 'under_observation';
        }

        SickbayVisit::create($checkIn);

        return redirect()->back()->with('success', 'Patient checked in successfully.');
    }

    public function updateVitalsAndTreatment(Request $request, SickbayVisit $visit)
    {
        Gate::authorize('write_sickbay_medical_logs');

        $validated = $request->validate([
            'blood_pressure' => 'nullable|string|max:20',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'weight' => 'nullable|numeric|min:0',
            'findings' => 'required|string',
            'treatment_given' => 'required|string',
            'medicines_dispensed' => 'nullable|array',
            'parent_contacted' => 'required|boolean',
            'parent_contact_notes' => 'nullable|string',
            'referral_hospital' => 'nullable|string|max:255',
            'referral_notes' => 'nullable|string',
            'recommended_tests' => 'nullable|array',
            'external_prescriptions' => 'nullable|array',
            'discharge_patient' => 'nullable|boolean',
        ]);

        try {
            DB::transaction(function () use ($validated, $visit) {
                // Determine final status
                $status = 'treated';
                if (!empty($validated['referral_hospital'])) {
                    $status = 'referred';
                } elseif ($visit->status === 'under_observation') {
                    if (!empty($validated['discharge_patient'])) {
                        $status = 'discharged';
                    } else {
                        $status = 'under_observation'; // Keep in bed unless discharged
                    }
                }

                // If check out is immediate
                $checkOutAt = null;
                if ($status !== 'under_observation') {
                    $checkOutAt = now();
                }

                $visit->update([
                    'status' => $status,
                    'check_out_at' => $checkOutAt,
                ]);

                // Create or update log
                SickbayMedicalLog::updateOrCreate(
                    ['sickbay_visit_id' => $visit->id],
                    [
                        'blood_pressure' => $validated['blood_pressure'],
                        'temperature' => $validated['temperature'],
                        'weight' => $validated['weight'],
                        'findings' => $validated['findings'],
                        'treatment_given' => $validated['treatment_given'],
                        'medicines_dispensed' => $validated['medicines_dispensed'],
                        'parent_contacted' => $validated['parent_contacted'],
                        'parent_contacted_at' => $validated['parent_contacted'] ? now() : null,
                        'parent_contact_notes' => $validated['parent_contact_notes'],
                        'referral_hospital' => $validated['referral_hospital'],
                        'referral_notes' => $validated['referral_notes'],
                        'recommended_tests' => $validated['recommended_tests'] ?? null,
                        'external_prescriptions' => $validated['external_prescriptions'] ?? null,
                    ]
                );

                // Deduct items from inventory
                if (!empty($validated['medicines_dispensed'])) {
                    foreach ($validated['medicines_dispensed'] as $dispensed) {
                        if (!empty($dispensed['id']) && !empty($dispensed['quantity'])) {
                            $item = SickbayItem::find($dispensed['id']);
                            if ($item) {
                                $item->decrement('stock_quantity', intval($dispensed['quantity']));
                            }
                        }
                    }
                }
            });

            return redirect()->back()->with('success', 'Treatment log updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save treatment details: ' . $e->getMessage());
        }
    }

    public function assignBed(Request $request, SickbayVisit $visit)
    {
        Gate::authorize('manage_observation_beds');

        $validated = $request->validate([
            'bed_number' => 'required|string|max:50',
        ]);

        $visit->update([
            'bed_number' => $validated['bed_number'],
            'admitted_to_bed_at' => now(),
            'status' => 'under_observation',
        ]);

        return redirect()->back()->with('success', 'Patient assigned to Bed ' . $validated['bed_number']);
    }

    public function dischargeBed(SickbayVisit $visit)
    {
        Gate::authorize('manage_observation_beds');

        $visit->update([
            'status' => 'discharged',
            'check_out_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Patient discharged from Bed ' . $visit->bed_number);
    }

    public function storeInventory(Request $request)
    {
        Gate::authorize('manage_sickbay_inventory');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'stock_quantity' => 'required|integer|min:0',
            'alert_threshold' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        SickbayItem::create($validated);

        return redirect()->back()->with('success', 'Inventory item added successfully.');
    }

    public function patientHistory(Request $request, User $user)
    {
        Gate::authorize('view_sickbay_portal');

        $visits = SickbayVisit::with(['attendant', 'medicalLog'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['treated', 'referred', 'discharged'])
            ->latest()
            ->get();

        return response()->json($visits);
    }

    public function storeBed(Request $request)
    {
        Gate::authorize('manage_observation_beds');

        $validated = $request->validate([
            'name' => 'required|string|unique:sickbay_beds,name|max:50',
            'description' => 'nullable|string|max:255',
        ]);

        \App\Models\SickbayBed::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Bed added successfully.');
    }
}
