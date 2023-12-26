<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Portfolioreactions extends Model
{
    protected $table = 'portfolio_reactions';
    protected $fillable = ['photo_id', 'user_by_id', 'reaction_type'];
}
