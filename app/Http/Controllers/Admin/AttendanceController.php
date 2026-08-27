<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Faculty;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AttendanceImport;
use App\Models\Session;
use App\Models\Semester;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Holiday;
use App\Services\AcademicCacheService;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with(['staff.user', 'staff.department.faculty', 'creator:id,name', 'updater:id,name']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        } else {
            $query->whereDate('date', now()->toDateString());
        }

        if ($request->filled('department_id')) {
            $query->whereHas('staff', fn($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->filled('status')) {
            $query->where('attendances.status', $request->status);
        }

        $sortBy = $request->input('sort_by', 'clock_in');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sortBy === 'name') {
            $query->join('staff', 'attendances.staff_id', '=', 'staff.id')
                  ->join('users', 'staff.user_id', '=', 'users.id')
                  ->orderBy('users.name', $sortDir)
                  ->select('attendances.*');
        } elseif ($sortBy === 'staff_number') {
            $query->join('staff', 'attendances.staff_id', '=', 'staff.id')
                  ->orderBy('staff.staff_number', $sortDir)
                  ->select('attendances.*');
        } elseif ($sortBy === 'clock_out') {
            $query->orderByRaw("attendances.clock_out IS NULL ASC, attendances.clock_out {$sortDir}");
        } else {
            // Default: clock_in of that day
            $query->orderByRaw("attendances.clock_in IS NULL ASC, attendances.clock_in {$sortDir}");
        }

        $attendances = $query->paginate(20)->withQueryString();

        $allStaff = Staff::whereHas('user', fn($q) => $q->where('is_active', true))
            ->with('user:id,name')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->user?->name ?? 'Unknown Staff',
                'staff_number' => $s->staff_number
            ]);

        $holiday = Holiday::whereDate('date', $request->date ?? now()->toDateString())->first();
        $holidays = Holiday::orderBy('date', 'desc')->get();

        return Inertia::render('Admin/HR/Attendance/Index', [
            'attendances' => $attendances,
            'allStaff' => $allStaff,
            'holiday' => $holiday,
            'holidays' => $holidays,
            'faculties' => AcademicCacheService::getAllFaculties(),
            'departments' => AcademicCacheService::getAllDepartments(),
            'filters' => array_merge(
                $request->only(['date', 'department_id', 'status', 'sort_by', 'sort_dir']),
                [
                    'sort_by' => $sortBy,
                    'sort_dir' => $sortDir,
                ]
            ),
        ]);
    }

    public function storeHoliday(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:holidays,date',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $holiday = Holiday::create($validated);

        activity('attendance')
            ->performedOn($holiday)
            ->causedBy(auth()->user())
            ->withProperties([
                'name' => $holiday->name,
                'date' => $holiday->date,
                'ip_address' => $request->ip(),
            ])
            ->log("Marked public holiday: {$holiday->name} on {$holiday->date}");

        return back()->with('success', 'Holiday marked successfully.');
    }

    public function updateHoliday(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:holidays,date,' . $holiday->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $oldData = $holiday->only(['name', 'date', 'description']);
        $holiday->update($validated);

        activity('attendance')
            ->performedOn($holiday)
            ->causedBy(auth()->user())
            ->withProperties([
                'old' => $oldData,
                'attributes' => $holiday->only(['name', 'date', 'description']),
                'ip_address' => $request->ip(),
            ])
            ->log("Updated public holiday: {$holiday->name} to date {$holiday->date}");

        return back()->with('success', 'Holiday updated successfully.');
    }

    public function destroyHoliday(Request $request, Holiday $holiday)
    {
        activity('attendance')
            ->performedOn($holiday)
            ->causedBy(auth()->user())
            ->withProperties([
                'name' => $holiday->name,
                'date' => $holiday->date,
                'ip_address' => $request->ip(),
            ])
            ->log("Removed public holiday: {$holiday->name} on {$holiday->date}");

        $holiday->delete();
        return back()->with('success', 'Holiday removed.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'date' => 'required|date',
            'clock_in' => 'nullable',
            'clock_out' => 'nullable',
            'status' => 'required|in:present,late,absent,on_leave',
            'notes' => 'nullable|string',
        ]);

        $attendance = Attendance::updateOrCreate(
            ['staff_id' => $validated['staff_id'], 'date' => $validated['date']],
            array_merge($validated, ['source' => 'manual'])
        );

        activity('attendance')
            ->performedOn($attendance)
            ->causedBy(auth()->user())
            ->withProperties([
                'staff_name' => $attendance->staff?->user?->name ?? 'Staff',
                'staff_number' => $attendance->staff?->staff_number,
                'date' => $validated['date'],
                'status' => $validated['status'],
                'clock_in' => $validated['clock_in'] ?? null,
                'clock_out' => $validated['clock_out'] ?? null,
                'ip_address' => $request->ip(),
            ])
            ->log("Created manual attendance record for staff {$attendance->staff?->user?->name} on {$validated['date']}");

        return back()->with('success', 'Attendance record saved successfully.');
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'clock_in' => 'nullable',
            'clock_out' => 'nullable',
            'status' => 'required|in:present,late,absent,on_leave',
            'notes' => 'nullable|string',
        ]);

        $oldData = [
            'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null,
            'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null,
            'status' => $attendance->status,
            'notes' => $attendance->notes,
        ];

        $attendance->update($validated);

        $newData = [
            'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null,
            'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null,
            'status' => $attendance->status,
            'notes' => $attendance->notes,
        ];

        activity('attendance')
            ->performedOn($attendance)
            ->causedBy(auth()->user())
            ->withProperties([
                'staff_name' => $attendance->staff?->user?->name ?? 'Staff',
                'staff_number' => $attendance->staff?->staff_number,
                'date' => $attendance->date?->format('Y-m-d'),
                'old' => $oldData,
                'attributes' => $newData,
                'ip_address' => $request->ip(),
            ])
            ->log("Updated attendance record for staff {$attendance->staff?->user?->name} on {$attendance->date?->format('Y-m-d')}");

        return back()->with('success', 'Attendance record updated successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'file' => 'required|file|extensions:csv,xls,xlsx|max:10240',
        ]);

        Excel::import(new AttendanceImport($request->date), $request->file('file'));

        activity('attendance')
            ->causedBy(auth()->user())
            ->withProperties([
                'date' => $request->date,
                'filename' => $request->file('file')->getClientOriginalName(),
                'ip_address' => $request->ip(),
            ])
            ->log("Imported bulk staff attendance log for date {$request->date}");

        return back()->with('success', 'Attendance imported successfully.');
    }

    public function destroy(Request $request, Attendance $attendance)
    {
        activity('attendance')
            ->performedOn($attendance)
            ->causedBy(auth()->user())
            ->withProperties([
                'staff_name' => $attendance->staff?->user?->name ?? 'Staff',
                'staff_number' => $attendance->staff?->staff_number,
                'date' => $attendance->date?->format('Y-m-d'),
                'status' => $attendance->status,
                'ip_address' => $request->ip(),
            ])
            ->log("Deleted attendance record for staff {$attendance->staff?->user?->name} on {$attendance->date?->format('Y-m-d')}");

        $attendance->delete();
        return back()->with('success', 'Attendance record removed.');
    }

    public function markAbsent(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $targetDate = $request->date;
        $activeStaff = Staff::whereHas('user', fn($q) => $q->where('is_active', true))->get();
        $count = 0;

        foreach ($activeStaff as $staff) {
            $exists = Attendance::where('staff_id', $staff->id)
                ->whereDate('date', $targetDate)
                ->exists();

            if (!$exists) {
                Attendance::create([
                    'staff_id' => $staff->id,
                    'date' => $targetDate,
                    'status' => 'absent',
                    'source' => 'manual',
                    'notes' => 'Marked absent by admin',
                ]);
                $count++;
            }
        }

        activity('attendance')
            ->causedBy(auth()->user())
            ->withProperties([
                'date' => $targetDate,
                'count' => $count,
                'ip_address' => $request->ip(),
            ])
            ->log("Marked {$count} unlogged active staff members as absent for date {$targetDate}");

        return back()->with('success', "Marked {$count} unlogged active staff members as absent.");
    }

    public function downloadTemplate()
    {
        $headers = ['staff_id', 'staff_name', 'department', 'clock_in', 'clock_out'];

        $staffMembers = Staff::whereHas('user', fn($q) => $q->where('is_active', true))
            ->with(['user', 'department'])
            ->get();

        $data = $staffMembers->map(function ($staff) {
            return [
                $staff->staff_number ?? $staff->id,
                $staff->user?->name ?? 'N/A',
                $staff->department?->name ?? 'N/A',
                '',
                '',
            ];
        })->toArray();

        if (empty($data)) {
            $data = [
                ['STF-001', 'John Doe', 'Computer Science', '', ''],
            ];
        }

        return Excel::download(new class($headers, $data) implements \Maatwebsite\Excel\Concerns\FromCollection {
            public function __construct(protected $headers, protected $data) {}
            public function collection() {
                return collect([$this->headers, ...$this->data]);
            }
        }, 'attendance_import_template_' . now()->format('Y_m_d') . '.xlsx');
    }

    public function reports(Request $request)
    {
        $stats = $this->getReportStats($request);

        return Inertia::render('Admin/HR/Attendance/Reports', [
            'stats' => $stats['data'],
            'sessions' => AcademicCacheService::getSessions(),
            'semesters' => Semester::orderBy('registration_starts_at', 'desc')->get(),
            'departments' => AcademicCacheService::getAllDepartments(),
            'reportTitle' => $stats['title'],
            'filters' => $request->all(),
        ]);
    }

    public function exportReport(Request $request)
    {
        $format = $request->input('format', 'excel');
        $stats = $this->getReportStats($request);

        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.attendance_report', [
                'stats' => $stats['data'],
                'title' => $stats['title'],
                'date' => now()->format('d M, Y')
            ])->setPaper('a4', 'landscape');
            
            return $pdf->download('attendance_report_' . now()->format('Y_m_d') . '.pdf');
        }

        return Excel::download(new class($stats['data']) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(protected $data) {}
            public function collection() {
                return $this->data->map(fn($s) => [
                    $s->staff?->staff_number ?? 'N/A',
                    $s->staff?->user?->name ?? 'Unknown',
                    $s->staff?->department?->name ?? 'N/A',
                    $s->total_days,
                    $s->present_count,
                    $s->late_count,
                    $s->absent_count,
                    $s->leave_count,
                    round(($s->present_count / ($s->total_days ?: 1)) * 100, 2) . '%'
                ]);
            }
            public function headings(): array {
                return ['Staff ID', 'Staff Name', 'Department', 'Total Days', 'Present', 'Late', 'Absent', 'On Leave', 'Attendance Rate'];
            }
        }, 'attendance_report_' . now()->format('Y_m_d') . '.xlsx');
    }

    private function getReportStats(Request $request)
    {
        $type = $request->input('type', 'monthly');
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        
        $query = Attendance::query();

        if ($type === 'monthly') {
            $query->whereMonth('date', $date->month)->whereYear('date', $date->year);
            $reportTitle = $date->format('F Y');
        } elseif ($type === 'weekly') {
            $query->whereBetween('date', [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()]);
            $reportTitle = "Week of " . $date->copy()->startOfWeek()->format('M d, Y');
        } elseif ($type === 'session' && $request->filled('session_id')) {
            $session = Session::findOrFail($request->session_id);
            $query->whereBetween('date', [$session->start_date, $session->end_date ?? now()]);
            $reportTitle = "Session: " . $session->name;
        } elseif ($type === 'semester' && $request->filled('semester_id')) {
            $semester = Semester::findOrFail($request->semester_id);
            $query->whereBetween('date', [$semester->registration_starts_at, $semester->registration_ends_at ?? now()]);
            $reportTitle = "Semester: " . $semester->name;
        } else {
            $query->whereMonth('date', now()->month)->whereYear('date', now()->year);
            $reportTitle = now()->format('F Y');
        }

        if ($request->filled('department_id')) {
            $query->whereHas('staff', fn($q) => $q->where('department_id', $request->department_id));
        }

        $data = $query->select(
            'staff_id',
            DB::raw('count(*) as total_days'),
            DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count'),
            DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count'),
            DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count'),
            DB::raw('SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as leave_count')
        )
        ->groupBy('staff_id')
        ->with(['staff.user', 'staff.department'])
        ->get();

        return ['data' => $data, 'title' => $reportTitle];
    }

    public function calendar(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        $month = $date->month;
        $year = $date->year;
        
        $daysInMonth = $date->daysInMonth;
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();

        $query = Staff::with('user:id,name', 'department:id,name');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $staffList = $query->get();

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('staff_id')
            ->map(function ($items) {
                return $items->keyBy(fn($i) => Carbon::parse($i->date)->format('Y-m-d'));
            });

        $holidays = Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn($h) => $h->date);

        return Inertia::render('Admin/HR/Attendance/Calendar', [
            'staffList' => $staffList,
            'attendances' => $attendances,
            'holidays' => $holidays,
            'daysInMonth' => $daysInMonth,
            'currentMonth' => $date->format('F Y'),
            'selectedDate' => $date->format('Y-m-d'),
            'departments' => AcademicCacheService::getAllDepartments(),
            'filters' => $request->only(['date', 'department_id']),
        ]);
    }
}
