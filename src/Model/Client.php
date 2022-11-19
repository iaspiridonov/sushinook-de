<?php namespace Src\Model;

use Core\Service\Locale;
use Core\Service\Translation;
use Core\Gateway\Subjects;

class Client extends AbstractModel
{
    public function url()
    {
        return '/'.Locale::getLocale().'/shops/'.$this->id.(($chpu = Translation::translit($this->name)) ? '-'.$chpu : '');
    }

	public function categoryName() {
		if(!$this->category) return '';
		if($activity = Subjects::of('Activity')->find($this->category)) {
			return $activity->name;
		}
	}

	public function getTier() {
		if($boutique = Subjects::of('Boutique')->select(['shop_id'=>$this->id])->first()) {
			return $boutique->parent->id;
		} else {
			return 0;
		}
	}
}