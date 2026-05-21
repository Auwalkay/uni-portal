<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OLevelResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'exam_type',
        'exam_year',
        'exam_number',
        'center_number',
        'subjects',
        'scanned_copy_path'
    ];

    protected $casts = [
        'subjects' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
