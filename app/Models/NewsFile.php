<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsFile extends Model
{
    protected $fillable = [
        'news_id',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'sort_order'
    ];
}
