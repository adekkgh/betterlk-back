<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    protected $fillable = [
        'journal_id',
        'student_id',
        'date',
        'is_absent',
        'score'
    ];

    protected $casts = [
        'date'      => 'date',
        'is_absent' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
