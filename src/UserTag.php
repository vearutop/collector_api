<?php

namespace HackerBadge;

use Yaoi\Database\Entity;

/**
 * Class UserTag
 * @package App
 * @property $user_id
 * @property $tag_id
 * @property $points
 */
class UserTag extends Entity
{
    protected $table = 'users_tags';
    protected $fillable = array('user_id', 'tag_id', 'points');

    //
}
