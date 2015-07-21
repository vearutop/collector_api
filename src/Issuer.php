<?php

namespace HackerBadge;

use Yaoi\Database\Entity;

class Issuer extends Entity
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'issuers';

    protected $fillable = array('name', 'api_key', 'owner_user_id');

}
