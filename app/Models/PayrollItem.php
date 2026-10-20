<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PayrollItem extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'allowance_breakdown' => 'array',
        'deduction_breakdown' => 'array',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Recalculate totals for this payroll item.
     */
    public function recalculate(): void
    {
        if ($this->status === 'excluded') {
            $this->net_salary = 0;
            return;
        }

        $basic = (float) $this->basic_salary;
        $allowances = (float) $this->total_allowances;
        $deductions = (float) $this->total_deductions;

        $this->net_salary = max(0, $basic + $allowances - $deductions);
    }
}
