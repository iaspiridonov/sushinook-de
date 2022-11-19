<?php namespace Core\Collection\Type;

use Core\Collection\BaseCollection;
use Core\Model\Type;

class AttributesCollection extends BaseCollection
{
    /** @var Type */
    protected $type;

    public function setType(Type $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType($attributeName = null)
    {
        return $attributeName ? $this->type[$attributeName] : $this->type;
    }
}