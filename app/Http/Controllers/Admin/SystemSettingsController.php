<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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
            ]
        ]);
    }
}
