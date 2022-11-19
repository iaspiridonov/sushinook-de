<?php
namespace Src\Controller\Cms;
use Core\Middleware\Controller;
use Src\Controller\Cms;
use Core\Facade\Template;
use Core\Service\Locale;
use Core\Service\Registry;
use Core\Gateway\Subjects;
use Src\Model;
use Core\Gateway;


class Frontpad extends Controller {
    protected $auth;

    public $categories;

    protected function bootProcess()
    {
        $this->middleware(($this->auth = new Cms));

    }

    protected function bootRouting()
    {
        $this->GET('/', 'loadPage');
        $this->POST('/articles','articles');
    }

    protected function bootDispatch()
    {
        Template::defaults([
            'Auth' => $this->auth,
            'alerts' => Registry::pull('session.alerts'),
            'path' => [
                'controller' => $this->path(),
                'uri' => $this->path(Registry::get('http.request.path'))
            ]
        ]);
    }

    public function loadPage() {
        return $this->html( Template::render('cms/module/frontpad/index'));
    }

    function req($url,$data){
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_FAILONERROR, 1); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); 
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
        $result = curl_exec($ch); 
        curl_close($ch);

        return $result;
    }

    function format_name($name){
    	$exceptions = ['35смП','35смТ','30смТ','25см','30см','35см','25','30','35'];
    	foreach($exceptions as $ex){
    		$name = str_replace($ex, '', $name);
    	}
    	return trim($name);
    }

    function create_article($name,$obj,$v){
        if($article = $obj->articles()->select(['name'=>$name])->first()){
            $article->offsetSet('value',$v);
            $article->save();
        }else{
            ($article = new Model\Article([
                'name'=>$name,
                'value'=>$v]))
                ->save();
            $obj->articles()->save($article);
        }
    }

    function create_cost($name,$obj,$price){
        if($cost = $obj->prices()->select(['name'=>$name])->first()){
            $cost->offsetSet('value',$price);
            $cost->save();
        }else{
            ($cost = new Model\Price([
                'name'=>$name,
                'value'=>$price]))
                ->save();
            $obj->prices()->save($cost);
        }
    }

    public function articles(){
    	$secret = 'fn5BikeSTkArBYt8z2Q7sSss3zGiE9fKnaaBkee6tFiYrFSNtyHKa454kAaSAhsNhtbzAhsTRDD67hGnEe2E7Syhdz4DFfdRBea3rsG6T93Qfz5KDSdAzBHBZB5sH778b72tnyk7tbKDBDb5ffrEG36tnZKZTFYYDHbR6rKktiRTNSKY34nySfQ76kTNTEzhaB36SBf9KZTkEt7shhQ4ssZiFEZk7hz8Hi5nK9niZ8zrrtHBDkrYEb68FS';

        $params = [
            'secret' => $secret
        ];

        $params_prepared = '';
        foreach($params as $k => $v){
            $params_prepared .= '&'.$k.'='.$v;
        }

        $data = $this->req("https://app.frontpad.ru/api/index.php?get_products",$params_prepared);
        $json = json_decode($data, true, 512, JSON_UNESCAPED_UNICODE);

        $other_cat = Subjects::of('Category')->find(59);
        
        foreach($json['product_id'] as $k => $v){
        	$name = $json['name'][$k];
            $price = $json['price'][$k];
        	$base_name = $this->format_name($name);

        	if(!$prod = Subjects::of('Product')->select(['name'=>$base_name])->first()){
                ($prod = new Model\Product([
                    'hide'=>0,
                    'name'=>$base_name,
                    'halfHide'=>0
                ]))->save();
                $other_cat->products()->save($prod);
            }

            if(strstr($name, '25см')){
                $prod->offsetSet('price',$price);
                $prod->save();
            }
            // else{
            //     $prod->offsetSet('price',$price);
            //     $prod->save();
            // }

            $this->create_article($name,$prod,$v);
            $this->create_cost($name,$prod,$price);

        	if($ing = Subjects::of('Ingridients')->select(['name'=>$base_name])->first()){
                if(strstr($name, '25')){
                    $ing->offsetSet('price',$price);
                    $ing->save();
                }

        		$this->create_article($name,$ing,$v);
                $this->create_cost($name,$ing,$price);
        	}

        	if($combo = Subjects::of('Combo')->select(['name'=>$base_name])->first()){
        		$combo->offsetSet('article',$v);
        		$combo->save();
        	}
        }

        return $this->html('<p style="color:green;">Товары успешно синхронизированы</p>');
    }

}

?>