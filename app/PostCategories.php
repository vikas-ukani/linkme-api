<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostCategories extends Model
{
    protected $table = 'postcategories';

    protected $fillable = [
        'postid', 'categoryid'
    ];
}
