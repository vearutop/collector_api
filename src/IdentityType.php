<?php
/**
 * Created by PhpStorm.
 * User: vearutop
 * Date: 31.08.2015
 * Time: 22:39
 */

namespace HackerBadge;


use Yaoi\Database\Definition\Column;
use Yaoi\Database\Definition\Table;
use Yaoi\Database\Entity;

class IdentityType extends Entity
{
    public $id;
    public $name;
    public $title;

    static function setUpColumns($columns)
    {
        $columns->id = Column::AUTO_ID;
        $columns->name = Column::create(Column::STRING + Column::NOT_NULL)->setUnique();
        $columns->title = Column::STRING;
    }

    static function setUpTable(\Yaoi\Database\Definition\Table $table, $columns)
    {
    }


}