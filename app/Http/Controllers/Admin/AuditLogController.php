<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user:id,name,email')->latest();

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by module
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Search in details
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Settings/Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['user_id', 'module', 'action', 'search']),
            'users' => User::has('auditLogs')->get(['id', 'name']),
            'modules' => AuditLog::distinct()->pluck('module')->toArray(),
            'actions' => AuditLog::distinct()->pluck('action')->toArray(),
        ]);
    }
}
