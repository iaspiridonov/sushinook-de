<?php namespace Core\Gateway;

use Core\Collection;
use Core\Model\Subject;
use Core\Model\Type\Attribute;
use Core\Service\Table;
use Zend\Db\Sql\Ddl\Column\BigInteger;
use Zend\Db\Sql\Ddl\Column\Varchar;
use Zend\Db\Sql\Ddl\Constraint\PrimaryKey;
use Zend\Db\Sql\Ddl\Constraint\UniqueKey;
use Zend\Db\Sql\Ddl\Index\Index;
use Zend\Db\Sql\Expression;;

class Relations extends AbstractTypeGateway
{
    public function createTable()
    {
        Table::create($this->getType()->relationsTable())
            //fields:
            ->addColumn(new BigInteger('id', false, NULL, ['autoincrement' => true]))
            ->addColumn(new Varchar('relation', 255))
            ->addColumn(new BigInteger('subject_id'))
            ->addColumn(new BigInteger('relation_subject_id'))
            //indexes:
            ->addConstraint(new PrimaryKey('id'))
            ->addConstraint(new UniqueKey(['relation', 'subject_id', 'relation_subject_id'], 'unique'))
            ->addConstraint(new Index(['relation', 'subject_id']))
            ->addConstraint(new Index(['relation', 'relation_subject_id']))
            ->execute();
    }

    protected function setTable()
    {
        $this->table = $this->getType()->relationsTable();
    }

    public function model()
    {
        return (new Subject\Relation($this->getType('name')));
    }

    public function load(Collection\SubjectsCollection &$subjects, $relations)//пиздатая уличная магия. НЕТ БЛЯДЬ ТЫ НЕ САМЫЙ УМНЫЙ ПРОГРОМИСТ, ЭТОТ КОД НЕВОЗМОЖНО СОКРАТИТЬ, ИДИ НАХУЙ
    {
        $relationsMap = [];

        foreach ($relations as $relationPath) {

            $relationsToLoad = explode('.', $relationPath);

            $first = array_shift($relationsToLoad);

            if(!$this->getType()->getRelations()->has($first)) {

                throw new \Exception('No Relation "'.$first.'" at Type "'.$this->getType('name').'"');
            }

            if(count($relationsToLoad)>0) {

                if(!array_key_exists($first, $relationsMap) || !is_array($relationsMap[$first])) {
                    $relationsMap[$first] = [];
                }
                $relationsMap[$first][] = join('.', $relationsToLoad);

            }else if(!array_key_exists($first, $relationsMap)){

                $relationsMap[$first] = false;
            }
        }

        if(!count($relationsMap)) return;

        $loadMap = [];

        $subjectsID = $subjects->pluck('id')->toArray();

        foreach ($relationsMap as $relationToLoad => $relationsToLoadNext) {

            if($relationsToLoadNext === false) {
                unset($relationsMap[$relationToLoad]);
            }//garbage collector

            /** @var Attribute\Relation\DirectRelation $relation */
            $relation = $this->getType()->getRelations()->get($relationToLoad);

            if($relation->isDirectRelation() && $relation->isFactory()) {

                $subjects->each(function($item) use (&$loadMap, $relationToLoad){/** @var Subject $item */

                    if ($targetType = $item->offsetGet($relationToLoad)) {
                        if (!key_exists($relationToLoad, $loadMap) || !key_exists($targetType, $loadMap[$relationToLoad])) {
                            $loadMap[$relationToLoad][$targetType] = [];
                        }

                        $loadMap[$relationToLoad][$targetType][] = $item['id'];
                    }
                });
            }else{
                $loadMap[$relationToLoad][$relation['settings']['is']] = $subjectsID;
            }
        }

        if(!($results = $this->loadMap($loadMap))) return;

        $relationsToSetup = [];

        foreach ($results as $relationName => $hash) {

            foreach ($hash as $targetType => &$subjectsCollection) {

                if (array_key_exists($relationName, $relationsMap)) {//todo needToCheck subRelations on production
                    (new static($targetType))->load($subjectsCollection, $relationsMap[$relationName]);
                }

                if (!array_key_exists($relationName, $relationsToSetup)) {
                    $relationsToSetup[$relationName] = &$subjectsCollection;
                    continue;
                }

                $relationsToSetup[$relationName] = $relationsToSetup[$relationName]->merge($subjectsCollection);
            }
        }

        $subjects->each(function($item) use ($relationsToSetup) {
            /** @var Subject $item */
            $key = $item['id'];

            foreach ($relationsToSetup as $relationName => $subjectsCollection) {

                /** @var Attribute\Relation $relation */
                $relation = $this->getType()->getAttributes()->get($relationName);

                /** @var Collection\SubjectsCollection $elements */
                if(($elements = $subjectsCollection->filter(function($itemResult) use ($key){
                    return $itemResult->target_id == $key;
                })) && $elements->count()>0){
                    if($relation->isSingle()) {
                        $elements = $elements->first();
                    } else {
                        $elements = $elements->keyBy('id');
                    }

                    $item->getRelations()->put($relationName, $elements);
                }

                $item->getRelations()->setLoaded($relationName);
            }
        });
    }

    protected function loadMap($map)
    {
        $results = [];

        foreach ($map as $relationName => $hash) {
            foreach ($hash as $targetTypeName => $subjectsID) {
                if (!$subjectsID) continue;
                $results[$relationName][$targetTypeName] = RelationSubjects::of($targetTypeName)->setRelation($this->getType()->getRelations()->get($relationName))->setup($subjectsID)->toCollection();
            }
        }

        return $results;
    }

    public function getCounts(array $subjects_id)
    {
        $select = $this->getSql()->select();
        $select->columns(['subject_id', 'relation', 'count' => new Expression('COUNT(*)')])->where(['subject_id' => $subjects_id])->group(['subject_id', 'relation']);

        $results = [];

        foreach ($this->executeSelect($select) as $result) {
            $results[$result['subject_id']][$result['relation']] = $result['count'];
        }

        return $results;
    }
}