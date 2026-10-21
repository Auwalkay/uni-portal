<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Payroll $payroll;
    protected array $filters = [];
    protected array $attendanceStats = [];

    public function __construct(Payroll $payroll, array $filters = [])
    {
        $this->payroll = $payroll;
        $this->filters = $filters;
        $this->loadAttendanceStats();
    }

    protected function loadAttendanceStats(): void
    {
        $staffIds = $this->payroll->items()->pluck('staff_id')->filter()->unique()->toArray();

        if (!empty($staffIds)) {
            $stats = Attendance::whereIn('staff_id', $staffIds)
                ->whereYear('date', $this->payroll->year)
                ->whereMonth('date', $this->payroll->month)
                ->selectRaw('staff_id, 
                    SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = "on_leave" THEN 1 ELSE 0 END) as leave_days')
                ->groupBy('staff_id')
                ->get()
                ->keyBy('staff_id');

            foreach ($staffIds as $sid) {
                $this->attendanceStats[$sid] = [
                    'present' => (int) ($stats[$sid]->present_days ?? 0),
                    'absent' => (int) ($stats[$sid]->absent_days ?? 0),
                    'on_leave' => (int) ($stats[$sid]->leave_days ?? 0),
                ];
            }
        }
    }

    public function collection()
    {
        $query = $this->payroll->items()->with(['staff.user', 'staff.department']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('staff.user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('staff', function ($sq) use ($search) {
                    $sq->where('staff_number', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($this->filters['status']) && $this->filters['status'] !== 'ALL_STATUS') {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['department_id']) && $this->filters['department_id'] !== 'ALL_DEPARTMENTS') {
            $query->whereHas('staff', function ($sq) {
                $sq->where('department_id', $this->filters['department_id']);
            });
        }

        if (!empty($this->filters['attendance_status']) && $this->filters['attendance_status'] !== 'ALL_ATTENDANCE') {
            $year = $this->payroll->year;
            $month = $this->payroll->month;
            if ($this->filters['attendance_status'] === 'on_leave') {
                $query->whereHas('staff.attendances', function ($aq) use ($year, $month) {
                    $aq->whereYear('date', $year)->whereMonth('date', $month)->where('status', 'on_leave');
                });
            } elseif ($this->filters['attendance_status'] === 'has_absent') {
                $query->whereHas('staff.attendances', function ($aq) use ($year, $month) {
                    $aq->whereYear('date', $year)->whereMonth('date', $month)->where('status', 'absent');
                });
            } elseif ($this->filters['attendance_status'] === 'has_late') {
                $query->whereHas('staff.attendances', function ($aq) use ($year, $month) {
                    $aq->whereYear('date', $year)->whereMonth('date', $month)->where('status', 'late');
                });
            } elseif ($this->filters['attendance_status'] === 'perfect') {
                $query->whereHas('staff.attendances', function ($aq) use ($year, $month) {
                    $aq->whereYear('date', $year)->whereMonth('date', $month)->where('status', 'present');
                })->whereDoesntHave('staff.attendances', function ($aq) use ($year, $month) {
                    $aq->whereYear('date', $year)->whereMonth('date', $month)->whereIn('status', ['absent', 'late', 'on_leave']);
                });
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'payroll_item_id',
            'staff_id',
            'staff_name',
            'email',
            'department',
            'basic_salary',
            'allowances',
            'deductions',
            'net_salary',
            'present_days',
            'absent_days',
            'on_leave_days',
            'status',
            'remarks',
        ];
    }

    public function map($row): array
    {
        $sid = $row->staff_id;
        $present = $this->attendanceStats[$sid]['present'] ?? 0;
        $absent = $this->attendanceStats[$sid]['absent'] ?? 0;
        $onLeave = $this->attendanceStats[$sid]['on_leave'] ?? 0;

        return [
            $row->id,
            $row->staff?->staff_number,
            $row->staff?->user?->name,
            $row->staff?->user?->email,
            $row->staff?->department?->name,
            (float) $row->basic_salary,
            (float) $row->total_allowances,
            (float) $row->total_deductions,
            (float) $row->net_salary,
            $present,
            $absent,
            $onLeave,
            $row->status,
            $row->remarks,
        ];
    }

    public function title(): string
    {
        return 'Payroll_' . $this->payroll->month . '_' . $this->payroll->year;
    }
}
