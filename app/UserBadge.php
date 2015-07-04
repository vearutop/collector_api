<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserBadge
 * @package App
 * @property $user_id
 * @property $tag_id
 * @property $badge
 */
class UserBadge extends Model
{
    //
    protected $table = 'users_badges';
    protected $fillable = array('user_id', 'tag_id', 'badge');

}
