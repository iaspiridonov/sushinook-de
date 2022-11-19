<?php namespace Src\Controller;


use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Service\Mail;
use Core\Service\Registry;
use Zend\Db\Sql\Select;
use Src\Model\Order;

class Constructor extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/', 'page');
        $this->POST('/ings','ings');
    }

    public function ings(){
        $data = Registry::get('http.request.body');
        $id = $data['id'];
        $prod = Subjects::of('Product')->find($id);
        $out = [];
        // if($prod->removeIngridients) return $this->html(Template::render('src/constructor/ings',['ings'=>$prod->removeIngridients]));
        return $this->html('');
    }

    public function page(){
        // Registry::set('session',[]);
        // $cart = Registry::get('session');
        // var_dump($cart);
        $products = Subjects::of('Category')->find(50)->products()->select(function($select){
            $select->where('hide = "0"');
            $select->order('sort');
        })->get();
        return $this->html(Template::render('src/constructor/page',['products'=>$products]));
    }
}