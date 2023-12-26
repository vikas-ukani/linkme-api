<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostMedias extends Model
{
    protected $table = 'postmedias';

    protected $fillable = [
        'postid', 'mediaUrl', 'type', 'width', 'height'
    ];
}
