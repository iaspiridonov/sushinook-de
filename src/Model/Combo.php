<?php namespace Src\Model;

use Core\Service\Locale;
use Core\Service\Translation;

class Combo extends AbstractModel
{

    public function url()
    {
        return '/'.Locale::getLocale().'/combo/'.$this->id.(($chpu = Translation::translit($this->name)) ? '-'.$chpu : '');
    }
}