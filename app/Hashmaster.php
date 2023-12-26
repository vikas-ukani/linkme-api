<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Hashmaster extends Model
{
    protected $table = 'hashmaster';
    protected $fillable = [
        'hastags'
    ];
}
