<?php

namespace HackerBadge;

use Yaoi\Database\Definition\Column;
use Yaoi\Database\Definition\Table;
use Yaoi\Database\Entity;

/**
 * Class UserBadge
 * @package App
 */
class UserBadge extends Entity
{
    public $id;
    public $userId;
    public $tagId;
    public $badge;

    static function setUpColumns($columns)
    {
        $columns->id = Column::AUTO_ID;
        $columns->userId = User::columns()->id;
        $columns->tagId = Tag::columns()->id;
        $columns->badge = Column::STRING + Column::NOT_NULL;
    }

    static function setUpTable(\Yaoi\Database\Definition\Table $table, $columns)
    {
        $table->setSchemaName('users_badges');
    }
}
