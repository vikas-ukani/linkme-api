<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ServiceReview extends Model
{
    protected $table = 'service_reviews';
    protected $fillable = [
        'serviceId', 'stars', 'comments', 'customer_id'
    ];
}
