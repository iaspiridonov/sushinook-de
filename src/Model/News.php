<?php namespace Src\Model;

use Core\Service\Locale;
use Core\Service\Translation;

class News extends AbstractModel
{
    public function url()
    {
        return '/'.Locale::getLocale().'/news/'.$this->id.(($chpu = Translation::translit($this->title)) ? '-'.$chpu.'.html' : '');
    }

    public function dateHtml()
    {
        $timestamp = strtotime($this->date);

        return ''. date('d', $timestamp) .' '. Translation::of('months')[date('m', $timestamp)] .',  '. date('Y', $timestamp) .' г.';
    }
}