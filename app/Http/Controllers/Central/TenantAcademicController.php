<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Tenant;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AcademicImport;

class TenantAcademicController extends Controller
{
    public function index(Tenant $tenant)
    {
        // Load the academic hierarchy dynamically from the tenant's isolated database
        $academics = $tenant->run(function () {
            return \App\Models\Faculty::with('departments.programmes')->get()->toArray();
        });

        return Inertia::render('Central/Tenants/Academics', [
            'tenant' => $tenant,
            'academics' => $academics,
        ]);
    }

    public function storeFaculty(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
        ]);

        $tenant->run(function () use ($validated) {
            \App\Models\Faculty::create([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'Faculty created successfully in tenant database.');
    }

    public function storeDepartment(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|uuid',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
        ]);

        $tenant->run(function () use ($validated) {
            $faculty = \App\Models\Faculty::findOrFail($validated['faculty_id']);
            $faculty->departments()->create([
                'name' => $validated['name'],
                'code' => strtoupper($validated['code']),
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'Department created successfully in tenant database.');
    }

    public function storeProgramme(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'department_id' => 'required|uuid',
            'name' => 'required|string|max:255',
            'duration' => 'required|integer|min:1|max:10',
            'award' => 'required|string|max:255', // e.g. ND, HND, BSc
        ]);

        $tenant->run(function () use ($validated) {
            $department = \App\Models\Department::findOrFail($validated['department_id']);
            $department->programmes()->create([
                'name' => $validated['name'],
                'duration' => $validated['duration'],
                'award' => $validated['award'],
                'is_active' => true,
            ]);
        });

        return back()->with('success', 'Programme created successfully in tenant database.');
    }

    public function bulkUpload(Request $request, Tenant $tenant)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
        ]);

        $import = new AcademicImport();

        try {
            $tenant->run(function () use ($import, $request) {
                Excel::import($import, $request->file('file'));
            });

            return back()->with('success', $import->getProcessedCount() . ' academic items imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
