<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'body'
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(NewsFile::class)->orderBy('sort_order');
    }
}
