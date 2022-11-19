<?php namespace Core\Model\Subject;

use Core\Facade\App;
use Core\Service\Registry;
use Core\Model\Type;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

abstract class AbstractModel extends \Core\Model\AbstractModel
{
    /** @var  Type */
    protected $type;

    public function __construct($typeName)
    {
        $this->setType($typeName);
    }

    abstract protected function setTable();

    public function setType($typeName)
    {
        if (!Registry::get('types')->has($typeName)) {
            throw new \Exception('There is no "'.$typeName.'" type');
        }

        $this->type = Registry::get('types')->get($typeName);
        $this->setTable();
        $this->sql = new Sql(App::getInstance()->getContainer()->get(Adapter::class), $this->table);

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