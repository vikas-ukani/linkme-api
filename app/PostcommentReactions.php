<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostcommentReactions extends Model
{
    protected $table = 'postcommentreactions';

    protected $fillable = [
        'commentid', 'reactionType', 'reactionBy'
    ];
}
