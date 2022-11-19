<?php namespace Core\Gateway;

use Core\Model\Subject;
use Core\Model\Type\Attribute;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Join;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Update;

class RelationSubjects extends Subjects
{
    /**
     * @var Select
     */
    protected $subSelectIds;

    /**
     * @var Subject
     */
    protected $subject;

    /**
     * @var Attribute\Relation\DirectRelation
     */
    protected $relation;

    /**
     * @return RelationSubjects
     */
    public function setSubject(Subject $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    public function getSubject($attributeName = null)
    {
        return $attributeName ? $this->subject->offsetGet($attributeName) : $this->subject;
    }
    /**
     * @return RelationSubjects
     */
    public function setRelation(Attribute\Relation $relation)
    {
        $this->relation = $relation;

        return $this;
    }
    /**
     * @var  Attribute\Relation\DirectRelation
     */
    public function getRelation($attributeName = null)
    {
        return $attributeName ? $this->relation->offsetGet($attributeName) : $this->relation;
    }

    /**
     * @return RelationSubjects
     */
    public function setup($subjectsID)
    {
        switch($this->getRelation('kind')){

            case Attribute\Relation\BackRelation::KIND:

                $this->select(function (Select $select) use ($subjectsID) {
                    $select->join(
                        $this->getType()->relationsTable(),
                        $this->getType()->subjectsTable('id') . ' = ' . $this->getType()->relationsTable('subject_id'),
                        ['target_id' => 'relation_subject_id'],
                        Join::JOIN_LEFT
                    )->where([
                        $this->getType()->relationsTable('relation') => $this->getRelation('settings')['relation'],
                        $this->getType()->relationsTable('relation_subject_id') => $subjectsID
                    ]);
                });

                $this->subSelectIds = (new Select($this->getType()->relationsTable()))->columns(['subject_id'])->where(['relation' => $this->getRelation('settings')['relation'], $this->getType()->relationsTable('relation_subject_id') => $subjectsID]);
                break;

            case Attribute\Relation\DirectRelation::KIND:
            default:

                $this->select(function (Select $select) use ($subjectsID) {
                    $select->join(
                        $this->getRelation()->getType()->relationsTable(),
                        $this->getType()->subjectsTable('id').' = '.$this->getRelation()->getType()->relationsTable('relation_subject_id'),
                        ['target_id' => 'subject_id'],
                        Join::JOIN_LEFT
                    )->where([
                        $this->getRelation()->getType()->relationsTable('relation') => $this->getRelation('name'),
                        $this->getRelation()->getType()->relationsTable('subject_id') => $subjectsID
                    ]);
                });

                $this->subSelectIds = (new Select($this->getRelation()->getType()->relationsTable()))->columns(['relation_subject_id'])->where(['relation' => $this->getRelation('name'), $this->getRelation()->getType()->relationsTable('subject_id') => $subjectsID]);
        }

        //dump($this->getSql()->buildSqlString($this->getSelect()));

        return $this;
    }

    public function save(Subject $subject)
    {
        if (is_null($this->getRelation())) {
            throw new \Exception('Need Subject relation for this operation $subject->relation()->save($subject)');
        }

        if ($this->getRelation()->isDirectRelation()) {

            if($this->getRelation()->isFactory()) {
                $this->getSubject()->offsetSet($this->getRelation('name'), $subject->getType('name'))->save();
            }

            $subject->offsetSet('is_related', 1)->save();

            Relations::of($this->getRelation()->getType('name'))->insert([
                'relation'              => $this->getRelation('name'),
                'subject_id'            => $this->getSubject('id'),
                'relation_subject_id'   => $subject['id']
            ]);
        }

        return $this;
    }

    public function saveMany($subjects)
    {
        foreach ($subjects as $subject) {
            $this->save($subject);
        }

        return $this;
    }

    public function create($data)
    {
        /** @var Subject $subject */
        $subject = parent::create($data);

        $this->save($subject);

        return $subject;
    }

    protected function executeInsert(Insert $insert)
    {
        $data = array_combine($insert->getRawState('columns'), $insert->getRawState('values'));

        return $this->create($data);
    }

    protected function executeUpdate(Update $update)
    {
        $update->where(['id IN ?' => $this->subSelectIds]);

        return parent::executeUpdate($update);
    }

    protected function executeDelete(Delete $delete)
    {
        $delete->where(['id IN ?' => $this->subSelectIds]);

        return parent::executeDelete($delete);
    }
}