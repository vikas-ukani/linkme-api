<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'chat';
    protected $fillable = [
        'createdBy', 'chatWith', 'friendlyName', 'uniqueName', 'channelId'
    ];
}
