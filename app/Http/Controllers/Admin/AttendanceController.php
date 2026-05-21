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

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendance::with(['staff.user', 'staff.department.faculty']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        } else {
            $query->whereDate('date', now()->toDateString());
        }

        if ($request->filled('department_id')) {
            $query->whereHas('staff', fn($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->latest()->paginate(20)->withQueryString();

        $allStaff = Staff::with('user:id,name')->get()->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->user->name
        ]);

        $holiday = Holiday::whereDate('date', $request->date ?? now()->toDateString())->first();

        return Inertia::render('Admin/HR/Attendance/Index', [
            'attendances' => $attendances,
            'allStaff' => $allStaff,
            'holiday' => $holiday,
            'faculties' => Faculty::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only(['date', 'department_id', 'status']),
        ]);
    }

    public function storeHoliday(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|unique:holidays,date',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Holiday::create($validated);

        return back()->with('success', 'Holiday marked successfully.');
    }

    public function destroyHoliday(Holiday $holiday)
    {
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

        Attendance::updateOrCreate(
            ['staff_id' => $validated['staff_id'], 'date' => $validated['date']],
            array_merge($validated, ['source' => 'manual'])
        );

        return back()->with('success', 'Attendance record saved successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'file' => 'required|mimes:xlsx,csv,xls|max:10240',
        ]);

        Excel::import(new AttendanceImport($request->date), $request->file('file'));

        return back()->with('success', 'Attendance imported successfully.');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return back()->with('success', 'Attendance record removed.');
    }

    public function downloadTemplate()
    {
        $headers = ['staff_id', 'staff_name', 'clock_in', 'clock_out'];
        $data = [
            ['STF-001', 'John Doe', '08:30', '16:30'],
            ['STF-002', 'Jane Smith', '09:00', ''],
        ];

        return Excel::download(new class($headers, $data) implements \Maatwebsite\Excel\Concerns\FromCollection {
            public function __construct(protected $headers, protected $data) {}
            public function collection() {
                return collect([$this->headers, ...$this->data]);
            }
        }, 'attendance_import_template.xlsx');
    }

    public function reports(Request $request)
    {
        $stats = $this->getReportStats($request);

        return Inertia::render('Admin/HR/Attendance/Reports', [
            'stats' => $stats['data'],
            'sessions' => Session::orderBy('start_date', 'desc')->get(),
            'semesters' => Semester::orderBy('registration_starts_at', 'desc')->get(),
            'departments' => Department::orderBy('name')->get(),
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
                    $s->staff->staff_number,
                    $s->staff->user->name,
                    $s->staff->department->name,
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
            'departments' => Department::orderBy('name')->get(),
            'filters' => $request->only(['date', 'department_id']),
        ]);
    }
}
