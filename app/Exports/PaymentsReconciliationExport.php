<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentsReconciliationExport implements FromCollection, WithHeadings, WithTitle, WithMapping
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Payment::with(['invoice.user.student.department', 'invoice.items.feeType'])
            ->where('status', 'success');

        if (!empty($this->filters['session_id']) && $this->filters['session_id'] !== 'all') {
            $query->whereHas('invoice', function ($q) {
                $q->where('session_id', $this->filters['session_id']);
            });
        }

        if (
            (!empty($this->filters['faculty_id']) && $this->filters['faculty_id'] !== 'all') ||
            (!empty($this->filters['department_id']) && $this->filters['department_id'] !== 'all') ||
            (!empty($this->filters['program_id']) && $this->filters['program_id'] !== 'all') ||
            (!empty($this->filters['level']) && $this->filters['level'] !== 'all') ||
            (!empty($this->filters['gender']) && $this->filters['gender'] !== 'all') ||
            (!empty($this->filters['entry_mode']) && $this->filters['entry_mode'] !== 'all')
        ) {
            $query->whereHas('invoice.user.student', function ($q) {
                if (!empty($this->filters['faculty_id']) && $this->filters['faculty_id'] !== 'all') $q->where('faculty_id', $this->filters['faculty_id']);
                if (!empty($this->filters['department_id']) && $this->filters['department_id'] !== 'all') $q->where('department_id', $this->filters['department_id']);
                if (!empty($this->filters['program_id']) && $this->filters['program_id'] !== 'all') $q->where('program_id', $this->filters['program_id']);
                if (!empty($this->filters['level']) && $this->filters['level'] !== 'all') $q->where('current_level', $this->filters['level']);
                if (!empty($this->filters['gender']) && $this->filters['gender'] !== 'all') $q->where('gender', $this->filters['gender']);
                if (!empty($this->filters['entry_mode']) && $this->filters['entry_mode'] !== 'all') $q->where('entry_mode', $this->filters['entry_mode']);
            });
        }

        if (!empty($this->filters['start_date'])) {
            $query->where('paid_at', '>=', $this->filters['start_date']);
        }
        if (!empty($this->filters['end_date'])) {
            $query->where('paid_at', '<=', $this->filters['end_date'] . ' 23:59:59');
        }

        return $query->latest('paid_at')->get();
    }

    public function map($payment): array
    {
        $student = $payment->invoice?->user?->student;
        $paymentType = $payment->invoice?->type 
            ? ucwords(str_replace('_', ' ', $payment->invoice->type)) 
            : 'Fees';

        return [
            $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : 'N/A',
            $payment->invoice?->reference ?? 'N/A',
            $payment->gateway_reference ?? 'N/A', // Reference sent to gateway
            $student?->matriculation_number ?? 'N/A',
            $payment->invoice?->user?->name ?? 'N/A',
            $student?->department?->name ?? 'N/A',
            $paymentType,
            (float) $payment->amount,
            $payment->channel ? strtoupper($payment->channel) : 'N/A',
            strtoupper($payment->status),
        ];
    }

    public function headings(): array
    {
        return [
            'Payment Date',
            'Invoice Reference',
            'Gateway Reference',
            'Student Reg/Matric No',
            'Student Name',
            'Department',
            'Payment Type',
            'Amount (NGN)',
            'Payment Channel',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Payments Reconciliation';
    }
}
