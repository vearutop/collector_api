<?php

namespace HackerBadge;

use Yaoi\Database\Entity;

/**
 * Class UserBadge
 * @package App
 * @property $user_id
 * @property $tag_id
 * @property $badge
 */
class UserBadge extends Entity
{
    //
    protected $table = 'users_badges';
    protected $fillable = array('user_id', 'tag_id', 'badge');

}
