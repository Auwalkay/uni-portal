<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Bulletin extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'content',
        'document_path',
        'target_audience',
        'created_by',
        'is_pinned',
        'published_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saved(function ($bulletin) {
            static::clearBulletinsCache();
        });

        static::deleted(function ($bulletin) {
            static::clearBulletinsCache();
        });
    }

    public static function clearBulletinsCache()
    {
        // Forget dashboard cache
        Cache::forget('student_dashboard_bulletins');
        Cache::forget('staff_dashboard_bulletins');
        
        // Forget paginated page caches (clear up to 20 pages to cover typical pagination counts)
        for ($page = 1; $page <= 20; $page++) {
            Cache::forget("student_bulletins_page_{$page}");
        }
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
