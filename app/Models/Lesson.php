<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'week_id',
        'course_id',
        'title',
        'description',
        'duration_minutes',
        'order',
        'is_completed'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
