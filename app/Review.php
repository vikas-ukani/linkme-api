<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Review extends Model

{

    protected $table = 'review';
    protected $fillable = [
        'stars', 'comments', 'customer_id'
    ];
}
