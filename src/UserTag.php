<?php

namespace HackerBadge;

use Yaoi\Database\Definition\Column;
use Yaoi\Database\Definition\Index;
use Yaoi\Database\Definition\Table;
use Yaoi\Database\Entity;
use Yaoi\Date\TimeMachine;

/**
 * Class UserTag
 * @package App
 */
class UserTag extends Entity
{
    public $id;
    public $userId;
    public $tagId;
    public $points;
    public $createdAt;
    public $updatedAt;

    public function __construct() {
        $this->createdAt = TimeMachine::getInstance()->now();
    }

    static function setUpColumns($columns)
    {
        $columns->id = Column::AUTO_ID;
        $columns->userId = User::columns()->id;
        $columns->tagId = Tag::columns()->id;
        $columns->points = Column::INTEGER + Column::NOT_NULL;

    }

    static function setUpTable(\Yaoi\Database\Definition\Table $table, $columns)
    {
        $table->setSchemaName('users_tags');
        $table->addIndex(Index::TYPE_UNIQUE, $columns->userId, $columns->tagId);
    }
}
