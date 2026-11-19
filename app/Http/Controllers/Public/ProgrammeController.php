<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\FeeConfiguration;
use App\Models\Programme;
use App\Services\AcademicCacheService;
use App\Services\Finance\FeeService;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ProgrammeController extends Controller
{
    public function index(FeeService $feeService): Response
    {
        $payload = Cache::remember('public_programmes_fees_schedule_v2', 1800, function () use ($feeService) {
            $ugSession = AcademicCacheService::getCurrentSessionForStream('UG', 'semester')
                ?? AcademicCacheService::getCurrentSession();
            $pgSession = AcademicCacheService::getCurrentSessionForStream('PG', 'semester')
                ?? $ugSession;

            $sessionIds = array_values(array_unique(array_filter([
                $ugSession?->id,
                $pgSession?->id,
            ])));

            $configsBySession = collect();
            if (! empty($sessionIds)) {
                $configsBySession = FeeConfiguration::whereIn('session_id', $sessionIds)
                    ->where('is_compulsory', true)
                    ->where('is_milestone_fee', false)
                    ->with('feeType:id,name,slug,is_one_time')
                    ->get()
                    ->groupBy('session_id');
            }

            $programmes = Programme::active()
                ->with(['department:id,name,faculty_id', 'department.faculty:id,name'])
                ->orderBy('name')
                ->get()
                ->map(function (Programme $programme) use ($feeService, $ugSession, $pgSession, $configsBySession) {
                    $isPg = $programme->isPostgraduate();
                    $targetSession = $isPg ? $pgSession : $ugSession;
                    $sessionConfigs = $targetSession
                        ? $configsBySession->get($targetSession->id, collect())
                        : collect();

                    if ($isPg) {
                        $schedule = $feeService->resolveScheduleForProgramme($programme, $sessionConfigs);
                        $schedulesByMode = null;
                    } else {
                        $schedulesByMode = $feeService->resolveUgSchedulesByEntryMode($programme, $sessionConfigs);
                        $schedule = $schedulesByMode['UTME'];
                    }

                    return [
                        'id' => $programme->id,
                        'name' => $programme->name,
                        'type' => strtoupper((string) ($programme->type ?? ($isPg ? 'PG' : 'UG'))),
                        'academic_stream' => $isPg ? 'PG' : 'UG',
                        'duration' => $programme->duration,
                        'department_id' => $programme->department_id,
                        'department_name' => $programme->department?->name ?? 'General',
                        'faculty_id' => $programme->department?->faculty_id,
                        'faculty_name' => $programme->department?->faculty?->name ?? 'Other Faculties',
                        'session_name' => $targetSession?->name ?? 'Current Session',
                        'total_fee' => $schedule['total_fee'],
                        'fee_items' => $schedule['items'],
                        'schedules_by_mode' => $schedulesByMode,
                    ];
                })
                ->values();

            $faculties = $programmes
                ->filter(fn ($p) => ! empty($p['faculty_id']))
                ->unique('faculty_id')
                ->map(fn ($p) => [
                    'id' => $p['faculty_id'],
                    'name' => $p['faculty_name'],
                ])
                ->sortBy('name')
                ->values();

            return [
                'programmes' => $programmes,
                'faculties' => $faculties,
                'ugSessionName' => $ugSession?->name,
                'pgSessionName' => $pgSession?->name,
            ];
        });

        return Inertia::render('Public/Programmes', $payload);
    }
}
