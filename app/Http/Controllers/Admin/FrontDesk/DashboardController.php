<?php

namespace App\Http\Controllers\Admin\FrontDesk;

use App\Http\Controllers\Controller;
use App\Models\FrontDesk\Complaint;
use App\Models\FrontDesk\Enquiry;
use App\Models\FrontDesk\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/FrontDesk/Dashboard', [
            'stats' => [
                'active_visitors' => Visitor::whereNull('check_out')->count(),
                'total_visitors_today' => Visitor::whereDate('check_in', today())->count(),
                'pending_complaints' => Complaint::where('status', 'pending')->count(),
                'open_enquiries' => Enquiry::where('status', 'open')->count(),
            ],
            'recent_visitors' => Visitor::latest()->take(5)->get(),
            'recent_complaints' => Complaint::latest()->take(5)->get(),
        ]);
    }
}
