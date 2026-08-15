<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bulletin;
use App\Jobs\SendBulletinEmailJob;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommunicationController extends Controller
{
    public function index(Request $request)
    {
        abort_if(!auth()->user()->can('manage_bulk_communications'), 403, 'Unauthorized action.');

        $bulletins = Bulletin::with('author')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/Communication/Index', [
            'bulletins' => $bulletins,
        ]);
    }

    public function create()
    {
        abort_if(!auth()->user()->can('manage_bulk_communications'), 403, 'Unauthorized action.');
        return Inertia::render('Admin/Communication/Create');
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('manage_bulk_communications'), 403, 'Unauthorized action.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'target_audience' => 'required|in:all,students,staff',
            'is_pinned' => 'boolean',
            'document' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:10240',
        ]);

        if (empty($validated['content']) && !$request->hasFile('document')) {
            return back()->withErrors(['content' => 'Either written content or a scanned document is required.']);
        }

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('announcements', 'public');
        }

        $bulletin = Bulletin::create([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? '',
            'document_path' => $documentPath,
            'target_audience' => $validated['target_audience'],
            'is_pinned' => $validated['is_pinned'] ?? false,
            'created_by' => auth()->id(),
            'published_at' => now(),
        ]);

        // Dispatch background email sending job
        SendBulletinEmailJob::dispatch($bulletin);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement published and emails queued successfully.');
    }

    public function edit(Bulletin $bulletin)
    {
        abort_if(!auth()->user()->can('manage_bulk_communications'), 403, 'Unauthorized action.');
        return Inertia::render('Admin/Communication/Edit', [
            'bulletin' => $bulletin,
        ]);
    }

    public function update(Request $request, Bulletin $bulletin)
    {
        abort_if(!auth()->user()->can('manage_bulk_communications'), 403, 'Unauthorized action.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'target_audience' => 'required|in:all,students,staff',
            'is_pinned' => 'boolean',
            'document' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:10240',
        ]);

        if (empty($validated['content']) && !$request->hasFile('document') && !$bulletin->document_path) {
            return back()->withErrors(['content' => 'Either written content or a scanned document is required.']);
        }

        $documentPath = $bulletin->document_path;
        if ($request->hasFile('document')) {
            // Delete old file if exists
            if ($bulletin->document_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($bulletin->document_path);
            }
            $documentPath = $request->file('document')->store('announcements', 'public');
        }

        $bulletin->update([
            'title' => $validated['title'],
            'content' => $validated['content'] ?? '',
            'document_path' => $documentPath,
            'target_audience' => $validated['target_audience'],
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Bulletin $bulletin)
    {
        abort_if(!auth()->user()->can('manage_bulk_communications'), 403, 'Unauthorized action.');

        if ($bulletin->document_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($bulletin->document_path);
        }

        $bulletin->delete();

        return back()->with('success', 'Announcement deleted successfully.');
    }
}
