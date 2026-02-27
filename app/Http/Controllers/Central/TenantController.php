<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with(['domains', 'subscriptions'])->latest();

        if ($request->filled('search')) {
            $query->where('school_name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $tenants = $query->paginate(9)->withQueryString();

        return Inertia::render('Central/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Central/Tenants/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'id' => 'required|string|unique:tenants,id|regex:/^[a-z0-9-]+$/|min:3',
            'domain' => 'required|string|unique:domains,domain',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'logo' => 'nullable|image|max:2048',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('tenant-logos', 'public');
        }

        $tenant = Tenant::create([
            'id' => $validated['id'],
            'school_name' => $validated['school_name'],
            'is_active' => true,
            'email' => $validated['email'],
            'address' => $validated['address'],
            'logo_path' => $logoPath,
            'admin_name' => $validated['admin_name'],
            'admin_email' => $validated['admin_email'],
            'admin_password_hash' => \Illuminate\Support\Facades\Hash::make($validated['admin_password']),
        ]);

        $tenant->domains()->create([
            'domain' => $validated['domain'],
        ]);

        // Explicitly seed the newly created tenant to guarantee the custom Admin is generated
        Artisan::call('tenants:seed', [
            '--tenants' => [$tenant->id],
        ]);

        return redirect()->route('central.tenants.index')->with('success', 'University (Tenant) created successfully! The database has been provisioned.');
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('domains');

        // Run queries inside the isolated tenant database context
        $tenantData = $tenant->run(function () {
            // These models will automatically use the correct tenant connection
            return [
                'insights' => [
                    'students_count' => \App\Models\Student::count(),
                    'staff_count' => \App\Models\Staff::count(),
                    'faculties_count' => \App\Models\Faculty::count(),
                    'departments_count' => \App\Models\Department::count(),
                    'courses_count' => \App\Models\Course::count(),
                    'applications_count' => \App\Models\Applicant::count(),
                ],
                'recent_data' => [
                    'students' => \App\Models\Student::with(['user', 'department.faculty', 'program'])
                        ->latest()
                        ->paginate(10)
                        ->withQueryString()
                        ->toArray(),
                    'applicants' => \App\Models\Applicant::with('user')->latest()->take(10)->get()->toArray(),
                    'faculties' => \App\Models\Faculty::latest()->take(10)->get()->toArray(),
                    'departments' => \App\Models\Department::with('faculty')->latest()->take(10)->get()->toArray(),
                    'programmes' => \App\Models\Programme::with('department.faculty')->latest()->take(10)->get()->toArray(),
                    'school_fees' => \App\Models\FeeConfiguration::with(['feeType', 'session', 'faculty', 'department', 'program'])->latest()->take(10)->get()->toArray(),
                ],
            ];
        });

        return Inertia::render('Central/Tenants/Show', [
            'tenant' => $tenant->load('subscriptions'),
            'insights' => $tenantData['insights'],
            'recentData' => $tenantData['recent_data'],
        ]);
    }

    public function storeSubscription(Tenant $tenant, \Illuminate\Http\Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'payment_reference' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = $startDate->copy()->addYear()->subDay();

        $tenant->subscriptions()->create([
            'amount' => $request->amount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_reference' => $request->payment_reference,
            'payment_method' => $request->payment_method,
            'notes' => $request->notes,
            'status' => 'active',
        ]);

        return back()->with('success', 'Subscription recorded successfully.');
    }

    public function update(Tenant $tenant, Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'address' => 'required|string|max:500',
            'logo' => 'nullable|image|max:2048',
        ]);

        $data = [
            'school_name' => $validated['school_name'],
            'email' => $validated['email'],
            'address' => $validated['address'],
        ];

        if ($request->hasFile('logo')) {
            // Delete old logo if it exists
            if ($tenant->logo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($tenant->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('tenant-logos', 'public');
        }

        $tenant->update($data);

        return back()->with('success', 'Polytechnic details updated successfully.');
    }

    public function showStudent(Tenant $tenant, $student_id)
    {
        Inertia::share([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->school_name,
                'logo' => $tenant->logo_path ? global_asset('storage/'.$tenant->logo_path) : null,
            ],
        ]);

        $student = $tenant->run(function () use ($student_id) {
            return \App\Models\Student::with([
                'user',
                'department.faculty',
                'program',
                'admittedSession',
                'registrations.course',
                'registrations.session',
                'registrations.semester',
                'oLevelResults',
                'sessions.session',
                'sessions.invoices.items',
                'state',
                'lga',
            ])->findOrFail($student_id)->toArray();
        });

        return Inertia::render('Central/Tenants/Students/Show', [
            'tenant' => $tenant,
            'student' => $student,
        ]);
    }

    public function toggleStatus(Tenant $tenant)
    {
        // Toggle the is_active property. Note: if it's null, default to false after toggle.
        $tenant->update([
            'is_active' => ! $tenant->is_active,
        ]);

        return back()->with('success', 'Tenant status updated successfully.');
    }
}
