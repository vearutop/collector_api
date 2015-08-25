<?php

namespace HackerBadge;

use Yaoi\Database\Definition\Column;
use Yaoi\Database\Definition\Index;
use Yaoi\Database\Definition\Table;
use Yaoi\Database\Entity;

class User extends Entity
{
    public $id;
    public $login;
    public $type;
    public $avatarUrl;

    static function setUpColumns($columns)
    {
        $columns->id = Column::AUTO_ID;
        $columns->login = Column::STRING + Column::NOT_NULL;
        $columns->type = Column::STRING + Column::NOT_NULL;
        $columns->avatarUrl = Column::STRING;
    }

    static function setUpTable(\Yaoi\Database\Definition\Table $table, $columns)
    {
        $table->addIndex(Index::TYPE_UNIQUE, $columns->login, $columns->type);
        $table->setSchemaName('users');
    }
}
