<?php namespace Src\Model;

use Core\Service\Locale;
use Core\Service\Translation;

class Category extends AbstractModel
{
    public function url()
    {
        if($this->products()->count()) {
            return '/'.Locale::getLocale().'/catalog/'.$this->parent->id.(($chpu = Translation::translit($this->parent->name)) ? '-'.$chpu : '').'/'.$this->id.(($chpu = Translation::translit($this->name)) ? '-'.$chpu : '');
        } else {
            return '/'.Locale::getLocale().'/catalog/'.$this->id.(($chpu = Translation::translit($this->name)) ? '-'.$chpu : '');
        }
    }

    public function productsCount() {
    	$count = $this->products->count();

    	foreach($this->categories as $cat) {
    		$count += $cat->products->count();
    	}
    	return $count;
    }

    public function isActive()
    {
        return (($url = $this->url()) &&  strstr($_SERVER['REQUEST_URI'], $url));
    }
}