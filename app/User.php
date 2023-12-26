<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fname',
        'lname',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'state',
        'category',
        'zipcode',
        'avatar',
        'bio',
        'user_type',
        'active',
        'socialproviderid',
        'socialprovidertype',
        'email_verified_at',
        'latitude',
        'longitude',
        'email_verification_token',
        'preference_location',
        "is_in_home_service",
        "service_location_lat",
        "service_location_long"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime', 'category' => 'array',
    ];

    public function posts()
    {
        return $this->hasMany('App\Post', 'user_id', 'id');
    }

    public function linkedSocialAccounts()
    {
        return $this->hasMany(LinkedSocialAccount::class);
    }

    /**
     * Customer Bookings
     *
     * @return void
     */
    public function bookings()
    {
        return $this->hasMany(Bookings::class, 'customerId', 'id');
    }

    /**
     * Providers Services
     *
     * @return void
     */
    public function services()
    {
        return $this->hasMany(Linkmeservices::class, 'provider_id', 'id');
    }
}
