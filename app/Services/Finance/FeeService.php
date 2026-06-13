<?php

namespace App\Services\Finance;

use App\Models\Student;
use App\Models\Session;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeConfiguration;
use App\Models\SystemSetting;
use App\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeeService
{
    /**
     * Generate school fee invoice for a student.
     */
    public function generateSchoolFeeInvoice(Student $student, Session $session)
    {
        // Check if invoice already exists for this session
        $existing = Invoice::where('user_id', $student->user_id)
            ->where('session_id', $session->id)
            ->where('type', 'school_fee')
            ->first();

        if ($existing) {
            return $this->refreshInvoiceIfUnpaid($existing);
        }

        // Fetch ALL potentially applicable fees for the target session
        // If policy is admission_session, we use their original session fees for the base amount
        $targetSessionId = ($student->fee_policy === 'admission_session' && $student->admitted_session_id) 
            ? $student->admitted_session_id 
            : $session->id;

        $allConfigs = FeeConfiguration::where('session_id', $targetSessionId)
            ->where(function ($q) use ($student) {
                $q->where('level', $student->current_level)
                    ->orWhereNull('level');
            })
            ->where(function ($q) use ($student) {
                $q->where('entry_mode', $student->entry_mode)
                    ->orWhereNull('entry_mode');
            })
            ->where('is_compulsory', true)
            ->with('feeType')
            ->get();

        // Specificity Resolver (Mirroring PaymentController logic)
        $resolvedConfigs = collect();
        $groupedConfigs = $allConfigs->groupBy('fee_type_id');

        foreach ($groupedConfigs as $feeTypeId => $configs) {
            $resolved = null;
            if ($student->program_id) {
                $resolved = $configs->where('program_id', $student->program_id)->first();
            }
            if (!$resolved && $student->department_id) {
                $resolved = $configs->where('department_id', $student->department_id)
                    ->whereNull('program_id')
                    ->first();
            }
            if (!$resolved && $student->faculty_id) {
                $resolved = $configs->where('faculty_id', $student->faculty_id)
                    ->whereNull('department_id')
                    ->whereNull('program_id')
                    ->first();
            }
            if (!$resolved) {
                $resolved = $configs->whereNull('faculty_id')
                    ->whereNull('department_id')
                    ->whereNull('program_id')
                    ->first();
            }
            if ($resolved) {
                if ($resolved->feeType && $resolved->feeType->is_one_time) {
                    $alreadyCharged = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student) {
                        $q->where('user_id', $student->user_id);
                    })->where('fee_type_id', $resolved->fee_type_id)->exists();

                    if ($alreadyCharged) {
                        continue;
                    }
                }
                $resolvedConfigs->push($resolved);
            }
        }

        if ($resolvedConfigs->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($student, $session, $resolvedConfigs) {
            $academicTotal = $resolvedConfigs->sum('amount');
            
            $adminChargeEnabled = SystemSetting::get('admin_charge_enabled', true);
            $adminChargeAmount = SystemSetting::get('admin_charge_amount', 250000);
            
            $totalAmountBeforeDiscount = $academicTotal;
            if ($adminChargeEnabled) {
                $totalAmountBeforeDiscount += $adminChargeAmount;
            }

            $discountAmount = 0;
            if ($student->scholarship && ($student->program?->scholarship_eligible ?? true)) {
                $scholarship = $student->scholarship;
                $baseForDiscount = $academicTotal;
                if ($adminChargeEnabled && $scholarship->covers_admin_charges) {
                    $baseForDiscount += $adminChargeAmount;
                }
                $discountAmount = $baseForDiscount * ($scholarship->percentage / 100);
            }

            $finalAmount = $totalAmountBeforeDiscount - $discountAmount;

            $studentSession = StudentSession::firstOrCreate(
                ['student_id' => $student->id, 'session_id' => $session->id],
                ['level' => $student->current_level, 'status' => 'active']
            );

            $invoice = Invoice::create([
                'user_id' => $student->user_id,
                'session_id' => $session->id,
                'student_session_id' => $studentSession->id,
                'type' => 'school_fee',
                'reference' => 'SCH-' . strtoupper(uniqid()),
                'invoice_number' => 'INV-' . strtoupper(Str::random(10)),
                'amount' => $finalAmount,
                'status' => 'pending',
                'due_date' => now()->addWeeks(4),
            ]);

            foreach ($resolvedConfigs as $config) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'fee_type_id' => $config->fee_type_id,
                    'description' => $config->feeType->name ?? 'Fee',
                    'amount' => $config->amount,
                ]);
            }

            if ($adminChargeEnabled) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Administrative Charges',
                    'amount' => $adminChargeAmount,
                ]);
            }

            if ($discountAmount > 0) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Scholarship Discount (' . $student->scholarship->name . ' - ' . floatval($student->scholarship->percentage) . '%)',
                    'amount' => -$discountAmount,
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Refresh invoice amount if no payment has been made yet.
     */
    public function refreshInvoiceIfUnpaid(Invoice $invoice)
    {
        // Only refresh school fee invoices that have NO payments
        if ($invoice->type !== 'school_fee' || $invoice->paid_amount > 0 || $invoice->status === 'paid') {
            return $invoice;
        }

        $student = Student::with(['scholarship', 'program'])->where('user_id', $invoice->user_id)->first();
        if (!$student) return $invoice;

        // Re-calculate based on current configuration for the session
        $targetSessionId = ($student->fee_policy === 'admission_session' && $student->admitted_session_id) 
            ? $student->admitted_session_id 
            : $invoice->session_id;

        $allConfigs = FeeConfiguration::where('session_id', $targetSessionId)
            ->where(function ($q) use ($student) {
                $q->where('level', $student->current_level)->orWhereNull('level');
            })
            ->where(function ($q) use ($student) {
                $q->where('entry_mode', $student->entry_mode)->orWhereNull('entry_mode');
            })
            ->where('is_compulsory', true)
            ->with('feeType')
            ->get();

        $resolvedConfigs = collect();
        $groupedConfigs = $allConfigs->groupBy('fee_type_id');

        foreach ($groupedConfigs as $feeTypeId => $configs) {
            $resolved = null;
            if ($student->program_id) $resolved = $configs->where('program_id', $student->program_id)->first();
            if (!$resolved && $student->department_id) $resolved = $configs->where('department_id', $student->department_id)->whereNull('program_id')->first();
            if (!$resolved && $student->faculty_id) $resolved = $configs->where('faculty_id', $student->faculty_id)->whereNull('department_id')->whereNull('program_id')->first();
            if (!$resolved) $resolved = $configs->whereNull('faculty_id')->whereNull('department_id')->whereNull('program_id')->first();
            if ($resolved) {
                if ($resolved->feeType && $resolved->feeType->is_one_time) {
                    $alreadyCharged = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student, $invoice) {
                        $q->where('user_id', $student->user_id)
                          ->where('id', '!=', $invoice->id);
                    })->where('fee_type_id', $resolved->fee_type_id)->exists();

                    if ($alreadyCharged) {
                        continue;
                    }
                }
                $resolvedConfigs->push($resolved);
            }
        }

        if ($resolvedConfigs->isEmpty()) return $invoice;

        return DB::transaction(function () use ($invoice, $student, $resolvedConfigs) {
            $academicTotal = $resolvedConfigs->sum('amount');
            $adminChargeEnabled = SystemSetting::get('admin_charge_enabled', true);
            $adminChargeAmount = SystemSetting::get('admin_charge_amount', 250000);
            
            $totalAmountBeforeDiscount = $academicTotal;
            if ($adminChargeEnabled) $totalAmountBeforeDiscount += $adminChargeAmount;

            $discountAmount = 0;
            if ($student->scholarship && ($student->program?->scholarship_eligible ?? true)) {
                $scholarship = $student->scholarship;
                $baseForDiscount = $academicTotal;
                if ($adminChargeEnabled && $scholarship->covers_admin_charges) $baseForDiscount += $adminChargeAmount;
                $discountAmount = $baseForDiscount * ($scholarship->percentage / 100);
            }

            $finalAmount = $totalAmountBeforeDiscount - $discountAmount;

            // Only update if the amount actually changed
            if (abs($invoice->amount - $finalAmount) > 0.01) {
                $invoice->update(['amount' => $finalAmount]);

                // Sync items
                $invoice->items()->delete();
                foreach ($resolvedConfigs as $config) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'fee_type_id' => $config->fee_type_id,
                        'description' => $config->feeType->name ?? 'Fee',
                        'amount' => $config->amount,
                    ]);
                }
                if ($adminChargeEnabled) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'Administrative Charges',
                        'amount' => $adminChargeAmount,
                    ]);
                }
                if ($discountAmount > 0) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'Scholarship Discount (' . $student->scholarship->name . ' - ' . floatval($student->scholarship->percentage) . '%)',
                        'amount' => -$discountAmount,
                    ]);
                }
            }

            return $invoice->fresh();
        });
    }

    /**
     * Bulk generate for all active students.
     */
    public function bulkGenerateInvoices(Session $session)
    {
        $students = Student::where('status', 'active')->get();
        $count = 0;

        foreach ($students as $student) {
            if ($this->generateSchoolFeeInvoice($student, $session)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get available optional fees for a student.
     */
    public function getAvailableOptionalFees(Student $student, Session $session)
    {
        // Fetch all configurations for the target session
        $allConfigs = FeeConfiguration::where('session_id', $session->id)
            ->where(function ($q) use ($student) {
                $q->where('level', $student->current_level)
                    ->orWhereNull('level');
            })
            ->where(function ($q) use ($student) {
                $q->where('entry_mode', $student->entry_mode)
                    ->orWhereNull('entry_mode');
            })
            ->where('is_compulsory', false)
            ->with('feeType')
            ->get();

        // Resolve specific configs based on student profile (program/dept/faculty)
        $resolvedConfigs = collect();
        $groupedConfigs = $allConfigs->groupBy('fee_type_id');

        foreach ($groupedConfigs as $feeTypeId => $configs) {
            $resolved = null;
            if ($student->program_id) {
                $resolved = $configs->where('program_id', $student->program_id)->first();
            }
            if (!$resolved && $student->department_id) {
                $resolved = $configs->where('department_id', $student->department_id)
                    ->whereNull('program_id')
                    ->first();
            }
            if (!$resolved && $student->faculty_id) {
                $resolved = $configs->where('faculty_id', $student->faculty_id)
                    ->whereNull('department_id')
                    ->whereNull('program_id')
                    ->first();
            }
            if (!$resolved) {
                $resolved = $configs->whereNull('faculty_id')
                    ->whereNull('department_id')
                    ->whereNull('program_id')
                    ->first();
            }
            if ($resolved) {
                $resolvedConfigs->push($resolved);
            }
        }

        // Filter out optional fees that have already been generated/invoiced for this student
        return $resolvedConfigs->filter(function ($config) use ($student) {
            // If the fee type is one-time, check if they have ever been invoiced for it
            if ($config->feeType && $config->feeType->is_one_time) {
                return !\App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student) {
                    $q->where('user_id', $student->user_id);
                })->where('fee_type_id', $config->fee_type_id)->exists();
            }

            // Otherwise, check if they've been invoiced for it in the current session
            return !\App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student, $config) {
                $q->where('user_id', $student->user_id)
                    ->where('session_id', $config->session_id);
            })->where('fee_type_id', $config->fee_type_id)->exists();
        })->values();
    }

    /**
     * Generate invoice for a specific optional fee configuration.
     */
    public function generateOptionalFeeInvoice(Student $student, Session $session, FeeConfiguration $config)
    {
        // Double check it's not already invoiced
        $isOneTime = $config->feeType ? $config->feeType->is_one_time : false;
        $existsQuery = \App\Models\InvoiceItem::whereHas('invoice', function ($q) use ($student, $config, $isOneTime) {
            $q->where('user_id', $student->user_id);
            if (!$isOneTime) {
                $q->where('session_id', $config->session_id);
            }
        })->where('fee_type_id', $config->fee_type_id);

        if ($existsQuery->exists()) {
            return null;
        }

        return DB::transaction(function () use ($student, $session, $config) {
            $studentSession = StudentSession::firstOrCreate(
                ['student_id' => $student->id, 'session_id' => $session->id],
                ['level' => $student->current_level, 'status' => 'active']
            );

            $invoice = Invoice::create([
                'user_id' => $student->user_id,
                'session_id' => $session->id,
                'student_session_id' => $studentSession->id,
                'type' => 'other_fee',
                'reference' => 'OTH-' . strtoupper(uniqid()),
                'invoice_number' => 'INV-' . strtoupper(Str::random(10)),
                'amount' => $config->amount,
                'status' => 'pending',
                'due_date' => now()->addWeeks(4),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_type_id' => $config->fee_type_id,
                'description' => $config->feeType->name ?? 'Optional Fee',
                'amount' => $config->amount,
            ]);

            return $invoice;
        });
    }
}
