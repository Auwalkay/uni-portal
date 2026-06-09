<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookLoan;
use App\Models\LibraryCategory;
use App\Models\User;
use App\Notifications\NewBookRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LibraryController extends Controller
{
    public function index(Request $request)
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

        // Get student's loan history
        $myLoans = BookLoan::with('book.category')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return Inertia::render('Student/Library/Index', [
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

        // Check if student has already borrowed this book or has a pending request
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
            Notification::send($librarians, new NewBookRequestNotification($loan));
        }

        return redirect()->back()->with('success', 'Borrow request submitted successfully. You will be notified when it is approved.');
    }

    public function downloadEbook(Book $book)
    {
        Gate::authorize('view_library');

        if (!$book->is_ebook) {
            abort(404, 'E-Book not found');
        }

        // Secure Access Verification (checking if user status is active if student)
        $user = auth()->user();
        if ($user->student && $user->student->status !== 'active') {
            return redirect()->back()->with('error', 'Only active students can download e-books.');
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
