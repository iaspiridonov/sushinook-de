<?php namespace Core\Model\Subject;

use Core\Gateway\Relations;
use Core\Gateway\Subjects;
use Core\Model\Type\Attribute\Relation\DirectRelation;

class Relation extends AbstractModel
{
    protected function setTable()
    {
        $this->table = $this->getType()->relationsTable();
        return $this;
    }

    public function delete()
    {
        if ($relation = $this->getType()->getRelations()->get($this->relation)) {/** @var \Core\Model\Type\Attribute\Relation $relation */
            if ($relation->isDirectRelation()) { /** @var DirectRelation $relation */
                if ($relation->isFactory()) {
                    if (Relations::of($this->getType('name'))->select(['relation' => $relation->name, 'subject_id' => $this->subject_id])->count()<2) {
                        Subjects::of($this->getType('name'))->update([$relation->name => null], ['id' => $this->subject_id]);
                    }
                }
            }
        }

        return parent::delete();
    }
}