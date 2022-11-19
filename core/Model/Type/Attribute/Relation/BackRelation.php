<?php namespace Core\Model\Type\Attribute\Relation;

use Core\Model\Type\Attribute\Relation;

class BackRelation extends Relation
{
    const KIND = 'BACK_RELATION';

    public function isBackRelation()
    {
        return true;
    }
}