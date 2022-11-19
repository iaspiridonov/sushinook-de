<?php namespace Core\Model\Type\Attribute;

use Core\Gateway\TypesAttributes;
use Core\Model\Type\Attribute;
use Core\Service\Locale;
use Core\Service\Table;

class Property extends Attribute
{
    const KINDS = [
        'STRING',
        'NUMBER',
        'DATE',
        'DATETIME',
        'TEXT',
        'LONGTEXT',
        'HTML',
        'PASSWORD',
        'FILE',
        'PHOTO',
        'CHECKBOX',
        'RADIO',
        'SELECT',
        'MULTISELECT',
        'MULTICHECKBOX'
    ];

    const MULTIVALUE_PROPERTIES = ['RADIO', 'SELECT', 'MULTISELECT', 'MULTICHECKBOX'];
    const MULTICHECK_PROPERTIES = ['MULTISELECT', 'MULTICHECKBOX'];
    const FILE_PROPERTIES = ['PHOTO', 'FILE'];

    public function isMultivalue()
    {
        return in_array($this['kind'], static::MULTIVALUE_PROPERTIES);
    }

    public function isMulticheck()
    {
        return in_array($this['kind'], static::MULTICHECK_PROPERTIES);
    }

    public function isFile()
    {
        return in_array($this['kind'], static::FILE_PROPERTIES);
    }

    public function isPhoto()
    {
        return ($this['kind'] == 'PHOTO');
    }

    public function isProperty()
    {
        return true;
    }

    public function isTranslatable()
    {
        return false;
    }

    public function isVisible()
    {
        return $this['settings']['interface']['visible'];
    }

    public function isRequired()
    {
        return $this['settings']['interface']['required'];
    }

    public function delete($withItself = true)
    {
        if ($this->isTranslatable()) {/** @var Attribute\Property\TranslatableProperty $this */
            $table = Table::alter($this->getType()->subjectsTable());

            foreach (Locale::getLocales() as $locale) {
                $table->dropColumn($this->getColumnName($locale));
            }

            $table->execute();
        } else {
            Table::alter($this->getType()->subjectsTable())->dropColumn($this->getColumnName())->execute();
        }

        if (!$withItself) return true;

        return parent::delete();
    }

    public function save()
    {
        if (!$this->rowExistsInDatabase()) {
            // INSERT
            if ($this->isTranslatable()) {/** @var Attribute\Property\TranslatableProperty $this */
                $table = Table::alter($this->getType()->subjectsTable());

                foreach (Locale::getLocales() as $locale) {
                    $table->addColumn($this->getColumn($locale));
                }

                $table->execute();
            } else {
                Table::alter($this->getType()->subjectsTable())->addColumn($this->getColumn())->execute();
            }
        }

        return parent::save();
    }
}