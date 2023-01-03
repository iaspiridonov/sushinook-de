<?php namespace Src\Controller;

use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Middleware\Controller;
use Core\Service\Locale;
use Core\Service\Registry;
use Illuminate\Support\Str;
use Zend\Db\Sql\Select;

abstract class AbstractController extends Controller
{
    protected $auth;
    protected $breadcrumbs = [];
    public function bootDispatchTemplateDefaults()
    {

        $contacts = Subjects::of('Contacts')->find(1);
        $slogan = Subjects::of('Variable')->find(3);
        $copyright = Subjects::of('Variable')->find(2);
        $currentWeekNumber = date('N');
        $workTime = Subjects::of('WorkTime')->select('weeks LIKE "%'.$currentWeekNumber.'%"')->first();
        $user = Registry::get('session.user');
        $infoModal = Subjects::of('WarningModal')->find(1);

        Template::defaults([
            'CMS' => new \Core\Service\CMS(),
            'catalogList'=>Subjects::of('Menu')->find(2)->elements,
            'Auth' => ($this->auth = new Cabinet),
            'locales' => $this->locales(),
            'lang'=>Locale::getLocale(),
            'breadcrumbs'=>$this->breadcrumbs,
            'contacts'=>$contacts,
            'slogan'=>$slogan,
            'socials'=>$this->socials(),
            'copyright'=>$copyright,
            'pagesMap' => $this->pagesMap(),
            'isWork' => $this->workTimeCheck(),
            'workModalShow' => $this->workTimeModalShow(),
            'infoModal' => $infoModal,
            'workTime' => $workTime,
            'header' => [
                'phones' => $this->phones(),
                'menu' => $this->menu(),
                'popup' => $this->popup(),
                'rycleinfo' => $this->rycleinfo(),
//                'recommended' => $this->recommended(),
                'recommended' => [],
                'user' => $user
            ],
            'footer' => [
//                'menu' => $this->footermenu(4),
                'menu2' => $this->footermenu(2),
                'phones' => $this->phonesList()
            ],
            'isCart' => (bool) strpos($_SERVER['REQUEST_URI'], '/cart')
        ]);
    }

    function plug_check(){
        $plug = Subjects::of('Plug')->find(1);
        return $plug->enabled;
    }

    function workTimeModalShow(){
        // unset($_SESSION['notWorkShown']);
        $isShown = @$_SESSION['notWorkShown'];
        if($isShown == true) return true;

        $current_time = time();


        $currentWeekNumber = date('N');

        $workTime = Subjects::of('WorkTime')->select('weeks LIKE "%'.$currentWeekNumber.'%"')->first();

        if(!$workTime){
            return false;
        }

        $start_time = strtotime($workTime['startTime']);
        $end_time = strtotime($workTime['endTime']);


        if($current_time - $start_time < 0) return false;
        if($end_time - $current_time <= 0) return false;
        return true;
    }

    function workTimeCheck(){
        $currentWeekNumber = date('N');

        $workTime = Subjects::of('WorkTime')->select('weeks LIKE "%'.$currentWeekNumber.'%"')->first();
        if(!$workTime){
            return false;
        }
        $current_time = time();
        // $workTime = Subjects::of('WorkTime')->find(1);
        $start_time = strtotime($workTime['startTime']);
        $end_time = strtotime($workTime['endTime']);

        $currentDateTime = new \DateTime('now', new \DateTimeZone('Europe/Berlin'));
        $currentHour = $currentDateTime->format('H');
        if ($currentHour > 13 && $currentHour < 17 && (int) $currentWeekNumber !== 6 && (int) $currentWeekNumber !== 7) return false;
        if($current_time - $start_time < 0) return false;
        if($end_time - $current_time <= 0) return false;
        return true;
    }

    function pagesMap(){
        $menu = Subjects::of('Menu')->select(function($select){
            $select->where(['id'=>[2,3]]);
            $select->order('sort');
        })->get();
        return Template::render('src/pages-map',['menu'=>$menu,'lang'=>Locale::getLocale()]);
    }

