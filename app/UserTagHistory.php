<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTagHistory extends Model
{
    protected $table = 'users_tags_history';
    protected $fillable = array('user_id', 'tag_id', 'points', 'origin_user_id');

    //
}
