<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Posthashtags extends Model
{
    protected $table = 'posthashtags';

    protected $fillable = ['postid', 'hashid'];
}
