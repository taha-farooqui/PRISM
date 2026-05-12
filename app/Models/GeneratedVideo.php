<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedVideo extends Model
{
    protected $fillable = ['user_id', 'topic', 'quality', 'status', 'progress_phase', 'progress_percent', 'job_id', 'video_path', 'subtitle_path', 'error_message'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
