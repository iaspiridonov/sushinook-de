<?php namespace Src\Model;

use Core\Model\Subject;
use Core\Service\Locale;
use Core\Service\Translation;

class MenuElement extends AbstractModel
{
    public function url()
    {
        if ($body = $this->getRelations('body')) {/** @var Subject $body */
            switch ($body->getType('name')) {
                case 'Link':
                case 'LinkTranslatable':
                    return $body->url;
                case 'Page':
                    return Locale::getLocalePath().'/page/'.$body->id.(($chpu = Translation::translit($body->title)) ? '-'.$chpu.'.html' : '');
            }
        }

        return null;
    }

    public function isActive()
    {
        return (($url = $this->url()) &&  strstr($_SERVER['REQUEST_URI'], $url));
    }
}