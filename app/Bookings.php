<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bookings extends Model
{
    protected $table = 'bookings';

    protected $fillable = [
        'providerId',
        'customerId',
        'serviceId',
        'duration',
        'price',
        'booked_at',
        'start_at',
        'end_at',
        'status',
        'bookingStartUtc',
        'bookingEndUtc'
    ];


    /**
     * Customer relation
     *
     * @return void
     */
    public function customer()
    {
        return $this->hasOne(User::class, 'id', 'customerId');
    }


    /**
     * Provider relations.
     *
     * @return void
     */
    public function provider()
    {
        return $this->hasOne(User::class, 'id', 'providerId');
    }


    /**
     * Link Me Service Relations
     *
     * @return void
     */
    public function service()
    {
        return $this->hasOne(Linkmeservices::class, 'id', 'serviceId');
    }
}
