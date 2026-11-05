<?php

namespace App\Services\Finance;

use App\Models\Student;
use App\Models\Session;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
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

        $progType = $student->program?->type;
        $isPg = $student->isPostgraduate();

        $allConfigs = FeeConfiguration::where('session_id', $targetSessionId)
            ->where(function ($q) use ($student) {
                $q->where('level', $student->current_level)
                    ->orWhereNull('level');
            })
            ->where(function ($q) use ($student, $progType, $isPg) {
                $modes = array_filter([$student->entry_mode, $progType]);
                if ($progType === 'MSC') { $modes[] = 'MSc'; }
                if ($progType === 'PHD') { $modes[] = 'PhD'; }
                if ($isPg) { $modes[] = 'Postgraduate'; $modes[] = 'PG'; }
                $q->whereIn('entry_mode', $modes)->orWhereNull('entry_mode');
            })
            ->where('is_compulsory', true)
            ->where('is_milestone_fee', false)
            ->with('feeType')
            ->get();

        // Specificity Resolver
        $resolvedConfigs = collect();
        $groupedConfigs = $allConfigs->groupBy('fee_type_id');

        foreach ($groupedConfigs as $feeTypeId => $configs) {
            $resolved = static::resolveConfigFromCollection(
                $configs,
                $student->program_id,
                $student->department_id,
                $student->faculty_id,
                $progType,
                $student->current_level,
                $student->entry_mode,
                $isPg
            );
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
            
            $tuition = 0;
            foreach ($resolvedConfigs as $config) {
                if (!$config->feeType || !$config->feeType->is_one_time) {
                    $tuition += $config->amount;
                }
            }

            $adminChargeEnabled = filter_var(SystemSetting::get('admin_charge_enabled', true), FILTER_VALIDATE_BOOLEAN);
            $adminChargeAmount = (float) SystemSetting::get('admin_charge_amount', 250000);
            
            $totalAmountBeforeDiscount = $academicTotal;
            if ($adminChargeEnabled) {
                $totalAmountBeforeDiscount += $adminChargeAmount;
            }

            $discountAmount = $this->calculateScholarshipDiscount($student, $resolvedConfigs, $adminChargeEnabled, $adminChargeAmount);

            // Check if late payment fine should be applied immediately (returning students only)
            $lateFineAmount = 0;
            $applyLateFine = false;
            $isLateFeeWaived = (bool) ($student->scholarship?->waives_late_fees);
            if (!$isLateFeeWaived && $student->isReturningStudent($session) && $session->late_payment_deadline && $session->late_payment_deadline->isPast() && $session->late_fee_amount > 0) {
                $isEnabled = filter_var(\App\Models\SystemSetting::get('late_fee_enabled', true), FILTER_VALIDATE_BOOLEAN);
                if ($isEnabled) {
                    $lateFineAmount = (float) $session->late_fee_amount;
                    $applyLateFine = true;
                }
            }

            $finalAmount = max(0.0, $totalAmountBeforeDiscount - $discountAmount + $lateFineAmount);
            $isPaidInFull = $finalAmount <= 0;

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
                'paid_amount' => 0.00,
                'status' => $isPaidInFull ? 'paid' : 'pending',
                'due_date' => now()->addWeeks(4),
                'late_fine_applied' => $applyLateFine,
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
                $scholarship = $student->scholarship;
                if ($scholarship->covers_all_expenses) {
                    $discountDesc = 'Scholarship Discount (' . $scholarship->name . ' - All School Expenses Covered)';
                } elseif ($scholarship->type === 'fixed') {
                    $discountDesc = 'Scholarship Discount (' . $scholarship->name . ' - Fixed ₦' . number_format($scholarship->amount, 2) . ')';
                } else {
                    $discountDesc = 'Scholarship Discount (' . $scholarship->name . ' - ' . floatval($scholarship->percentage) . '%)';
                }
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $discountDesc,
                    'amount' => -$discountAmount,
                ]);
            }

            if ($applyLateFine) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Late Payment Fine (' . $session->name . ')',
                    'amount' => $lateFineAmount,
                ]);
            }

            if ($isPaidInFull) {
                Payment::create([
                    'transaction_id' => Payment::generateTransactionId('SCH'),
                    'invoice_id' => $invoice->id,
                    'user_id' => $student->user_id,
                    'gateway' => 'scholarship',
                    'gateway_reference' => 'SCHOLARSHIP-' . strtoupper(uniqid()),
                    'amount' => 0.00,
                    'status' => 'success',
                    'channel' => 'scholarship',
                    'paid_at' => now(),
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Calculate expected school fee for a student for a session without creating an invoice.
     */
    public function calculateExpectedSchoolFee(Student $student, Session $session): float
    {
        $adminChargeEnabled = filter_var(SystemSetting::get('admin_charge_enabled', true), FILTER_VALIDATE_BOOLEAN);
        $adminChargeAmount = (float) SystemSetting::get('admin_charge_amount', 250000);

        $targetSessionId = ($student->fee_policy === 'admission_session' && $student->admitted_session_id) 
            ? $student->admitted_session_id 
            : $session->id;

        $allConfigs = FeeConfiguration::where('session_id', $targetSessionId)
            ->where('is_compulsory', true)
            ->where('is_milestone_fee', false)
            ->with('feeType')
            ->get();

        $allConfigsGrouped = collect([$targetSessionId => $allConfigs]);

        return $this->calculateExpectedSchoolFeeWithConfigs($student, $session, $allConfigsGrouped, $adminChargeEnabled, $adminChargeAmount);
    }

    /**
     * High-performance calculation using pre-loaded fee configurations and settings in memory.
     */
    public function calculateExpectedSchoolFeeWithConfigs(
        Student $student, 
        Session $session, 
        $allConfigsBySession, 
        bool $adminChargeEnabled = true, 
        float $adminChargeAmount = 250000
    ): float {
        $targetSessionId = ($student->fee_policy === 'admission_session' && $student->admitted_session_id) 
            ? $student->admitted_session_id 
            : $session->id;

        $sessionConfigs = $allConfigsBySession->get($targetSessionId, collect());

        $progType = $student->program?->type;
        $isPg = $student->isPostgraduate();

        $resolvedConfigs = collect();
        $groupedConfigs = $sessionConfigs->where('is_milestone_fee', false)->groupBy('fee_type_id');

        foreach ($groupedConfigs as $feeTypeId => $configs) {
            $resolved = static::resolveConfigFromCollection(
                $configs,
                $student->program_id,
                $student->department_id,
                $student->faculty_id,
                $progType,
                $student->current_level,
                $student->entry_mode,
                $isPg
            );
            if ($resolved) {
                $resolvedConfigs->push($resolved);
            }
        }

        $academicTotal = $resolvedConfigs->sum('amount');
        
        $totalAmountBeforeDiscount = $academicTotal;
        if ($adminChargeEnabled) {
            $totalAmountBeforeDiscount += $adminChargeAmount;
        }

        $discountAmount = $this->calculateScholarshipDiscount($student, $resolvedConfigs, $adminChargeEnabled, $adminChargeAmount);

        return max(0, $totalAmountBeforeDiscount - $discountAmount);
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

        $progType = $student->program?->type;
        $isPg = $student->isPostgraduate();

        $allConfigs = FeeConfiguration::where('session_id', $targetSessionId)
            ->where(function ($q) use ($student) {
                $q->where('level', $student->current_level)->orWhereNull('level');
            })
            ->where(function ($q) use ($student, $progType, $isPg) {
                $modes = array_filter([$student->entry_mode, $progType]);
                if ($progType === 'MSC') { $modes[] = 'MSc'; }
                if ($progType === 'PHD') { $modes[] = 'PhD'; }
                if ($isPg) { $modes[] = 'Postgraduate'; $modes[] = 'PG'; }
                $q->whereIn('entry_mode', $modes)->orWhereNull('entry_mode');
            })
            ->where('is_compulsory', true)
            ->where('is_milestone_fee', false)
            ->with('feeType')
            ->get();

        $resolvedConfigs = collect();
        $groupedConfigs = $allConfigs->groupBy('fee_type_id');

        foreach ($groupedConfigs as $feeTypeId => $configs) {
            $resolved = static::resolveConfigFromCollection(
                $configs,
                $student->program_id,
                $student->department_id,
                $student->faculty_id,
                $progType,
                $student->current_level,
                $student->entry_mode,
                $isPg
            );
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

            $adminChargeEnabled = filter_var(SystemSetting::get('admin_charge_enabled', true), FILTER_VALIDATE_BOOLEAN);
            $adminChargeAmount = (float) SystemSetting::get('admin_charge_amount', 250000);

            $discountAmount = $this->calculateScholarshipDiscount($student, $resolvedConfigs, $adminChargeEnabled, $adminChargeAmount);

            // Check if late payment fine should be included (returning students only, if not previously waived)
            $session = $invoice->session;
            $lateFineAmount = 0;
            $applyLateFine = false;
            $isLateFeeWaived = (bool) ($student->scholarship?->waives_late_fees);
            if (!$isLateFeeWaived && !$invoice->late_fine_waived && $student->isReturningStudent($session) && $session && $session->late_payment_deadline && $session->late_payment_deadline->isPast() && $session->late_fee_amount > 0) {
                $isEnabled = filter_var(SystemSetting::get('late_fee_enabled', true), FILTER_VALIDATE_BOOLEAN);
                if ($isEnabled) {
                    $lateFineAmount = (float) $session->late_fee_amount;
                    $applyLateFine = true;
                }
            }

            $totalAmountBeforeDiscount = $academicTotal;
            if ($adminChargeEnabled) $totalAmountBeforeDiscount += $adminChargeAmount;
            if ($applyLateFine) $totalAmountBeforeDiscount += $lateFineAmount;

            $finalAmount = max(0.0, $totalAmountBeforeDiscount - $discountAmount);
            $isPaidInFull = $finalAmount <= 0;
            $newStatus = $isPaidInFull ? 'paid' : ($invoice->paid_amount > 0 ? 'partial' : 'pending');

            // Only update if the amount actually changed, late fine status changed, or paid status changed
            if (abs($invoice->amount - $finalAmount) > 0.01 || $invoice->late_fine_applied !== $applyLateFine || ($isPaidInFull && $invoice->status !== 'paid')) {
                $invoice->update([
                    'amount' => $finalAmount,
                    'status' => $newStatus,
                    'late_fine_applied' => $applyLateFine,
                ]);

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
                    $scholarship = $student->scholarship;
                    if ($scholarship->covers_all_expenses) {
                        $discountDesc = 'Scholarship Discount (' . $scholarship->name . ' - All School Expenses Covered)';
                    } elseif ($scholarship->type === 'fixed') {
                        $discountDesc = 'Scholarship Discount (' . $scholarship->name . ' - Fixed ₦' . number_format($scholarship->amount, 2) . ')';
                    } else {
                        $discountDesc = 'Scholarship Discount (' . $scholarship->name . ' - ' . floatval($scholarship->percentage) . '%)';
                    }
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $discountDesc,
                        'amount' => -$discountAmount,
                    ]);
                }
                if ($applyLateFine && $session) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'Late Payment Fine (' . $session->name . ')',
                        'amount' => $lateFineAmount,
                    ]);
                }

                if ($isPaidInFull && !$invoice->payments()->where('status', 'success')->exists()) {
                    Payment::create([
                        'transaction_id' => Payment::generateTransactionId('SCH'),
                        'invoice_id' => $invoice->id,
                        'user_id' => $student->user_id,
                        'gateway' => 'scholarship',
                        'gateway_reference' => 'SCHOLARSHIP-' . strtoupper(uniqid()),
                        'amount' => 0.00,
                        'status' => 'success',
                        'channel' => 'scholarship',
                        'paid_at' => now(),
                    ]);
                }
            }

            return $invoice->fresh(['items', 'session']);
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
            ->where('is_milestone_fee', false)
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
     * Get available unbundled milestone fees (bench_fee, practicum_fee, defense_fee, extension_fee) for a student.
     */
    public function getAvailableMilestoneFees(Student $student, Session $session)
    {
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
            ->where('is_milestone_fee', true)
            ->with('feeType')
            ->get();

        $resolvedConfigs = collect();
        $groupedConfigs = $allConfigs->groupBy(fn ($cfg) => $cfg->milestone_type ?: $cfg->fee_type_id);

        foreach ($groupedConfigs as $groupKey => $configs) {
            $resolved = null;
            if ($student->program_id) {
                $resolved = $configs->where('program_id', $student->program_id)->first();
            }
            if (! $resolved && $student->department_id) {
                $resolved = $configs->where('department_id', $student->department_id)
                    ->whereNull('program_id')
                    ->first();
            }
            if (! $resolved && $student->faculty_id) {
                $resolved = $configs->where('faculty_id', $student->faculty_id)
                    ->whereNull('department_id')
                    ->whereNull('program_id')
                    ->first();
            }
            if (! $resolved) {
                $resolved = $configs->whereNull('faculty_id')
                    ->whereNull('department_id')
                    ->whereNull('program_id')
                    ->first();
            }
            if ($resolved) {
                $resolvedConfigs->push($resolved);
            }
        }

        return $resolvedConfigs->filter(function ($config) use ($student, $session) {
            $isOneTime = ($config->feeType && $config->feeType->is_one_time)
                || in_array($config->milestone_type, ['bench_fee', 'defense_fee', 'practicum_fee'], true);

            return ! InvoiceItem::whereHas('invoice', function ($q) use ($student, $session, $isOneTime) {
                $q->where('user_id', $student->user_id);
                if (! $isOneTime) {
                    $q->where('session_id', $session->id);
                }
            })->where('fee_type_id', $config->fee_type_id)->exists();
        })->values();
    }

    /**
     * Generate an unbundled milestone fee invoice (e.g. bench_fee, defense_fee, practicum_fee, extension_fee).
     */
    public function generateMilestoneFeeInvoice(Student $student, Session $session, string $milestoneType): ?Invoice
    {
        $targetSessionId = ($student->fee_policy === 'admission_session' && $student->admitted_session_id)
            ? $student->admitted_session_id
            : $session->id;

        $configs = FeeConfiguration::where('session_id', $targetSessionId)
            ->where('is_milestone_fee', true)
            ->where('milestone_type', $milestoneType)
            ->where(function ($q) use ($student) {
                $q->where('level', $student->current_level)
                    ->orWhereNull('level');
            })
            ->where(function ($q) use ($student) {
                $q->where('entry_mode', $student->entry_mode)
                    ->orWhereNull('entry_mode');
            })
            ->with('feeType')
            ->get();

        if ($configs->isEmpty()) {
            return null;
        }

        $config = null;
        if ($student->program_id) {
            $config = $configs->where('program_id', $student->program_id)->first();
        }
        if (! $config && $student->department_id) {
            $config = $configs->where('department_id', $student->department_id)
                ->whereNull('program_id')
                ->first();
        }
        if (! $config && $student->faculty_id) {
            $config = $configs->where('faculty_id', $student->faculty_id)
                ->whereNull('department_id')
                ->whereNull('program_id')
                ->first();
        }
        if (! $config) {
            $config = $configs->whereNull('faculty_id')
                ->whereNull('department_id')
                ->whereNull('program_id')
                ->first();
        }

        if (! $config) {
            return null;
        }

        $isOneTime = ($config->feeType && $config->feeType->is_one_time)
            || in_array($milestoneType, ['bench_fee', 'defense_fee', 'practicum_fee'], true);

        $existingItem = InvoiceItem::whereHas('invoice', function ($q) use ($student, $session, $isOneTime) {
            $q->where('user_id', $student->user_id)
                ->where('status', '!=', 'cancelled');
            if (! $isOneTime) {
                $q->where('session_id', $session->id);
            }
        })->where('fee_type_id', $config->fee_type_id)->with('invoice')->first();

        if ($existingItem && $existingItem->invoice) {
            return $existingItem->invoice;
        }

        return DB::transaction(function () use ($student, $session, $config, $milestoneType) {
            $studentSession = StudentSession::firstOrCreate(
                ['student_id' => $student->id, 'session_id' => $session->id],
                ['level' => $student->current_level, 'status' => 'active']
            );

            $description = $config->feeType?->name
                ?? (FeeConfiguration::MILESTONE_TYPES[$milestoneType] ?? 'Postgraduate Milestone Fee');

            $invoice = Invoice::create([
                'user_id' => $student->user_id,
                'session_id' => $session->id,
                'student_session_id' => $studentSession->id,
                'type' => 'milestone_fee',
                'reference' => 'MLS-' . strtoupper(uniqid()),
                'invoice_number' => 'INV-MLS-' . strtoupper(Str::random(8)),
                'amount' => $config->amount,
                'paid_amount' => 0.00,
                'status' => 'pending',
                'due_date' => now()->addWeeks(4),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_type_id' => $config->fee_type_id,
                'description' => $description,
                'amount' => $config->amount,
            ]);

            return $invoice;
        });
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

    /**
     * Generate per-course invoice for a student for a specific semester (e.g. Summer Semester).
     */
    public function generatePerCourseInvoice(Student $student, Session $session, \App\Models\Semester $semester, $courses)
    {
        if ($courses->isEmpty()) {
            return null;
        }

        // Find per-course fee configuration for this session and semester (or session fallback)
        $feeConfig = FeeConfiguration::where('session_id', $session->id)
            ->where('is_per_course', true)
            ->where(function ($q) use ($semester) {
                $q->where('semester_id', $semester->id)
                  ->orWhereNull('semester_id');
            })
            ->with('feeType')
            ->orderByRaw('semester_id IS NOT NULL DESC')
            ->first();

        $ratePerCourse = $feeConfig ? $feeConfig->amount : 15000;
        $totalAmount = $ratePerCourse * $courses->count();

        // Check if invoice already exists for this semester
        $invoice = Invoice::where('user_id', $student->user_id)
            ->where('session_id', $session->id)
            ->where('type', 'summer_fee')
            ->first();

        return DB::transaction(function () use ($student, $session, $semester, $courses, $ratePerCourse, $totalAmount, $feeConfig, $invoice) {
            $studentSession = StudentSession::firstOrCreate(
                ['student_id' => $student->id, 'session_id' => $session->id],
                ['level' => $student->current_level, 'status' => 'active']
            );

            if (!$invoice) {
                $invoice = Invoice::create([
                    'user_id' => $student->user_id,
                    'session_id' => $session->id,
                    'student_session_id' => $studentSession->id,
                    'type' => 'summer_fee',
                    'reference' => 'SUM-' . strtoupper(uniqid()),
                    'invoice_number' => 'INV-SUM-' . strtoupper(Str::random(8)),
                    'amount' => $totalAmount,
                    'status' => 'pending',
                    'due_date' => now()->addDays(7),
                ]);
            } else {
                if ($invoice->status === 'pending') {
                    $invoice->update(['amount' => $totalAmount]);
                    $invoice->items()->delete();
                }
            }

            if ($invoice->status === 'pending') {
                foreach ($courses as $course) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'fee_type_id' => $feeConfig?->fee_type_id,
                        'description' => "{$course->code} - {$course->title} ({$semester->name})",
                        'amount' => $ratePerCourse,
                    ]);
                }
            }

            return $invoice;
        });
    }

    /**
     * Calculate scholarship discount amount for a student given resolved configurations and admin charge settings.
     */
    private function calculateScholarshipDiscount(
        Student $student,
        $resolvedConfigs,
        bool $adminChargeEnabled,
        float $adminChargeAmount
    ): float {
        if (!$student->scholarship || !($student->program?->scholarship_eligible ?? true)) {
            return 0.0;
        }

        $scholarship = $student->scholarship;
        $baseForDiscount = 0.0;

        if ($scholarship->covers_all_expenses) {
            // Covers all expenses except specifically excluded fee types
            foreach ($resolvedConfigs as $config) {
                if ($config->fee_type_id && $scholarship->isFeeTypeExcluded($config->fee_type_id)) {
                    continue;
                }
                $baseForDiscount += (float) $config->amount;
            }

            if ($adminChargeEnabled) {
                $baseForDiscount += (float) $adminChargeAmount;
            }

            return $baseForDiscount;
        }

        // Standard scholarship coverage (tuition / recurring academic fees)
        foreach ($resolvedConfigs as $config) {
            if (!$config->feeType || !$config->feeType->is_one_time) {
                if ($config->feeType) {
                    $feeName = strtolower($config->feeType->name);
                    $feeSlug = $config->feeType->slug;
                    $isExcluded = str_contains($feeName, 'drug test') || 
                                  str_contains($feeSlug, 'drug-test') ||
                                  str_contains($feeName, 'acceptance') || 
                                  str_contains($feeSlug, 'acceptance') ||
                                  str_contains($feeName, 'matriculation') || 
                                  str_contains($feeSlug, 'matriculation');
                    if ($isExcluded) {
                        continue;
                    }
                }
                $baseForDiscount += (float) $config->amount;
            }
        }

        if ($adminChargeEnabled && $scholarship->covers_admin_charges) {
            $baseForDiscount += (float) $adminChargeAmount;
        }

        if ($scholarship->type === 'fixed') {
            return max(0.0, (float) ($baseForDiscount - $scholarship->amount));
        }

        return (float) ($baseForDiscount * ($scholarship->percentage / 100));
    }

    public static function normalizeTier(?string $val): string
    {
        if (! $val) {
            return '';
        }
        $clean = strtoupper(trim(str_replace(['.', ' ', '-'], '', $val)));

        return match ($clean) {
            'MSC', 'MASTERS', 'MS', 'MENG', 'LLM' => 'MSC',
            'PHD', 'DOCTORATE' => 'PHD',
            'PGD' => 'PGD',
            'MBA' => 'MBA',
            'MIM' => 'MIM',
            'UG', 'UTME', 'DIRECTENTRY', 'DE' => 'UG',
            default => $clean,
        };
    }

    public static function resolveConfigFromCollection(
        $configs,
        ?string $programId,
        ?string $departmentId,
        ?string $facultyId,
        ?string $programType = null,
        ?string $level = null,
        ?string $entryMode = null,
        bool $isPg = false
    ): ?FeeConfiguration {
        $progTier = static::normalizeTier($programType);

        // Filter candidate configs compatible with student level and entry mode / tier
        $filtered = $configs->filter(function ($c) use ($level, $entryMode, $progTier, $isPg) {
            // Level check
            if ($c->level !== null && $c->level !== '') {
                if ((string) $c->level !== (string) $level) {
                    return false;
                }
            }

            // Entry mode / Tier check
            if ($c->entry_mode !== null && $c->entry_mode !== '' && $c->entry_mode !== 'all') {
                $cNorm = static::normalizeTier($c->entry_mode);

                // Exact entry mode match (e.g. Direct Entry === Direct Entry)
                if ($entryMode && strcasecmp($c->entry_mode, $entryMode) === 0) {
                    return true;
                }

                // Tier match (e.g. rule entry_mode 'MSc' matches program type 'MSC' or 'MBA' matches 'MBA')
                if ($progTier !== '' && $cNorm === $progTier) {
                    return true;
                }

                // Universal 'Postgraduate' rule matches any PG stream or PG tier
                if (in_array(strtoupper($c->entry_mode), ['POSTGRADUATE', 'PG']) && ($isPg || in_array($progTier, ['PGD', 'MSC', 'MBA', 'MIM', 'MPHIL', 'PHD', 'MASTERS']))) {
                    return true;
                }

                return false;
            }

            return true;
        });

        if ($filtered->isEmpty()) {
            return null;
        }

        // Specificity Ranking:
        // Rank 1: Program-Specific match
        if ($programId) {
            $match = $filtered->firstWhere('program_id', $programId);
            if ($match) {
                return $match;
            }
        }

        // Rank 2: Department + Degree Tier match
        if ($departmentId && $progTier !== '') {
            $match = $filtered->first(function ($c) use ($departmentId, $progTier) {
                return (string) $c->department_id === (string) $departmentId
                    && empty($c->program_id)
                    && static::normalizeTier($c->entry_mode) === $progTier;
            });
            if ($match) {
                return $match;
            }
        }

        // Rank 3: Department Generic match
        if ($departmentId) {
            $match = $filtered->first(function ($c) use ($departmentId) {
                return (string) $c->department_id === (string) $departmentId
                    && empty($c->program_id);
            });
            if ($match) {
                return $match;
            }
        }

        // Rank 4: Faculty + Degree Tier match
        if ($facultyId && $progTier !== '') {
            $match = $filtered->first(function ($c) use ($facultyId, $progTier) {
                return (string) $c->faculty_id === (string) $facultyId
                    && empty($c->department_id)
                    && empty($c->program_id)
                    && static::normalizeTier($c->entry_mode) === $progTier;
            });
            if ($match) {
                return $match;
            }
        }

        // Rank 5: Faculty Generic match
        if ($facultyId) {
            $match = $filtered->first(function ($c) use ($facultyId) {
                return (string) $c->faculty_id === (string) $facultyId
                    && empty($c->department_id)
                    && empty($c->program_id);
            });
            if ($match) {
                return $match;
            }
        }

        // Rank 6: University-Wide Degree Tier match
        if ($progTier !== '') {
            $match = $filtered->first(function ($c) use ($progTier) {
                return empty($c->faculty_id)
                    && empty($c->department_id)
                    && empty($c->program_id)
                    && static::normalizeTier($c->entry_mode) === $progTier;
            });
            if ($match) {
                return $match;
            }
        }

        // Rank 7: University-Wide Generic match
        return $filtered->first(function ($c) {
            return empty($c->faculty_id)
                && empty($c->department_id)
                && empty($c->program_id);
        });
    }
}
