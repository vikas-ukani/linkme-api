<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostComments extends Model
{
    protected $table = 'postcomments';

    protected $fillable = [
        'postid', 'comment', 'commentBy', 'userTags'
    ];
}
