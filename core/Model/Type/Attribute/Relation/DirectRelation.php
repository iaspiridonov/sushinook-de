<?php namespace Core\Model\Type\Attribute\Relation;

use Core\Model\Type\Attribute\Relation;
use Core\Gateway\Relations;
use Core\Gateway\TypesAttributes;
use Core\Service\Table;

class DirectRelation extends Relation
{
    const KIND = 'DIRECT_RELATION';

    public function isDirectRelation()
    {
        return true;
    }

    public function isFactory()
    {
        return is_array($this->settings['is']);
    }

    public function isStrong()
    {
        return !!$this->settings['strong'];
    }

    public function isInternal()
    {
        return !!$this->settings['interface']['internal'];
    }

    public function is($typeName)
    {
        return (($this->isFactory() && in_array($typeName, $this['settings']['is'])) || ( !$this->isFactory() && parent::is($typeName)));
    }

    public function delete($withItself = true)
    {
        if ($this->isFactory()) {
            Table::alter($this->getType()->subjectsTable())->dropColumn($this['name'])->execute();
        }

        if (!$withItself && (new TypesAttributes())->select(['type' => $this->getType('name'), 'kind' => DirectRelation::KIND])->count()) {
            Relations::of($this->getType('name'))->delete(['relation' => $this['name']]);
        }

        if (!$withItself) return true;

        if ($return = parent::delete()) {

            if (!(new TypesAttributes())->select(['type' => $this->getType('name'), 'kind' => DirectRelation::KIND])->count()) {
                Table::drop($this->getType()->relationsTable());
            }
        }

        return $return;
    }

    public function save()
    {
        if (!$this->rowExistsInDatabase() && $this->isFactory()) {
            Table::alter($this->getType()->subjectsTable())->addColumn($this->getColumn())->execute();
        }

        return parent::save();
    }
}