    function recommended(){
//        $prods = Subjects::of('Product')->select(function($select){
//            $select->where(' recommended = "1" AND hide = "0"');
//            $select->order('sort');
//        })->get() ?? [];
//        return Template::render('src/recommended',['proAbstractController.phpds'=>$prods]);
    }

    function rycleinfo() {
        $sum = 0;
        $total_count = 0;
        $cart = Registry::get('session.cart',[]) ?? [];
        try {
            if($cart){
                foreach($cart as $id=>$product) {
                    $sum += $product['price'] * $product['count'];
                    $total_count += $product['count'];
                }
            } else {
                $cart = [];
            }
            $combo = Registry::get('session.combo',[]) ?? [];
            if($combo){
                foreach($combo as $id=>$item){
                    $sum += $item['total'] * $item['count'];
                    $total_count += $item['count'];
                }
            } else {
                $combo = [];
            }
            $half = Registry::get('session.half') ?? [];
            if($half){
                foreach($half as $id=>$item){
                    $sum += $item['price'] * $item['count'];
                    $total_count += $item['count'];
                }
            } else {
                $half = [];
            }
        } catch (\Exception $e) {
            Registry::set('session.cart',[]);
            header('Location: /', true, 307);
        }

        $promoamount = Registry::get('session.promoamount');
        if(!$promoamount) $promoamount = null;

        $count = count($cart) + count($combo) + count($half);
        return ['sum'=>$sum,'count'=>$count,'promoamount'=>$promoamount,'totalcount'=>$total_count];
    }

    function popup(){
        $cart = Registry::get('session.cart',[]);
        if(!$cart || count($cart) == 0) $cart = '';
        $combo = Registry::get('session.combo');
        if(!$combo || count($combo) == 0) $combo = '';
        $half = Registry::get('session.half');
        if(!$half || count($half) == 0) $half = '';

        return Template::render('src/cart-popup',['cart'=>$cart,'combo'=>$combo,'halfpizza'=>$half]);
    }

    function socials(){
        $title = Subjects::of('Social')->find(1);
        $socials = Subjects::of('Social')->find(1)->elements()->select(function(Select $select){
            $select->order('sort ASC');
        })->get();
        return Template::render('src/social',['title'=>$title,'socials'=>$socials]);
    }

    function footermenu($id){
        $title = Subjects::of('Menu')->find($id);
        $menu = Subjects::of('Menu')->find($id)->elements()->select(function(Select $select){
            $select->order('sort ASC');
        })->get();
        return Template::render('src/footer-menu',['title'=>$title,'menu'=>$menu]);
    }

    function menu(){
        $menu = Subjects::of('Menu')->find(1)->elements()->select(function(Select $select){
            $select->order('sort ASC');
        })->get();
        return Template::render('src/menu',['menu'=>$menu]);
    }

    function phonesList(){
        $contacts = Subjects::of('Contacts')->find(1);
        $phones = $contacts['phones'];
        return Template::render('src/phones-list',['phones'=>$phones]);
    }

    function phones(){
        $contacts = Subjects::of('Contacts')->find(1);
        $phones = $contacts['phones'];
        $count = count(explode("\n",$phones));
        return Template::render('src/phones',['phones'=>$phones,'count'=>$count]);
    }

    protected function locales()
    {
        $locales = [];

        foreach (Locale::getLocales() as $locale) {
            if ($locale == Locale::getLocale()) continue;
            $locales [Str::upper($locale)] = in_array(Registry::get('http.controller.path'), ['/'.Locale::getLocale()]) ? '/'.$locale : substr_replace(Registry::get('http.controller.path'), '/'.$locale, 0, 3).(Registry::get('http.request.path') != '/' ? Registry::get('http.request.path') : '');
        }
        $langs = ['RU'=>'Казахстан - Русский','EN'=>'Казахстан - Английский', 'DE' => 'Германия - Немецкий'];
        return Template::render('src/common/switch-locale', ['locales' => $locales, 'Str' => new Str, 'langs'=>$langs]);
    }
}