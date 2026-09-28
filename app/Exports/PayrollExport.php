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
    protected array $attendanceStats = [];

    public function __construct(Payroll $payroll)
    {
        $this->payroll = $payroll;
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
                    SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days')
                ->groupBy('staff_id')
                ->get()
                ->keyBy('staff_id');

            foreach ($staffIds as $sid) {
                $this->attendanceStats[$sid] = [
                    'present' => (int) ($stats[$sid]->present_days ?? 0),
                    'absent' => (int) ($stats[$sid]->absent_days ?? 0),
                ];
            }
        }
    }

    public function collection()
    {
        return $this->payroll->items()->with(['staff.user', 'staff.department'])->get();
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
            'status',
            'remarks',
        ];
    }

    public function map($row): array
    {
        $sid = $row->staff_id;
        $present = $this->attendanceStats[$sid]['present'] ?? 0;
        $absent = $this->attendanceStats[$sid]['absent'] ?? 0;

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
            $row->status,
            $row->remarks,
        ];
    }

    public function title(): string
    {
        return 'Payroll_' . $this->payroll->month . '_' . $this->payroll->year;
    }
}
