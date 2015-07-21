<?php

namespace HackerBadge;

use Yaoi\Database\Entity;

class Tag extends Entity
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tags';

    protected $fillable = array('name');

    //
}
