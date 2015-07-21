<?php

namespace HackerBadge;

use Yaoi\Database\Entity;

class User extends Entity
{
    protected $table = 'users';
    protected $fillable = array('login', 'type');
}
