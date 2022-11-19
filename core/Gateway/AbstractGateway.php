<?php namespace Core\Gateway;

use Core\Facade\App;
use Core\Service\Pagination;
use Core\Collection\BaseCollection;
use Zend\Db\Adapter\Adapter;
use Zend\Db\RowGateway\RowGateway;
use Zend\Db\Sql\Exception;
use Zend\Db\Sql\Predicate;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\AbstractTableGateway;

abstract class AbstractGateway extends AbstractTableGateway
{
    /** @var  Select */
    protected $select;

    protected $paginator;

    public function __construct()
    {
        $this->adapter = App::getInstance()->getContainer()->get(Adapter::class);

        $this->setTable();

        $this->initialize();
    }

    abstract protected function setTable();

    /**
     * @return RowGateway
     */
    public function model()
    {
        return new RowGateway('id', $this->table, $this->getAdapter());
    }

    /**
     * @return BaseCollection
     */
    public function collection($array)
    {
        return new BaseCollection($array);
    }

    protected function setSelect(Select $select)
    {
        $this->select = $select;

        return $this;
    }

    public function &getSelect()
    {
        if (!$this->select) {
            $this->select = $this->sql->select();
        }

        return $this->select;
    }

    public function select($where = null)
    {
        $select = $this->getSelect();

        if ($where instanceof \Closure) {
            $where($select);
        } elseif ($where !== null) {
            $select->where($where);
        }

        return $this->selectWith($select);
    }

    public function selectWith(Select $select)
    {
        return $this->setSelect($select);
    }

    public function execute()
    {
        return parent::executeSelect($this->getSelect());
    }

    public function next()
    {
        foreach ($this->execute() as $row) {
            yield $this->model()->exchangeArray((array)$row);
        }
    }

    public function toArray()
    {
        $rows = [];

        foreach ($this->next() as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function toCollection()
    {
        return $this->collection($this->toArray());
    }

    public function get()
    {
        return $this->toCollection()->keyBy('id');
    }

    public function first()
    {
        $this->select(function (Select $select) {
            $select->limit(1);
        });

        return $this->get()->first();
    }

    public function find($id)
    {
        $this->select(['id' => $id]);

        return $this->first();
    }

    public function count()
    {
        $select = clone $this->getSelect();

        $select->columns(['count' => new Expression('COUNT(1)')]);

        $statement = $this->sql->prepareStatementForSqlObject($select);
      //  var_dump($statement->getSql());
        return $statement->execute()->current()['count'];
    }

    public function paginate($perPage = 15, $pageName = 'page')
    {
        $paginator = new Pagination($this->count(), $perPage, $pageName);

        $this->select(function (Select $select) use ($paginator) {

            $page = $paginator->get('page');

            $perPage = $paginator->get('perPage');

            $select->offset(($page - 1) * $perPage)->limit($perPage);
        });

        return $this->get()->setPagination($paginator);
    }

    public function create($data)
    {
        $subject = $this->model()->populate($data);

        $subject->save();

        return $subject;
    }

    public function createMany($dataList)
    {
        $out = [];

        foreach ($dataList as $data) {
            $out[] = $this->create($data);
        }

        return $out;
    }
}