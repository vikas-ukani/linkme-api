<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Newsfeeds extends Model
{
    protected $table = 'newsfeeds';
    protected $fillable = ['postid', 'visibilityType', 'createdBy', 'feedJson', 'commentsCount', 'categoryJson', 'hashJson', 'reactionJson', 'searchIndex'];


    
}
