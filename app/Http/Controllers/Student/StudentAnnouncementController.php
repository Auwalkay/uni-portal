<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Bulletin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class StudentAnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->get('page', 1);

        $bulletins = Cache::remember("student_bulletins_page_{$page}", 60 * 10, function () {
            return Bulletin::with('author')
                ->whereIn('target_audience', ['all', 'students'])
                ->orderBy('is_pinned', 'desc')
                ->orderBy('published_at', 'desc')
                ->paginate(10);
        });

        return Inertia::render('Student/Announcements/Index', [
            'announcements' => $bulletins,
        ]);
    }
}
