<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Book extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'library_category_id', 'title', 'author', 'isbn', 
        'publisher', 'publish_year', 'is_ebook', 'ebook_file_path',
        'ebook_url', 'total_copies', 'available_copies', 
        'shelf_location', 'description', 'cover_image_path'
    ];

    protected $casts = [
        'is_ebook' => 'boolean',
    ];

    protected $appends = ['ebook_download_url'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LibraryCategory::class, 'library_category_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(BookLoan::class);
    }

    public function getEbookDownloadUrlAttribute()
    {
        if (!$this->is_ebook) return null;
        return $this->ebook_file_path ? asset('storage/' . $this->ebook_file_path) : $this->ebook_url;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }
}
