<?php namespace Core\Gateway;

use Core\Model;
use Core\Collection;
use Zend\Db\Sql\Ddl\Column\Boolean;
use Zend\Db\Sql\Ddl\Column\Date;
use Zend\Db\Sql\Ddl\Column\Datetime;
use Zend\Db\Sql\Ddl\Column\Integer;
use Zend\Db\Sql\Ddl\Column\Text;
use Core\Vendor\Ddl\LongText;
use Zend\Db\Sql\Ddl\Column\Varchar;

class TypesAttributes extends AbstractGateway
{
    const TABLE = 'types_attributes';

    public static function getColumn($kind, $name)
    {
        switch ($kind) {

            case 'NUMBER':
                return new Integer($name, true);

            case 'HTML':
            case 'LONGTEXT':
                return new LongText($name, true);

            case 'TEXT':
                return new Text($name, null, true);

            case 'DATE':
                return new Date($name, true);

            case 'DATETIME':
                return new Datetime($name, true);

            case 'CHECKBOX':
                return new Boolean($name, false, 0);

            default:
                return new Varchar($name, 255, true);
        }
    }

    public static function getTranslatableColumnName($name, $locale)
    {
        return '_'.$locale.'_'.$name;
    }

    public function model()
    {
        return new Model\Type\Attribute\Factory;
    }

    public function next()
    {
        foreach ($this->executeSelect($this->getSelect()) as $row) {
            yield $row['id'] => $this->model()->exchangeArray((array)$row);
        }
    }

    public function collection($array)
    {
        return new Collection\Type\AttributesCollection($array);
    }

    protected function setTable()
    {
        $this->table = static::TABLE;
    }
}