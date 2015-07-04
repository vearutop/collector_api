<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserTag
 * @package App
 * @property $user_id
 * @property $tag_id
 * @property $points
 */
class UserTag extends Model
{
    protected $table = 'users_tags';
    protected $fillable = array('user_id', 'tag_id', 'points');

    //
}
