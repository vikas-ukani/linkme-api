<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostReactions extends Model
{
    protected $table = 'postreactions';

    protected $fillable = [
        'postid', 'reactionType', 'reactionBy'
    ];
}
