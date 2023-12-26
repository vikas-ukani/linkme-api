<?php

namespace App;

use App\User;
use Illuminate\Database\Eloquent\Model;

class LinkedSocialAccount extends Model
{
    protected $fillable = [
        'provider_name',
        'provider_id',
    ];

    /**
     * User Social Account
     *
     * @return void
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
