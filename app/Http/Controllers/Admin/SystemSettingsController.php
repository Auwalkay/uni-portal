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
            ]
        ]);
    }

    public function updateSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string',
        ]);

        SystemSetting::set($request->key, $request->value);

        return back()->with('success', 'Setting updated successfully.');
    }
}
