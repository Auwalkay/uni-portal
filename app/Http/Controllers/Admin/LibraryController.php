<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\LibraryCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LibraryController extends Controller
{
    private function checkLibraryManager(Request $request)
    {
        if (!$request->user()->hasAnyPermission(['manage_library_books', 'manage_library_borrows']) && !$request->user()->hasRole('admin')) {
            return redirect()->route('admin.library.history');
        }
        return null;
    }

    public function index(Request $request)
    {
        if ($redirect = $this->checkLibraryManager($request)) {
            return $redirect;
        }

        Gate::authorize('view_library');

        $query = Book::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('library_category_id', $request->category_id);
        }

        if ($request->filled('is_ebook')) {
            $query->where('is_ebook', $request->is_ebook === 'true');
        }

        $books = $query->latest()->paginate(15)->withQueryString();
        $categories = LibraryCategory::withCount('books')->get();

        // Get borrow requests & active loans
        $pendingLoans = BookLoan::with(['book', 'user'])->where('status', 'pending')->latest()->get();
        $activeLoans = BookLoan::with(['book', 'user'])->whereIn('status', ['approved', 'active', 'overdue'])->latest()->get();

        // Stats
        $stats = [
            'total_books' => Book::count(),
            'ebooks_count' => Book::where('is_ebook', true)->count(),
            'active_loans' => BookLoan::whereIn('status', ['approved', 'active'])->count(),
            'overdue_loans' => BookLoan::where('status', 'overdue')->count(),
            'pending_requests' => BookLoan::where('status', 'pending')->count(),
        ];

        return Inertia::render('Admin/Library/Index', [
            'books' => $books,
            'categories' => $categories,
            'pendingLoans' => $pendingLoans,
            'activeLoans' => $activeLoans,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category_id', 'is_ebook']),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage_library_books');

        $validated = $request->validate([
            'library_category_id' => 'required|exists:library_categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:255|unique:books,isbn',
            'publisher' => 'nullable|string|max:255',
            'publish_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
            'is_ebook' => 'required|boolean',
            'total_copies' => 'required_if:is_ebook,false|nullable|integer|min:0',
            'shelf_location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'ebook_file' => 'nullable|file|mimes:pdf,epub|max:20480', // Max 20MB
            'ebook_url' => 'nullable|url|max:255',
        ]);

        if ($validated['is_ebook']) {
            $validated['total_copies'] = 0;
            $validated['available_copies'] = 0;
            $validated['shelf_location'] = null;
        } else {
            $validated['available_copies'] = $validated['total_copies'] ?? 1;
            $validated['ebook_file_path'] = null;
            $validated['ebook_url'] = null;
        }

        // Handle Cover Image
        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('book-covers', 'public');
            $validated['cover_image_path'] = $path;
        }

        // Handle E-Book File privately
        if ($validated['is_ebook'] && $request->hasFile('ebook_file')) {
            $path = $request->file('ebook_file')->store('ebooks', 'local');
            $validated['ebook_file_path'] = $path;
        }

        Book::create($validated);

        return redirect()->back()->with('success', 'Book added to catalog successfully.');
    }

    public function update(Request $request, Book $book)
    {
        Gate::authorize('manage_library_books');

        $validated = $request->validate([
            'library_category_id' => 'required|exists:library_categories,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:255|unique:books,isbn,' . $book->id,
            'publisher' => 'nullable|string|max:255',
            'publish_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
            'is_ebook' => 'required|boolean',
            'total_copies' => 'required_if:is_ebook,false|nullable|integer|min:0',
            'shelf_location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'ebook_file' => 'nullable|file|mimes:pdf,epub|max:20480',
            'ebook_url' => 'nullable|url|max:255',
        ]);

        if ($validated['is_ebook']) {
            $validated['total_copies'] = 0;
            $validated['available_copies'] = 0;
            $validated['shelf_location'] = null;
        } else {
            // Adjust available copies based on total changes
            $difference = ($validated['total_copies'] ?? 1) - $book->total_copies;
            $validated['available_copies'] = $book->available_copies + $difference;

            if ($validated['available_copies'] < 0) {
                return redirect()->back()->with('error', 'Cannot reduce total copies below what is currently checked out.');
            }
            $validated['ebook_file_path'] = null;
            $validated['ebook_url'] = null;
        }

        // Handle Cover Image
        if ($request->hasFile('cover_image')) {
            if ($book->cover_image_path) {
                Storage::disk('public')->delete($book->cover_image_path);
            }
            $path = $request->file('cover_image')->store('book-covers', 'public');
            $validated['cover_image_path'] = $path;
        }

        // Handle E-Book File
        if ($validated['is_ebook'] && $request->hasFile('ebook_file')) {
            if ($book->ebook_file_path) {
                Storage::disk('local')->delete($book->ebook_file_path);
            }
            $path = $request->file('ebook_file')->store('ebooks', 'local');
            $validated['ebook_file_path'] = $path;
        }

        $book->update($validated);

        return redirect()->back()->with('success', 'Book details updated successfully.');
    }

    public function destroy(Book $book)
    {
        Gate::authorize('manage_library_books');

        // Check if there are active loans
        if ($book->loans()->whereIn('status', ['approved', 'active', 'overdue'])->exists()) {
            return redirect()->back()->with('error', 'Cannot delete a book that is currently checked out.');
        }

        $book->delete();

        return redirect()->back()->with('success', 'Book deleted from catalog.');
    }

    public function storeCategory(Request $request)
    {
        Gate::authorize('manage_library_books');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:library_categories,name',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        LibraryCategory::create($validated);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function approveLoan(BookLoan $loan)
    {
        Gate::authorize('manage_library_borrows');

        try {
            DB::transaction(function () use ($loan) {
                // Fetch book with pessimistic locking
                $book = Book::where('id', $loan->book_id)->lockForUpdate()->firstOrFail();

                if ($book->available_copies <= 0) {
                    throw new \Exception('No physical copies of this book are currently available.');
                }

                $book->decrement('available_copies');
                $loan->update([
                    'status' => 'approved',
                    'borrowed_at' => now(),
                    'due_at' => now()->addDays(14), // Standard 2 weeks loan
                ]);
            });

            return redirect()->back()->with('success', 'Borrow request approved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function rejectLoan(Request $request, BookLoan $loan)
    {
        Gate::authorize('manage_library_borrows');

        $request->validate([
            'admin_notes' => 'nullable|string|max:255',
        ]);

        $loan->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
        ]);

        return redirect()->back()->with('success', 'Borrow request rejected.');
    }

    public function returnBook(BookLoan $loan)
    {
        Gate::authorize('manage_library_borrows');

        try {
            DB::transaction(function () use ($loan) {
                $book = Book::where('id', $loan->book_id)->lockForUpdate()->firstOrFail();
                
                $book->increment('available_copies');
                $loan->update([
                    'status' => 'returned',
                    'returned_at' => now(),
                ]);
            });

            return redirect()->back()->with('success', 'Book return recorded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error recording return: ' . $e->getMessage());
        }
    }

    public function history(Request $request)
    {
        Gate::authorize('view_library');

        $query = Book::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('library_category_id', $request->category_id);
        }

        if ($request->filled('is_ebook')) {
            $query->where('is_ebook', $request->is_ebook === 'true');
        }

        $books = $query->latest()->paginate(12)->withQueryString();
        $categories = LibraryCategory::all();

        // Get user's loan history
        $myLoans = BookLoan::with('book.category')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return Inertia::render('Admin/Library/History', [
            'books' => $books,
            'categories' => $categories,
            'myLoans' => $myLoans,
            'filters' => $request->only(['search', 'category_id', 'is_ebook']),
        ]);
    }

    public function requestBook(Request $request)
    {
        Gate::authorize('request_library_book');

        $request->validate([
            'book_id' => 'required|exists:books,id',
            'user_notes' => 'nullable|string|max:255',
        ]);

        $book = Book::findOrFail($request->book_id);

        if ($book->is_ebook) {
            return redirect()->back()->with('error', 'E-Books do not require borrow requests. You can read them instantly.');
        }

        // Check if user has already borrowed this book or has a pending request
        $existing = BookLoan::where('user_id', auth()->id())
            ->where('book_id', $book->id)
            ->whereIn('status', ['pending', 'approved', 'active', 'overdue'])
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'You already have an active request or checkout for this book.');
        }

        if ($book->available_copies <= 0) {
            return redirect()->back()->with('error', 'All physical copies of this book are currently checked out.');
        }

        $loan = BookLoan::create([
            'book_id' => $book->id,
            'user_id' => auth()->id(),
            'status' => 'pending',
            'user_notes' => $request->user_notes,
        ]);

        // Notify librarians
        $librarians = User::permission('manage_library_borrows')->get();
        if ($librarians->count() > 0) {
            Notification::send($librarians, new \App\Notifications\NewBookRequestNotification($loan));
        }

        return redirect()->back()->with('success', 'Borrow request submitted successfully. You will be notified when it is approved.');
    }

    public function downloadEbook(Book $book)
    {
        Gate::authorize('view_library');

        if (!$book->is_ebook) {
            abort(404, 'E-Book not found');
        }

        if ($book->ebook_file_path) {
            if (Storage::disk('local')->exists($book->ebook_file_path)) {
                return Storage::disk('local')->download($book->ebook_file_path, Str::slug($book->title) . '.pdf');
            }
            abort(404, 'File not found on storage disk.');
        }

        if ($book->ebook_url) {
            return redirect()->away($book->ebook_url);
        }

        abort(404, 'E-Book resource path not defined.');
    }
}
