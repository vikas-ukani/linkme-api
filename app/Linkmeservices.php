<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Linkmeservices extends Model
{
    protected $fillable = [
        'provider_id', 'title', 'category', 'duration', 'price', 'before_24_cancellation', 'after_24_cancellation', 'description', 'service_img',
    ];
}
