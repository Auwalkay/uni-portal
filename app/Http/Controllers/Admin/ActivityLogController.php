<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with(['causer'])->latest();

        // Keyword Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('subject_type', 'like', "%{$search}%")
                  ->orWhere('subject_id', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Action / Event Filter
        if ($request->filled('event') && $request->event !== 'all') {
            $query->where('description', $request->event);
        }

        // Module / Subject Type Filter
        if ($request->filled('subject_type') && $request->subject_type !== 'all') {
            $subjectType = $request->subject_type;
            if (!str_contains($subjectType, '\\')) {
                $subjectType = 'App\\Models\\' . $subjectType;
            }
            $query->where('subject_type', $subjectType);
        }

        // Date Range Filters
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', Carbon::parse($request->start_date)->startOfDay());
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        // KPI Summary Analytics (for current filtered scope)
        $statsQuery = clone $query;
        $stats = [
            'total_events' => (clone $statsQuery)->count(),
            'today_events' => (clone $statsQuery)->whereDate('created_at', Carbon::today())->count(),
            'creates_count' => (clone $statsQuery)->where('description', 'created')->count(),
            'updates_count' => (clone $statsQuery)->where('description', 'updated')->count(),
            'deletes_count' => (clone $statsQuery)->where('description', 'deleted')->count(),
        ];

        $perPage = $request->integer('per_page', 20);
        if (!in_array($perPage, [10, 20, 50, 100])) {
            $perPage = 20;
        }

        $logs = $query->paginate($perPage)->withQueryString();

        // Get distinct subject types from activity log database
        $rawSubjectTypes = Activity::query()
            ->whereNotNull('subject_type')
            ->select('subject_type')
            ->distinct()
            ->pluck('subject_type');

        $subjectTypes = $rawSubjectTypes->map(function ($type) {
            $basename = class_basename($type);
            return [
                'full' => $type,
                'short' => $basename,
                'label' => preg_replace('/(?<!^)[A-Z]/', ' $0', $basename),
            ];
        })->unique('short')->values();

        return Inertia::render('Admin/ActivityLogs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'subjectTypes' => $subjectTypes,
            'filters' => [
                'search' => $request->query('search', ''),
                'event' => $request->query('event', 'all'),
                'subject_type' => $request->query('subject_type', 'all'),
                'start_date' => $request->query('start_date', ''),
                'end_date' => $request->query('end_date', ''),
                'per_page' => $perPage,
            ],
        ]);
    }
}
