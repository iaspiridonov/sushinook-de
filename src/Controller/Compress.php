<?php namespace Src\Controller;

use Core\Service\Validator;
use Core\Service\Registry;
use Core\Service\Translation;
use Core\Facade\Template;
use Core\Service\Locale;
use Core\Gateway\Subjects;
use Src\Model;

class Compress extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/', 'index');
    }

    function compress($path,$quality){
        $image = $path;
        $info = getimagesize($path);
        if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($path);
        if ($info['mime'] == 'image/png') $image = imagecreatefrompng($path);

        imagejpeg($image, $path, $quality);

        return $path;
    }

    public function index(){
        $root = $_SERVER['DOCUMENT_ROOT'];
        $path = $root.'/uploads/images/2020/08/28/';
        // $dirs = scandir($path);
        // unset($dirs[0]);
        // unset($dirs[1]);
        // foreach($dirs as $d){
            $files = scandir($path.$d.'/');
            unset($files[0]);
            unset($files[1]);
            foreach($files as $f){
                $fPath = $path.$d.'/'.$f;
                // $dest = $this->compress($fPath,90);
                var_dump($dest);
            }
        // }
    }
}
