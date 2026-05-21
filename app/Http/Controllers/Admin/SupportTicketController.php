<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view_inventory'); // Or a specific permission like 'manage_support'
        // For now using view_inventory as a placeholder or assuming admin access

        $query = SupportTicket::with(['user', 'latestMessage']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $tickets = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Support/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'category']),
        ]);
    }

    public function show(SupportTicket $ticket)
    {
        Gate::authorize('view_inventory');

        $ticket->load(['user', 'messages.user']);

        return Inertia::render('Admin/Support/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        Gate::authorize('view_inventory'); // Assuming manage_inventory or similar

        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'priority' => 'required|in:low,medium,high',
        ]);

        $ticket->update($validated);

        return redirect()->back()->with('success', 'Ticket updated successfully.');
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        Gate::authorize('view_inventory');

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        DB::transaction(function () use ($ticket, $validated) {
            SupportMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'message' => $validated['message'],
            ]);

            // Update status to in_progress when admin replies
            if ($ticket->status === 'open') {
                $ticket->update(['status' => 'in_progress']);
            }
        });

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }
}
