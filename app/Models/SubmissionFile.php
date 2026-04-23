<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFile extends Model
{
    protected $table = 'submissions_files';

    protected $fillable = [
        'submission_id', 'file_path',
        'file_type', 'original_name', 'file_size',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(HomeworkSubmission::class, 'submission_id');
    }

    // completed URL for download
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
