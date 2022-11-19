<?php namespace Core\Model\Type\Attribute\Property;

use Core\Service\Locale;
use Core\Model\Type\Attribute\Property;
use Core\Gateway\TypesAttributes;

class TranslatableProperty extends Property
{
    public function isTranslatable()
    {
        return true;
    }

    public function getColumnName($locale = null)
    {
        return TypesAttributes::getTranslatableColumnName($this->name, $locale ? $locale : Locale::getLocale());
    }

    public function getColumn($locale = null)
    {
        return TypesAttributes::getColumn($this->kind, $this->getColumnName($locale));
    }
}