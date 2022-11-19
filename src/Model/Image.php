<?php namespace Src\Model;

use Core\Service\Locale;
use Core\Service\Translation;
use Core\Service\Resizer;

class Image extends AbstractModel
{
    public function resize($width, $height, $proportion = true) {
        return Resizer::resize($this->image, $width, $height, $proportion);
    }
}