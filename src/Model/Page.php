<?php namespace Src\Model;

use Core\Service\Locale;
use Core\Service\Translation;

class Page extends AbstractModel
{
    public function url()
    {
        return Locale::getLocalePath().'/page/'.$this->id.(($chpu = Translation::translit($this->title)) ? '-'.$chpu.'.html' : '');
    }
}