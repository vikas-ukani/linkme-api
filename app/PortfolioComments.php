<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PortfolioComments extends Model
{
    protected $table = 'portfolio_comments';


    protected $fillable = ['photo_id', 'user_by_id', 'comments'];
}
