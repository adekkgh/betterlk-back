<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalRating extends Model
{
    protected $fillable = [
        'journal_id',
        'student_id',
        'rating_score',
        'exam_score'
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
