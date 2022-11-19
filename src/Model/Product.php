<?php namespace Src\Model;

use Core\Service\Locale;
use Core\Service\Translation;

class Product extends AbstractModel
{
	function fileExists($path){
        $root = $_SERVER['DOCUMENT_ROOT'];
        return file_exists($root.$path);
    }

	public function previewImage() {
		if(!$this->fileExists($this->image)) return '/img/noimage/pizza.svg';
        return $this->image;
	}	

    public function url()
    {
        return '/'.Locale::getLocale().'/catalog/product/'.$this->id.(($chpu = Translation::translit($this->name)) ? '-'.$chpu : '');
    }
}