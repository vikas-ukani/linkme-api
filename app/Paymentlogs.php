<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Paymentlogs extends Model
{
    protected $table = 'paymentlogs';
    protected $fillable = ['bookingId', 'CustomerId', 'bookingamount', 'chargeId', 'paymentstatus'];
}
