<?php namespace Core\Model\Type\Attribute;

use Core\Model\Type\Attribute;

class Relation extends Attribute
{
    public function isRelation()
    {
        return true;
    }

    public function isDirectRelation()
    {
        return false;
    }

    public function isBackRelation()
    {
        return false;
    }

    public function is($typeName)
    {
        return $this['settings']['is'] == $typeName;
    }

    public function isSingle()
    {
        return !!$this['settings']['single'];
    }
}