<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\SystemSetting;

class SystemSettingsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings/Index', [
            'stats' => [
                'roles_count' => Role::count(),
                'permissions_count' => Permission::count(),
                'admin_users' => User::role('admin')->count(),
                'staff_users' => User::role('staff')->count(),
            ],
            'settings' => [
                'matric_format' => SystemSetting::get('matric_format', 'MIU{YEAR}{SEQUENCE}'),
                'admin_charge_amount' => SystemSetting::get('admin_charge_amount', 250000),
                'admin_charge_enabled' => SystemSetting::get('admin_charge_enabled', true),
                'admin_charge_splittable' => SystemSetting::get('admin_charge_splittable', true),
                'payment_gateway' => SystemSetting::get('payment_gateway', env('PAYMENT_GATEWAY', 'paystack')),
                'application_fee' => SystemSetting::get('application_fee', 100000),
                'enforce_school_fee_for_results' => filter_var(SystemSetting::get('enforce_school_fee_for_results', false), FILTER_VALIDATE_BOOLEAN),
                'enforce_hostel_fee_for_results' => filter_var(SystemSetting::get('enforce_hostel_fee_for_results', false), FILTER_VALIDATE_BOOLEAN),
                'enable_exam_card_download' => filter_var(SystemSetting::get('enable_exam_card_download', true), FILTER_VALIDATE_BOOLEAN),
                'enable_hostel_booking' => filter_var(SystemSetting::get('enable_hostel_booking', true), FILTER_VALIDATE_BOOLEAN),
                'hostel_booking_expiry_days' => intval(SystemSetting::get('hostel_booking_expiry_days', 2)),
                'promote_pending_payments' => filter_var(SystemSetting::get('promote_pending_payments', false), FILTER_VALIDATE_BOOLEAN),
                'late_fee_enabled' => filter_var(SystemSetting::get('late_fee_enabled', true), FILTER_VALIDATE_BOOLEAN),
            ]
        ]);
    }

    public function updateSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable',
        ]);

        $key = $request->key;
        $newValue = is_bool($request->value) ? ($request->value ? '1' : '0') : (string) $request->value;
        $oldValue = (string) SystemSetting::get($key, '');

        if ($oldValue !== $newValue) {
            SystemSetting::set($key, $newValue);
            \App\Services\AcademicCacheService::clearAll();

            activity('system_settings')
                ->causedBy(auth()->user())
                ->withProperties([
                    'key' => $key,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'ip_address' => $request->ip(),
                ])
                ->log("Updated system setting [{$key}] from '{$oldValue}' to '{$newValue}'");
        }

        return back()->with('success', 'Setting updated successfully.');
    }
}
