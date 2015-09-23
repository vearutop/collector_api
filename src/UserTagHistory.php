<?php

namespace HackerBadge;

use Yaoi\Database\Definition\Column;
use Yaoi\Database\Entity;

class UserTagHistory extends Entity
{
    public $id;
    public $userId;
    public $tagId;
    public $points;
    public $originUserId;
    public $createdAt;

    //
    static function setUpColumns($columns)
    {
        $columns->id = Column::AUTO_ID;
        $columns->userId = User::columns()->id;
        $columns->tagId = Tag::columns()->id;
        $columns->points = Column::INTEGER + Column::NOT_NULL;
        $columns->originUserId = User::columns()->id;
        $columns->createdAt = Column::INTEGER;
    }

    static function setUpTable(\Yaoi\Database\Definition\Table $table, $columns)
    {
        $table->setSchemaName('users_tags_history');
        // TODO: Implement setUpTable() method.
    }

}
