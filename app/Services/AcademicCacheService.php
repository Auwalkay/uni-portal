<?php

namespace App\Services;

use App\Models\Faculty;
use App\Models\Department;
use App\Models\Programme;
use App\Models\State;
use App\Models\Scholarship;
use App\Models\Session;
use Illuminate\Support\Facades\Cache;

class AcademicCacheService
{
    const TTL = 86400; // 24 hours

    public static function getFaculties()
    {
        return Cache::remember('faculties_with_departments', self::TTL, function () {
            return Faculty::with('departments:id,name,faculty_id')->orderBy('name')->get(['id', 'name']);
        });
    }

    public static function getProgrammes()
    {
        return Cache::remember('all_programmes_list_v2', self::TTL, function () {
            return Programme::orderBy('name')->get(['id', 'name', 'department_id']);
        });
    }

    public static function getStates()
    {
        return Cache::remember('states_with_lgas', self::TTL, function () {
            return State::with('lgas:id,name,state_id')->orderBy('name')->get(['id', 'name']);
        });
    }

    public static function getScholarships()
    {
        return Cache::remember('all_scholarships', self::TTL, function () {
            return Scholarship::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        });
    }

    public static function getSessions()
    {
        return Cache::remember('academic_sessions_list', self::TTL, function () {
            return Session::latest()->get(['id', 'name']);
        });
    }

    public static function getCurrentSession()
    {
        return Cache::remember('current_session', self::TTL, function () {
            return Session::where('is_current', true)->first();
        });
    }

    public static function getCurrentSemester()
    {
        return Cache::remember('current_semester', self::TTL, function () {
            return \App\Models\Semester::where('is_current', true)->with('session')->first();
        });
    }

    public static function getAllFaculties()
    {
        return Cache::remember('all_faculties', self::TTL, fn() => Faculty::orderBy('name')->get());
    }

    public static function getAllDepartments()
    {
        return Cache::remember('all_departments', self::TTL, fn() => Department::orderBy('name')->get());
    }

    public static function getAllProgrammes()
    {
        return Cache::remember('all_programmes', self::TTL, fn() => Programme::orderBy('name')->get());
    }

    public static function getFacultiesFull()
    {
        return Cache::remember('faculties_with_departments_full', self::TTL, fn() => Faculty::with('departments.units')->get());
    }

    public static function getNonAcademicDepartments()
    {
        return Cache::remember('non_academic_departments', self::TTL, fn() => \App\Models\Department::whereNull('faculty_id')->with('units')->get());
    }

    public static function getDesignations()
    {
        return Cache::remember('staff_designations_list', 3600, function () {
            return \App\Models\Designation::where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->toArray();
        });
    }

    public static function clearAll()
    {
        Cache::forget('faculties_with_departments');
        Cache::forget('all_programmes_list');
        Cache::forget('all_programmes_list_v2');
        Cache::forget('states_with_lgas');
        Cache::forget('all_scholarships');
        Cache::forget('academic_sessions_list');
        Cache::forget('current_session');
        Cache::forget('current_semester');
        Cache::forget('all_faculties');
        Cache::forget('all_departments');
        Cache::forget('all_programmes');
        Cache::forget('faculties_with_departments_full');
        Cache::forget('non_academic_departments');
        Cache::forget('staff_designations_list');
    }
}
