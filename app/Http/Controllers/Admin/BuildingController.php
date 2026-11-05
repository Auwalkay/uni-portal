<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\BuildingImport;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BuildingController extends Controller
{
    public const BUILDING_TYPES = [
        ['value' => 'classroom', 'label' => 'Classroom', 'has_seating' => true],
        ['value' => 'lecture_theatre', 'label' => 'Lecture Theatre', 'has_seating' => true],
        ['value' => 'auditorium', 'label' => 'Auditorium', 'has_seating' => true],
        ['value' => 'multipurpose_hall', 'label' => 'Multipurpose Hall', 'has_seating' => true],
        ['value' => 'e_library', 'label' => 'E-Library / CBT Center', 'has_seating' => true],
        ['value' => 'laboratory', 'label' => 'Laboratory / Workshop', 'has_seating' => true],
        ['value' => 'admin_block', 'label' => 'Administrative Block', 'has_seating' => false],
        ['value' => 'office', 'label' => 'Faculty / Staff Office', 'has_seating' => false],
        ['value' => 'library', 'label' => 'Main Library', 'has_seating' => true],
        ['value' => 'hostel', 'label' => 'Hostel / Residential Hall', 'has_seating' => false],
        ['value' => 'clinic', 'label' => 'Clinic / Health Center', 'has_seating' => false],
        ['value' => 'sports_complex', 'label' => 'Sports & Recreation Complex', 'has_seating' => false],
        ['value' => 'maintenance', 'label' => 'Maintenance & Utility', 'has_seating' => false],
        ['value' => 'other', 'label' => 'Other Facility', 'has_seating' => false],
    ];

    public function index(Request $request)
    {
        $this->authorizePermission(['view_buildings', 'manage_buildings']);

        $query = Building::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('building_type')) {
            $query->where('building_type', $request->building_type);
        }

        if ($request->filled('usable_for_exams')) {
            $usable = filter_var($request->usable_for_exams, FILTER_VALIDATE_BOOLEAN);
            $query->where('usable_for_exams', $usable);
        }

        $buildings = $query->orderBy('name')->paginate(15)->withQueryString();

        $stats = [
            'total_buildings' => Building::count(),
            'total_capacity' => Building::where('status', 'active')->whereNotNull('capacity')->sum('capacity'),
            'exam_usable_count' => Building::usableForExams()->where('status', 'active')->count(),
            'active_count' => Building::where('status', 'active')->count(),
        ];

        return Inertia::render('Admin/Buildings/Index', [
            'buildings' => $buildings,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'building_type', 'usable_for_exams']),
            'building_types' => self::BUILDING_TYPES,
            'userPermissions' => [
                'canCreate' => $request->user()->can('create_buildings') || $request->user()->can('manage_buildings'),
                'canEdit' => $request->user()->can('edit_buildings') || $request->user()->can('manage_buildings'),
                'canDisable' => $request->user()->can('disable_buildings') || $request->user()->can('manage_buildings'),
                'canDelete' => $request->user()->can('delete_buildings') || $request->user()->can('manage_buildings'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission(['create_buildings', 'manage_buildings']);

        if ($request->filled('code')) {
            $request->merge(['code' => mb_strtoupper(trim($request->code))]);
        } else {
            $request->merge(['code' => Building::generateUniqueCode($request->name ?? 'BUILDING')]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:buildings,code',
            'building_type' => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:0',
            'usable_for_exams' => 'required|boolean',
            'status' => 'required|string|in:active,under_maintenance,inactive',
            'description' => 'nullable|string|max:1000',
        ]);

        Building::create($validated);

        return back()->with('success', "Building '{$validated['name']}' created successfully.");
    }

    public function update(Request $request, Building $building)
    {
        $this->authorizePermission(['edit_buildings', 'manage_buildings']);

        if ($request->filled('code')) {
            $request->merge(['code' => mb_strtoupper(trim($request->code))]);
        } else {
            $request->merge(['code' => Building::generateUniqueCode($request->name ?? 'BUILDING', $building->id)]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('buildings', 'code')->ignore($building->id)],
            'building_type' => 'required|string|max:50',
            'capacity' => 'nullable|integer|min:0',
            'usable_for_exams' => 'required|boolean',
            'status' => 'required|string|in:active,under_maintenance,inactive',
            'description' => 'nullable|string|max:1000',
        ]);

        $building->update($validated);

        return back()->with('success', "Building '{$building->name}' updated successfully.");
    }

    public function destroy(Building $building)
    {
        $this->authorizePermission(['delete_buildings', 'manage_buildings']);

        $name = $building->name;
        $building->delete();

        return back()->with('success', "Building '{$name}' deleted successfully.");
    }

    public function toggleStatus(Building $building)
    {
        $this->authorizePermission(['disable_buildings', 'manage_buildings']);

        $building->status = $building->status === 'active' ? 'inactive' : 'active';
        $building->save();

        return back()->with('success', "Building status set to '{$building->status}'.");
    }

    public function toggleExamStatus(Building $building)
    {
        $this->authorizePermission(['edit_buildings', 'manage_buildings']);

        $building->usable_for_exams = ! $building->usable_for_exams;
        $building->save();

        $statusText = $building->usable_for_exams ? 'eligible for examinations' : 'no longer used for examinations';

        return back()->with('success', "Building '{$building->name}' is now {$statusText}.");
    }

    public function import(Request $request)
    {
        $this->authorizePermission(['create_buildings', 'manage_buildings']);

        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        try {
            Excel::import(new BuildingImport, $request->file('file'));

            return back()->with('success', 'Campus buildings imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing buildings: '.$e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $this->authorizePermission(['view_buildings', 'manage_buildings']);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campus_buildings_import_template.csv"',
        ];

        $columns = ['name', 'code', 'building_type', 'capacity', 'usable_for_exams', 'status', 'description'];

        $sampleData = [
            [
                'name' => 'Science Lecture Theatre 2',
                'code' => 'SLT-2',
                'building_type' => 'lecture_theatre',
                'capacity' => '150',
                'usable_for_exams' => 'true',
                'status' => 'active',
                'description' => 'Main lecture hall near Faculty of Science.',
            ],
            [
                'name' => 'General Purpose Hall C',
                'code' => '',
                'building_type' => 'multipurpose_hall',
                'capacity' => '300',
                'usable_for_exams' => 'true',
                'status' => 'active',
                'description' => 'Large hall with central air conditioning.',
            ],
            [
                'name' => 'Senate Administrative Complex',
                'code' => '',
                'building_type' => 'admin_block',
                'capacity' => '',
                'usable_for_exams' => 'false',
                'status' => 'active',
                'description' => 'University central administration, council chambers, and registry.',
            ],
        ];

        $callback = function () use ($columns, $sampleData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function authorizePermission(array $permissions)
    {
        $user = auth()->user();
        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        abort(403, 'Unauthorized action.');
    }
}
