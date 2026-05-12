<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Week extends Model
{
    protected $fillable = ['course_id', 'title', 'order'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function completedLessonsCount()
    {
        return $this->lessons()->where('is_completed', true)->count();
    }

    public function progressPercentage()
    {
        $total = $this->lessons()->count();
        return $total > 0 ? round(($this->completedLessonsCount() / $total) * 100) : 0;
    }

    public function quizzesCount(): int
    {
        // Count quizzes generated for any lesson title in this week
        $titles = $this->lessons->pluck('title')->toArray();
        if (empty($titles) || !$this->course) {
            return 0;
        }
        return \App\Models\Quiz::where('user_id', $this->course->user_id)
            ->whereIn('title', $titles)->count();
    }

    public function classesLeftCount()
    {
        return $this->lessons()->where('is_completed', false)->count();
    }
}
