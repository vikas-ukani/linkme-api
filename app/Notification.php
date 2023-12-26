<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';
    protected $fillable = [
        'userId', 'type', 'title', 'duration', 'message', 'data', 'created_at'
    ];
}
