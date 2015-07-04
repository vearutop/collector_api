<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTag extends Model
{
    protected $table = 'users_tags';
    protected $fillable = array('user_id', 'tag_id', 'points');

    //
}
