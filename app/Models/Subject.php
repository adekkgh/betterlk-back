<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name'
    ];

    public function journals()
    {
        return $this->hasMany(Journal::class);
    }

    public function professorSubjects()
    {
        return $this->hasMany(ProfessorSubject::class);
    }
}
