<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class FeeType extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }
    protected $fillable = ['name', 'slug', 'description', 'is_one_time'];

    protected $casts = [
        'is_one_time' => 'boolean',
    ];

    public function configurations()
    {
        return $this->hasMany(FeeConfiguration::class);
    }
}
