<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessorGroup extends Model
{
    protected $fillable = [
        'professor_id',
        'group_id'
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function professor()
    {
        return $this->belongsTo(User::class, 'professor_id');
    }
}
