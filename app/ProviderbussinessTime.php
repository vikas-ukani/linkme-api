<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProviderbussinessTime extends Model
{
    protected $table = 'providerbussiness_times';

    protected $fillable = ['providerid', 'availbillity', 'vacations'];
}
