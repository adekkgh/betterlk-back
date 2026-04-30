<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessorSubject extends Model
{
    protected $fillable = [
        'professor_id',
        'subject_id',
        'group_id'
    ];

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
