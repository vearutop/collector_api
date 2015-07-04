<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Issuer extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'issuers';

    protected $fillable = array('name');

}
