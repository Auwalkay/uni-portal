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
use Illuminate\Support\Facades\Cache;
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
            ->take(100)
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
            'departmentSummary' => $stats['departmentSummary'],
            'atRiskStaff' => $stats['atRiskStaff'],
            'overallStats' => $stats['overallStats'],
            'dateRange' => [
                'start' => $stats['dateRange'][0]->format('Y-m-d'),
                'end' => $stats['dateRange'][1]->format('Y-m-d'),
            ],
            'sessions' => AcademicCacheService::getSessions(),
            'semesters' => Semester::orderBy('registration_starts_at', 'desc')->get(),
            'departments' => AcademicCacheService::getAllDepartments(),
            'reportTitle' => $stats['title'],
            'filters' => $request->all(),
        ]);
    }

    public function staffHistory(Request $request, Staff $staff)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Attendance::where('staff_id', $staff->id);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } else {
            $query->whereMonth('date', now()->month)->whereYear('date', now()->year);
        }

        $records = $query->orderBy('date', 'desc')->get()->map(function ($rec) {
            $hours = 0;
            if ($rec->clock_in && $rec->clock_out) {
                $start = Carbon::parse($rec->clock_in);
                $end = Carbon::parse($rec->clock_out);
                $hours = round($start->diffInMinutes($end) / 60, 1);
            }
            $rec->formatted_hours = $hours > 0 ? "{$hours} hrs" : '---';
            $rec->formatted_date = Carbon::parse($rec->date)->format('D, d M Y');
            $rec->formatted_clock_in = $rec->clock_in ? Carbon::parse($rec->clock_in)->format('h:i A') : '---';
            $rec->formatted_clock_out = $rec->clock_out ? Carbon::parse($rec->clock_out)->format('h:i A') : '---';
            return $rec;
        });

        $staff->load('user', 'department');

        return response()->json([
            'staff' => $staff,
            'records' => $records,
        ]);
    }

    public function exportReport(Request $request)
    {
        $format = $request->input('format', 'excel');
        $stats = $this->getReportStats($request);

        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('documents.attendance_report', [
                'stats' => $stats['data'],
                'overallStats' => $stats['overallStats'],
                'departmentSummary' => $stats['departmentSummary'],
                'title' => $stats['title'],
                'date' => now()->format('d M, Y')
            ])->setPaper('a4', 'landscape');
            
            return $pdf->download('attendance_report_' . now()->format('Y_m_d') . '.pdf');
        }

        return Excel::download(new class($stats['data'], $stats['overallStats']) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function __construct(protected $data, protected $overallStats) {}
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
                    $s->avg_clock_in ?? 'N/A',
                    $s->total_hours_formatted ?? 'N/A',
                    $s->punctuality_rate . '%',
                    $s->rate . '%'
                ]);
            }
            public function headings(): array {
                return [
                    'Staff ID', 
                    'Staff Name', 
                    'Department', 
                    'Total Recorded Days', 
                    'Present (On Time)', 
                    'Late', 
                    'Absent', 
                    'On Leave', 
                    'Average Clock-In', 
                    'Total Hours Worked', 
                    'Punctuality Rate', 
                    'Attendance Rate'
                ];
            }
        }, 'attendance_report_' . now()->format('Y_m_d') . '.xlsx');
    }

    private function getReportStats(Request $request)
    {
        $type = $request->input('type', 'monthly');
        $date = $request->filled('date') ? Carbon::parse($request->date) : now();
        
        $cacheKey = 'att_rep_' . md5(json_encode([
            't' => $type,
            'd' => $date->format('Y-m-d'),
            's' => $request->session_id,
            'sem' => $request->semester_id,
            'dept' => $request->department_id,
        ]));

        return Cache::remember($cacheKey, 300, function () use ($request, $type, $date) {
            $query = Attendance::query();

            if ($type === 'monthly') {
                $dateRange = [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
                $query->whereBetween('date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')]);
                $reportTitle = $date->format('F Y');
            } elseif ($type === 'weekly') {
                $dateRange = [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()];
                $query->whereBetween('date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')]);
                $reportTitle = "Week of " . $dateRange[0]->format('M d, Y') . " - " . $dateRange[1]->format('M d, Y');
            } elseif ($type === 'session' && $request->filled('session_id')) {
                $session = Session::findOrFail($request->session_id);
                $dateRange = [Carbon::parse($session->start_date), $session->end_date ? Carbon::parse($session->end_date) : now()];
                $query->whereBetween('date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')]);
                $reportTitle = "Session: " . $session->name;
            } elseif ($type === 'semester' && $request->filled('semester_id')) {
                $semester = Semester::findOrFail($request->semester_id);
                $dateRange = [Carbon::parse($semester->registration_starts_at), $semester->registration_ends_at ? Carbon::parse($semester->registration_ends_at) : now()];
                $query->whereBetween('date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')]);
                $reportTitle = "Semester: " . $semester->name;
            } else {
                $dateRange = [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
                $query->whereBetween('date', [$dateRange[0]->format('Y-m-d'), $dateRange[1]->format('Y-m-d')]);
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
                DB::raw('SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as leave_count'),
                DB::raw('SEC_TO_TIME(AVG(CASE WHEN clock_in IS NOT NULL THEN TIME_TO_SEC(clock_in) END)) as avg_clock_in_sec'),
                DB::raw('SUM(CASE WHEN clock_in IS NOT NULL AND clock_out IS NOT NULL THEN TIME_TO_SEC(TIMEDIFF(clock_out, clock_in)) ELSE 0 END) as total_seconds_worked')
            )
            ->groupBy('staff_id')
            ->with(['staff.user', 'staff.department'])
            ->get()
            ->map(function ($s) {
                $totalRecorded = $s->present_count + $s->late_count + $s->absent_count + $s->leave_count;
                $s->rate = $totalRecorded > 0 ? round((($s->present_count + $s->late_count) / $totalRecorded) * 100, 1) : 0;
                $s->punctuality_rate = ($s->present_count + $s->late_count) > 0 ? round(($s->present_count / ($s->present_count + $s->late_count)) * 100, 1) : 0;
                
                if ($s->avg_clock_in_sec) {
                    $s->avg_clock_in = Carbon::parse($s->avg_clock_in_sec)->format('h:i A');
                } else {
                    $s->avg_clock_in = 'N/A';
                }

                $hours = floor($s->total_seconds_worked / 3600);
                $minutes = floor(($s->total_seconds_worked % 3600) / 60);
                $s->total_hours_formatted = "{$hours}h {$minutes}m";

                return $s;
            });

            // Departmental Summary
            $departmentSummary = $data->groupBy(fn($item) => $item->staff?->department?->name ?? 'Unassigned')
                ->map(function ($group, $deptName) {
                    $staffCount = $group->count();
                    $totalPresent = $group->sum('present_count');
                    $totalLate = $group->sum('late_count');
                    $totalAbsent = $group->sum('absent_count');
                    $totalLeave = $group->sum('leave_count');
                    $totalDaysSum = $group->sum('total_days');
                    
                    $avgRate = $group->avg('rate');
                    $avgPunctuality = $group->avg('punctuality_rate');

                    return [
                        'department_name' => $deptName,
                        'staff_count' => $staffCount,
                        'total_days' => $totalDaysSum,
                        'present_count' => $totalPresent,
                        'late_count' => $totalLate,
                        'absent_count' => $totalAbsent,
                        'leave_count' => $totalLeave,
                        'avg_rate' => round($avgRate ?? 0, 1),
                        'avg_punctuality' => round($avgPunctuality ?? 0, 1),
                    ];
                })->values();

            // At-Risk Staff (< 75% attendance or >= 3 absences)
            $atRiskStaff = $data->filter(fn($item) => $item->rate < 75 || $item->absent_count >= 3)->values();

            // Overall Summary Stats
            $overallStats = [
                'total_staff' => $data->count(),
                'avg_attendance_rate' => round($data->avg('rate') ?? 0, 1),
                'avg_punctuality_rate' => round($data->avg('punctuality_rate') ?? 0, 1),
                'total_present' => $data->sum('present_count'),
                'total_late' => $data->sum('late_count'),
                'total_absent' => $data->sum('absent_count'),
                'total_leave' => $data->sum('leave_count'),
                'at_risk_count' => $atRiskStaff->count(),
                'total_hours_worked' => round($data->sum('total_seconds_worked') / 3600, 1),
            ];

            return [
                'data' => $data,
                'departmentSummary' => $departmentSummary,
                'atRiskStaff' => $atRiskStaff,
                'overallStats' => $overallStats,
                'title' => $reportTitle,
                'dateRange' => $dateRange,
            ];
        });
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

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('staff_number', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $staffList = $query->paginate(30)->withQueryString();
        $staffIds = $staffList->pluck('id');

        $attendances = Attendance::whereIn('staff_id', $staffIds)
            ->whereBetween('date', [$startDate, $endDate])
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
            'filters' => $request->only(['date', 'department_id', 'search']),
        ]);
    }
}
