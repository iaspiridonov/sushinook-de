<?php namespace Src\Controller;

use Core\Service\Validator;
use Core\Service\Registry;
use Core\Service\Translation;
use Core\Facade\Template;
use Core\Service\Locale;
use Core\Gateway\Subjects;
use Src\Model;

class City extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/', 'index');
        $this->GET('/parser','parser');
    }

    public function index(){
        $w = @$_GET['w'];
        if(!$w) return $this->json('');

        $streets = Subjects::of('Street')->select(['name LIKE ?'=>['%'.$w.'%']])->get();
        $out = [];
        foreach($streets as $k => $data){
            $out[] = $data->name;
        }
        return $this->html(Template::render('src/city/list',['list'=>$out]));
    }

    public function parser(){
        $chars = [
            "А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ъ", "Ы", "Ь", "Э", "Ю", "Я"
        ];
        $root = $_SERVER['DOCUMENT_ROOT'];
        $path = $root.'/city/';
        $file_name = 'list.json';
        $out = [];
        foreach($chars as $c){
            if(file_exists($path.$c.'.json')){
                $json = file_get_contents($path.$c.'.json');
                $data = json_decode($json);
                foreach($data->result as $k => $v){
                    $name = $v->name;
                    $prefix = $v->shortStreetTypeName;
                    $first = mb_substr($name, 0, 1, 'utf-8');
                    if($first == $c){
                        $out[] = $prefix.' '.$name;
                    }
                    // shortStreetTypeName
                    // fullStreetTypeName
                }
            }
        }

        $json = file_get_contents($path.'цифры.json');
        $data = json_decode($json);
        foreach($data->result as $k => $v){
            $name = $v->name;
            $prefix = $v->shortStreetTypeName;
            if(!in_array($prefix.' '.$name, $out)) $out[] = $prefix.' '.$name;
        }

        $result = json_encode($out, JSON_UNESCAPED_UNICODE);
        // file_put_contents($path.'city.json', $result);
        var_dump($result);
    }
}
