<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HostelBooking;
use App\Models\Session;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HostelBookingController extends Controller
{
    public function index(Request $request)
    {
        $currentSession = Session::current();
        $sessionId = $request->input('session_id', $currentSession?->id);

        $bookings = HostelBooking::with([
            'student.user',
            'student.department',
            'room.floor.block.hostel',
            'session',
            'invoice.payments'
        ])
            ->when($sessionId, function ($query, $sessionId) {
                $query->where('session_id', $sessionId);
            })
            ->latest()
            ->get();

        $sessions = Session::latest()->get(['id', 'name']);

        return Inertia::render('Admin/Hostels/Bookings', [
            'bookings' => $bookings,
            'sessions' => $sessions,
            'filters' => [
                'session_id' => $sessionId,
            ],
        ]);
    }
}
