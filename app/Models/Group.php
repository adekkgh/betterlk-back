<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'course', 'specialization_id'];

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    public function students()
    {
        return $this->hasMany(StudentProfile::class);
    }

    public function professors()
    {
        return $this->hasMany(ProfessorGroup::class);
    }
}
