<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppNews extends Model
{
    protected $table = 'app_news';
    protected $fillable = ['type', 'url', 'title', 'lead', 'content', 'image', 'public_at'];
    protected $casts = [
    ];
}
