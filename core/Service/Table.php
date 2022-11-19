<?php namespace Core\Service;

use Core\Facade\App;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\AbstractSql;
use Zend\Db\Sql\Ddl\AlterTable;
use Zend\Db\Sql\Ddl\CreateTable;
use Zend\Db\Sql\Ddl\DropTable;
use Zend\Db\Sql\Sql;

class Table
{
    /** @var AbstractSql  */
    protected $ddl;

    public function __construct(AbstractSql $ddl)
    {
        $this->ddl = $ddl;
    }

    /**
     * @return CreateTable
     */
    public static function create($tableName)
    {
        return (new static(new CreateTable($tableName)));
    }

    /**
     * @return AlterTable
     */
    public static function alter($tableName)
    {
        return (new static(new AlterTable($tableName)));
    }

    public static function drop($tableName)
    {
        return (new static(new DropTable($tableName)))->execute();
    }

    public function __call($name, $arguments)
    {
        call_user_func_array([$this->ddl, $name], $arguments);

        return $this;
    }

    public function execute()
    {
        if (!$this->ddl) {
            throw new \Exception('Ddl command is empty');
        }

        $sql = new Sql(App::getInstance()->getContainer()->get(Adapter::class));

        //dump($sql->buildSqlString($this->ddl));

        $sql->getAdapter()->query(
            $sql->buildSqlString($this->ddl),
            Adapter::QUERY_MODE_EXECUTE
        );

        return $this;
    }
}