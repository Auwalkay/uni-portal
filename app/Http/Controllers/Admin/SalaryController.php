<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Exports\StaffSalaryExport;
use App\Imports\StaffSalaryImport;
use Maatwebsite\Excel\Facades\Excel;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $query = Staff::with(['user', 'department']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        return Inertia::render('Admin/Finance/Salary/Index', [
            'staff' => $query->paginate(15)->withQueryString(),
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only(['search', 'department_id']),
        ]);
    }

    public function update(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'required|numeric|min:0',
            'deductions' => 'required|numeric|min:0',
            'bonuses' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:20',
            'account_name' => 'nullable|string|max:255',
        ]);

        $staff->update($validated);

        return back()->with('success', 'Salary details updated.');
    }

    public function export()
    {
        return Excel::download(new StaffSalaryExport, 'staff_salaries.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        Excel::import(new StaffSalaryImport, $request->file('file'));

        return back()->with('success', 'Staff salaries updated successfully.');
    }
}
