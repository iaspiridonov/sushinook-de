<?php namespace Core\Model\Type\Attribute;

use Core\Model\Type\Attribute;

class Factory
{
    public function populate(array $rowData, $rowExistsInDatabase = false)
    {
        $kind = $rowData['kind'];
        if (in_array($kind, Property::KINDS)) {

            return ((is_array($rowData['settings']) ? $rowData['settings'] : json_decode($rowData['settings'], true))['translatable'] ? new Property\TranslatableProperty : new Property)->populate($rowData, $rowExistsInDatabase);

        } elseif ($kind == Relation\DirectRelation::KIND) {

            return (new Relation\DirectRelation)->populate($rowData, $rowExistsInDatabase);

        } else if ($kind == Relation\BackRelation::KIND) {

            return (new Relation\BackRelation)->populate($rowData, $rowExistsInDatabase);
        }

        return (new Attribute)->populate($rowData, $rowExistsInDatabase);
    }

    public function exchangeArray($array)
    {
        return $this->populate($array, true);
    }
}