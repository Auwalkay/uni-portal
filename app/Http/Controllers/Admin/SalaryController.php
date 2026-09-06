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
use App\Services\AcademicCacheService;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $query = Staff::query()
            ->select('staff.*')
            ->join('users', 'users.id', '=', 'staff.user_id')
            ->leftJoin('departments', 'departments.id', '=', 'staff.department_id')
            ->with(['user', 'department']);

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('staff.staff_number', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        // Department Filter
        if ($request->filled('department_id') && $request->department_id !== 'all') {
            $query->where('staff.department_id', $request->department_id);
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'staff_number');
        $sortOrder = $request->query('sort_order', 'asc');

        if ($sortBy === 'name') {
            $query->orderBy('users.name', $sortOrder);
        } elseif ($sortBy === 'department') {
            $query->orderBy('departments.name', $sortOrder);
        } elseif ($sortBy === 'net') {
            $query->orderByRaw('(staff.basic_salary + staff.allowances + staff.bonuses - staff.deductions) ' . $sortOrder);
        } else {
            $query->orderBy('staff.staff_number', $sortOrder);
        }

        // Calculate Stats based on the filtered query (ignoring pagination limit)
        $statsQuery = clone $query;
        $totalCount = $statsQuery->count();
        $stats = [
            'totalBasic' => (double)$statsQuery->sum('staff.basic_salary'),
            'totalAllowances' => (double)$statsQuery->sum('staff.allowances'),
            'totalDeductions' => (double)$statsQuery->sum('staff.deductions'),
            'avgNet' => $totalCount > 0 
                ? (double)(($statsQuery->sum('staff.basic_salary') + $statsQuery->sum('staff.allowances') + $statsQuery->sum('staff.bonuses') - $statsQuery->sum('staff.deductions')) / $totalCount)
                : 0.0
        ];

        // Pagination
        $perPage = $request->query('per_page', 15);

        return Inertia::render('Admin/Finance/Salary/Index', [
            'staff' => $query->paginate($perPage)->withQueryString(),
            'departments' => AcademicCacheService::getAllDepartments(),
            'stats' => $stats,
            'filters' => [
                'search' => $request->query('search'),
                'department_id' => $request->query('department_id'),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
                'per_page' => (int)$perPage,
            ],
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
            'file' => 'required|file|extensions:csv,xls,xlsx',
        ]);

        try {
            Excel::import(new StaffSalaryImport, $request->file('file'));
            return back()->with('success', 'Staff salaries updated successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return back()->withErrors(['file' => $errors]);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Import error: ' . $e->getMessage()]);
        }
    }
}
