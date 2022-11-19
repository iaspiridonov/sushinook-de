<?php namespace Core\Collection\Subject;

use Core\Collection\BaseCollection;
use Core\Model\Subject;
use Core\Gateway;
use Core\Model\Type\Attribute;
use Zend\Db\Sql\Select;

class RelationsCollection extends BaseCollection
{
    protected $loaded = [];
    /** @var  Subject */
    protected $subject;

    public function setSubject(Subject $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    public function getSubject($attributeName = null)
    {
        return $attributeName ? $this->subject[$attributeName] : $this->subject;
    }

    public function setLoaded($relationName)
    {
        $this->loaded[] = $relationName;

        return $this;
    }

    public function isLoaded($relationName)
    {
        return in_array($relationName, $this->loaded);
    }

    public function hasRelation($relationName)
    {
        return $this->getSubject()->getType()->getRelations()->has($relationName);
    }

    public function getRelationService($relationName)
    {
        if (!$this->hasRelation($relationName)) {
            throw new \InvalidArgumentException('Type "' . $this->getSubject()->getType('name') . '" has no Relation "' . $relationName . '"');
        }

        $service = null;

        /** @var Attribute\Relation\DirectRelation $relation */
        $relation = $this->getSubject()->getType()->getRelations()->get($relationName);

        if ($relation->isDirectRelation() && $relation->isFactory()) {
            $targetType = $this->getSubject()->offsetGet($relationName);
        } else {
            $targetType = $relation->settings['is'];
        }

        if ($targetType) {
            $service = Gateway\RelationSubjects::of($targetType)->setSubject($this->getSubject())->setRelation($relation)->setup($this->getSubject('id'));

            if ($relation->isSingle()) {
                $service->select(function (Select $select) {
                    $select->limit(1);
                });
            }
        }

        return $service;
    }

    public function getRelation($relationName)
    {
        if ($this->has($relationName)) {

            return $this->get($relationName);

        } elseif (!$this->isLoaded($relationName)) {
            /** @var Attribute\Relation $relation */
            $relation = $this->getSubject()->getType()->getRelations()->get($relationName);

            if ($service = $this->getRelationService($relationName)) {
                $result = $relation->isSingle() ? $service->first() : $service->get();

                $this->put($relationName, $result);

                return $result;
            }
        }

        return null;
    }
}