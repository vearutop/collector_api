<?php

namespace HackerBadge;

use Yaoi\Database\Definition\Column;
use Yaoi\Database\Definition\Index;
use Yaoi\Database\Definition\Table;
use Yaoi\Database\Entity;

class Identity extends Entity
{
    public $id;
    public $userId;
    public $login;
    public $identityTypeId;
    public $createdAt;

    static function setUpColumns($columns)
    {
        $columns->id = Column::AUTO_ID;
        $columns->userId = User::columns()->id;
        $columns->login = Column::STRING + Column::NOT_NULL;
        $columns->identityTypeId = IdentityType::columns()->id;
        $columns->createdAt = Column::INTEGER + Column::NOT_NULL;
    }

    static function setUpTable(\Yaoi\Database\Definition\Table $table, $columns)
    {
        $table->addIndex(Index::TYPE_UNIQUE, $columns->login, $columns->identityTypeId);
    }

}