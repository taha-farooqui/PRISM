<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProgress extends Model
{
    protected $table = 'user_progress';

    protected $fillable = [
        'user_id',
        'course_id',
        'current_class_id',
        'remaining_seconds',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function currentClass(): BelongsTo
    {
        return $this->belongsTo(ClassLesson::class, 'current_class_id');
    }

    /**
     * Format remaining seconds as HH:MM:SS
     */
    public function getFormattedTimeAttribute(): string
    {
        $hours = floor($this->remaining_seconds / 3600);
        $minutes = floor(($this->remaining_seconds % 3600) / 60);
        $seconds = $this->remaining_seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
