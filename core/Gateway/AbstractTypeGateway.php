<?php namespace Core\Gateway;

use Core\Service\Registry;
use Core\Model\Type;

abstract class AbstractTypeGateway extends AbstractGateway
{
    /** @var  Type */
    protected $type;

    public static function of($type)
    {
        return new static($type);
    }

    public function __construct($typeName)
    {
        $this->setType($typeName);

        parent::__construct();
    }

    protected function setType($typeName)
    {
        if (!Registry::get('types')->has($typeName)) {
            throw new \Exception('There is no "'.$typeName.'" type');
        }

        $this->type = Registry::get('types')->get($typeName);

        return $this;
    }

    public function getType($attributeName = null)
    {
        if (!$this->type) {
            throw new \Exception('There is no type set');
        }

        return $attributeName ? $this->type->offsetGet($attributeName) : $this->type;
    }
}