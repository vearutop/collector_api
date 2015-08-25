<?php

namespace HackerBadge;

use Illuminate\Database\Eloquent\Model;
use Yaoi\Database\Definition\Column;
use Yaoi\Database\Definition\Table;
use Yaoi\Database\Entity;

class UserTagHistory extends Entity
{
    protected $table = 'users_tags_history';
    protected $fillable = array('user_id', 'tag_id', 'points', 'origin_user_id');

    public $id;
    public $userId;
    public $tagId;
    public $points;
    public $originUserId;

    //
    static function setUpColumns($columns)
    {
        $columns->id = Column::AUTO_ID;
        $columns->userId = User::columns()->id;
        $columns->tagId = Tag::columns()->id;
        $columns->points = Column::INTEGER + Column::NOT_NULL;
        $columns->originUserId = User::columns()->id;
    }

    static function setUpTable(\Yaoi\Database\Definition\Table $table, $columns)
    {
        $table->setSchemaName('users_tags_history');
        // TODO: Implement setUpTable() method.
    }

}
