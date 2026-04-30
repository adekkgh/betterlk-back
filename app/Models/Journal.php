<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $fillable = [
        'subject_id',
        'group_id',
        'professor_id',
        'semester',
        'year'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class)->with('students.user');
    }

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function entries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function ratings()
    {
        return $this->hasMany(JournalRating::class);
    }
}
