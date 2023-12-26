<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Weeklist extends Model
{
    protected $table = 'availablityweeks';
    protected $fillable = ['week_short', 'week_fullname'];
}
