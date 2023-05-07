<?php namespace Src\Controller;

use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Service\Mail;
use Src\Helpers\UTM;
use Zend\Db\Sql\Select;
use Core\Service\Registry;
use Src\Model\Subscriber;
use Src\Model\Boutique;
class Index extends AbstractController
{
    protected function bootRouting()
    {
        $this->GET('/', 'indexPage');
        $this->GET('/about', 'aboutPage');
        $this->GET('/contacts', 'contactsPage');
        $this->POST('/combo-modal/', 'comboModal');
        $this->POST('/passrecover','passrecover');
        $this->POST('/isshown','isshown');
        $this->POST('/setshown','setshown');
        $this->GET('/plug','plug');
        $this->GET('/test', 'testPage');
    }

    public function testPage(){
        
    }

    public function plug(){
        $plug = Subjects::of('Plug')->find(1);
        return $this->html(Template::render('src/plug/index',['plug'=>$plug]));
    }

    public function setshown(){
        $_SESSION['notWorkShown'] = true;
        return $this->json($_SESSION['notWorkShown']);
    }

    public function isshown(){
        $isshown = false;
        if(@$_SESSION['notWorkShown']) $isshown = true;
        return $this->json($isshown);
    }

    function passGen(){
        $pass = '';
        $i = 1;
        while($i <= 10){
            $pass .= rand(1,20);
            $i++;
        }
        return $pass;
    }

    public function passrecover(){
        $email = $_POST['email'];

        $account = Subjects::of('Account')->select(['email'=>$email])->first();
        if(!$account) return $this->json(['error'=>'<p style="color:red; font-size:15px;">Пользователь с указанным E-mail не найден</p>','message'=>'']);
        
        $new_pass = $this->passGen();
        $account->offsetSet('password',md5($new_pass));
        $account->save();

        $mail = (new Mail)->subject('Восстановление пароля')->body(Template::render('src/mail/recover', [
            'login' => $account->phone,
            'pass' => $new_pass
        ]))->send($email);

        return $this->json(['error'=>'','message'=>'<p style="color:green; font-size:15px;">Письмо с новыми данными отправлено на почту.</p>']);
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

    function get_user_bonus($phone){
        $secret = 'fn5BikeSTkArBYt8z2Q7sSss3zGiE9fKnaaBkee6tFiYrFSNtyHKa454kAaSAhsNhtbzAhsTRDD67hGnEe2E7Syhdz4DFfdRBea3rsG6T93Qfz5KDSdAzBHBZB5sH778b72tnyk7tbKDBDb5ffrEG36tnZKZTFYYDHbR6rKktiRTNSKY34nySfQ76kTNTEzhaB36SBf9KZTkEt7shhQ4ssZiFEZk7hz8Hi5nK9niZ8zrrtHBDkrYEb68FS';
        $info = ['secret'=>$secret];
        $info['client_phone'] = $phone;

        $reqdata = '';
        foreach($info as $k => $v){
            $reqdata .= '&'.$k.'='.$v;
        }

        $req_res = $this->req('https://app.frontpad.ru/api/index.php?get_client',$reqdata);

        return $req_res;
    }

    public function comboModal() {
        $id = $_POST['id'];
        $combo = Subjects::of('Combo')->find($id);
        $seo = $combo->seo;
        return $this->html(Template::render('src/index/combo-modal',['combo'=>$combo,'seo'=>$seo]));
    }

    // public function productModal() {
    //     $id = $_POST['id'];
    //     $product = Subjects::of('Product')->find($id);
    //     $section = Subjects::of('Category')->find(1);
    //     return $this->html(Template::render('src/index/product-modal',['item'=>$product, /*'section' => $section*/]));
    // }

    public function aboutPage() {
        $breadcrumbs[''] = 'О компании';
        $page = Subjects::of('AboutPage')->find(1);
        return $this->html(Template::render(    'src/about/index',['breadcrumbs'=>$breadcrumbs,'page'=>$page]));
    }

    public function contactsPage() {
        $breadcrumbs[''] = 'Контакты';
        return $this->html(Template::render('src/contacts/index',['breadcrumbs'=>$breadcrumbs]));
    }
  
    public function indexPage(){
        // Установим UTM-метки в куки
        UTM::setUTM();

        $slides = Subjects::of('Slider')->find(1)->elements()->select(function(Select $select){
            $select->order('sort');
        })->get();
        $sections = Subjects::of('Category')->select(function($select){
            $select->order('sort');
        })->get();

        $cart = Registry::get('session.cart',[]);
        $text = Subjects::of('InfoBlock')->find(1);
        $combo = Subjects::of('Combo')->select(function($select){
            $select->order('sort');
        })->get();
        $seo = Subjects::of('Seo')->find(1);

        $gifts = Subjects::of('Gifts')->select(function($select){
            $select->where(['hidden' => false]);
            $select->order(['price' => 'ACS']);
        })->get();

        return $this->html(Template::render('src/index/index',[
            'constructorProducts' => [],
            'slides'=>$slides,
            'combo'=>$combo,
            'sections'=>$sections,
            'cart'=>$cart,
            'text'=>$text,
            'seo'=>$seo,
            'gifts' => $gifts
        ]));
    }

}
