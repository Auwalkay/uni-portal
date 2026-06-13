<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::where('user_id', auth()->id())
            ->with('latestMessage')
            ->latest()
            ->get();

        return Inertia::render('Student/Support/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'message' => 'required|string',
        ]);

        DB::transaction(function () use ($validated) {
            $ticket = SupportTicket::create([
                'user_id' => auth()->id(),
                'category' => $validated['category'],
                'subject' => $validated['subject'],
                'priority' => $validated['priority'],
                'status' => 'open',
            ]);

            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $validated['message'],
            ]);
        });

        return redirect()->route('student.support.index')->with('success', 'Support ticket created successfully.');
    }

    public function show(SupportTicket $ticket)
    {
        // Ensure user owns the ticket
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $ticket->load(['messages.user']);

        return Inertia::render('Student/Support/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        DB::transaction(function () use ($ticket, $validated) {
            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $validated['message'],
            ]);

            // Reopen ticket if it was resolved/closed
            if (in_array($ticket->status, ['resolved', 'closed'])) {
                $ticket->update(['status' => 'open']);
            }
        });

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }
}
