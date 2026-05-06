<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessorSubjectAssignment extends Model
{
    protected $fillable = [
        'professor_id',
        'subject_id'
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }
}
