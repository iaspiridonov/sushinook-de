<?php namespace Core\Gateway;

use Core\Model\Subject;
use Core\Collection;
use Core\Model\Type;
use Core\Model\Type\Attribute;
use Core\Service\Registry;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Update;

class Subjects extends AbstractTypeGateway
{
    protected $withRelations = [];

    public static function __callStatic($name, $arguments)
    {
        $object = static::of($name);

        if ($arguments) {
            call_user_func_array([$object, 'select'], $arguments);
        }

        return $object;
    }

    public function with(...$relations)
    {
        if ($params = is_array($relations[0]) ? $relations[0] : $relations) {
            $this->withRelations = array_merge($this->withRelations, $params);
        }

        return $this;
    }

    protected function setTable()
    {
        $this->table = $this->getType()->subjectsTable();
    }

    /** @return Subject */
    public function model()
    {
        $individualModelClass = 'Src\Model\\'.$this->getType('name');

        if (class_exists($individualModelClass)) {
            return new $individualModelClass;
        }

        return new Subject($this->getType('name'));
    }

    public function collection($array)
    {
        return new Collection\SubjectsCollection($array);
    }

    public function get()
    {
        $subjects = parent::get();

        if ($subjects->count() && count($this->withRelations)) {
            Relations::of($this->getType('name'))->load($subjects, $this->withRelations);
        }

        return $subjects;
    }

    protected function executeDelete(Delete $delete)
    {
        $select = (new Select($this->getTable()))->columns(['id'])->where($delete->where);

        if (!$this->selectWith($select)->count()) return false;

        $childsQueryLine = [];

        if (($childs = $this->getType()->getDirectRelations()->filter(function (Attribute\Relation\DirectRelation $item) {
            return $item->isStrong();
        }))->count()) {

            $this->buildDeleteRelationsMap($childs->setType($this->getType()), $select, $childsQueryLine);

            if ($childsQueryLine) {

                $childsQueryLine = array_reverse($childsQueryLine);// we should start from the end

                foreach ($childsQueryLine as $result) {

                    $this->getSql()->getAdapter()->query(
                        $this->getSql()->buildSqlString($result),
                        Adapter::QUERY_MODE_EXECUTE
                    );
                }
            }
        }

        $parentsQueryLine = [];

        if (($attributes = (new TypesAttributes())->select(['kind' => Attribute\Relation\DirectRelation::KIND])->get())) {

            $attributes->each(function ($parent) use (&$parentsQueryLine, $select) {
                /** @var Attribute\Relation\DirectRelation $parent */

                if ($parent->is($this->getType('name'))) {
                    $where = ['relation' => $parent->name, 'relation_subject_id IN ?' => $select];

                    if ($parent->isFactory()) {
                        $where['subject_id IN ?'] = (new Select($parent->getType()->subjectsTable()))->columns(['id'])->where([$parent->name => $this->getType('name')]);
                    }

                    if (Relations::of($parent->getType('name'))->select($where)->count()) {
                        $parentsQueryLine []= (new Delete($parent->getType()->relationsTable()))->where(['id' => Relations::of($parent->getType('name'))->select($where)->get()->pluck('id')->toArray()]);

                        if ($parent->isFactory()) {
                            $parentsQueryLine []= (new Update($parent->getType()->subjectsTable()))->set([$parent->name => null])->where([$parent->name => $this->getType('name'), 'id NOT IN ?' => (new Select($parent->getType()->relationsTable()))->columns(['subject_id'])->where(['relation' => $parent->name])]);//, 'relation_subject_id' => $this->id
                        }
                    }
                }
            });
        }

        parent::executeDelete($delete);

        if ($parentsQueryLine) {
            foreach ($parentsQueryLine as $result) {

                $this->getSql()->getAdapter()->query(
                    $this->getSql()->buildSqlString($result),
                    Adapter::QUERY_MODE_EXECUTE
                );
            }
        }

        return $this;
    }

    protected function buildDeleteRelationsMap(Collection\Type\AttributesCollection $childs, $subjects_id, &$results)
    {
        $countLimit = 500;// before this limit we take ids every time at isolated queries, after we put quiries themselves

        foreach($childs as $item){

            /** @var Attribute\Relation\DirectRelation $item */

            $where = ['relation' => $item->name];

            if (is_array($subjects_id)) {
                $where['subject_id'] = $subjects_id;
            } else {
                $where['subject_id IN ?'] = $subjects_id;
            }

            $select = (new Select($childs->getType()->relationsTable()))->where($where);

            if (($count = Relations::of($childs->getType('name'))->selectWith($select)->count())) {

                $selectResult = null;

                if ($count < $countLimit) {
                    $selectResult = Relations::of($childs->getType('name'))->selectWith($select)->get();
                }

                $results []= (new Delete($childs->getType()->relationsTable()))->where($selectResult ? ['id' => $selectResult->pluck('id')->toArray()] : ['id IN ?' => $select->columns(['id'])]);

                if ($item->isFactory()) {

                    foreach ($item->settings['is'] as $typeName) {
                        /** @var Type $type */
                        $type = Registry::get('types')->get($typeName);

                        $factorySelect = (new Select($item->getType()->relationsTable()))->where(['relation' => $item->name, 'subject_id IN ?' => (new Select($item->getType()->subjectsTable()))->columns(['id'])->where($selectResult ? ['id' => $selectResult->pluck('subject_id')->toArray(), $item->name => $type->name] : ['id IN ?' => $select->columns(['relation_subject_id']), $item->name => $type->name])]);

                        $factorySelectResult = null;

                        if (($count = Relations::of($item->getType('name'))->selectWith($factorySelect)->count())) {

                            if ($count < $countLimit) {
                                $factorySelectResult = Relations::of($item->getType('name'))->selectWith($factorySelect)->get()->pluck('relation_subject_id')->toArray();
                            }

                            $results []= (new Delete($type->subjectsTable()))->where($factorySelectResult ? ['id' => $factorySelectResult] : ['id IN ?' => $factorySelect->columns(['relation_subject_id'])]);

                            $newChilds = $type->getDirectRelations()->filter(function (Attribute\Relation\DirectRelation $item) {
                                return $item->isStrong();
                            });

                            if ($newChilds->count()) {
                                $this->buildDeleteRelationsMap($newChilds->setType($type), $factorySelect->columns(['relation_subject_id']), $results);//$factorySelectResult ? $factorySelectResult :
                            }
                        }
                    }

                } else {
                    /** @var Type $type */
                    $type = Registry::get('types')->get($item->settings['is']);

                    $results []= (new Delete($type->subjectsTable()))->where($selectResult ? ['id' => $selectResult->pluck('relation_subject_id')->toArray()] : ['id IN ?' => $select->columns(['relation_subject_id'])]);

                    $newChilds = $type->getDirectRelations()->filter(function (Attribute\Relation\DirectRelation $item) {
                        return $item->isStrong();
                    });

                    if ($newChilds->count()) {
                        $this->buildDeleteRelationsMap($newChilds->setType($type), $selectResult ? $selectResult->pluck('relation_subject_id')->toArray() : $select->columns(['relation_subject_id']), $results);
                    }
                }
            }
        }
    }
}