<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookLoan extends Model
{
    protected $fillable = [
        'book_id', 'user_id', 'borrowed_at', 'due_at', 
        'returned_at', 'status', 'user_notes', 'admin_notes'
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class)->withTrashed(); // include soft-deleted books in logs
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
