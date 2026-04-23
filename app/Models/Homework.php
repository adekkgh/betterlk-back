<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homework extends Model
{
    protected $table = 'homeworks';

    protected $fillable = [
        'group_id', 'created_by', 'title', 'description',
        'type', 'max_score', 'deadline',
        'deadline_extended', 'extended_deadline',
    ];

    protected $casts = [
        'deadline'          => 'datetime',
        'extended_deadline' => 'datetime',
        'deadline_extended' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class);
    }

    // active deadline considering its extension
    public function getActivateDeadlineAttribute(): \Carbon\Carbon
    {
        return $this->deadline_extended && $this->extended_deadline
            ? $this->extended_deadline
            : $this->deadline;
    }

    public function isExpired(): bool
    {
        return $this->activate_deadline->isPast();
    }
}
