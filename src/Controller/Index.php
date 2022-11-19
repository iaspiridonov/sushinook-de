<?php namespace Src\Controller;

use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Service\Mail;
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
        // $this->GET('/test', 'test');
        $this->POST('/combo-modal/', 'comboModal');
        // $this->POST('/product-modal/', 'productModal');
        $this->POST('/passrecover','passrecover');
        $this->GET('/constructor-modal','constructorModal');
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

    public function constructorModal(){
        $constructorProducts = Subjects::of('Category')->find(50)->products()->select(function($select){
            $select->where('hide = "0" AND halfHide = "0"');
            $select->order('sort');
        })->get();

        return $this->html(Template::render('src/index/constructor-modal-desktop',[
            'constructorProducts' => $constructorProducts]));
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

    public function test() {
        
        $frontpad_articles = ["304","344","357","356","301","102","252","250","271","159","261","260","97","98","160","100","99","101","152","155","154","156","157","158","161","162","163","165","164","166","153","117","118","120","119","121","140","77","78","80","79","81","221","225","223","167","168","170","169","171","182","183","185","184","186","112","113","115","114","116","122","123","125","124","126","187","188","190","189","191","197","198","200","199","201","172","173","175","174","176","137","138","87","139","141","202","203","205","204","206","67","68","70","69","71","212","213","215","214","216","88","90","89","91","82","83","85","84","86","207","208","210","209","211","132","133","135","134","136","127","128","130","129","131","192","193","194","195","196","142","143","145","144","146","92","93","95","94","96","147","148","150","149","151","177","178","180","179","181","275","273","243","245","277","278","229","230","227","228","226","231","235","236","103","105","104","106","312","313","246","305","306","307","309","310","311","314","315","316","317","300","298","299","297","302","303","293","318","251","280","279","262","263","264","319","320","321","322","218","290","294","330","328","345","346","348","347","349","358","269","270","240","241","259","296","265","266","267","268","72","292","308","335","338","217","327","326","324","325","73","75","74","76","244","276","274","272","107","108","110","111","109","333","234","233","242","340","295","239","329","237","253","254","255","256","257","232","66","249","258","323","331","332","289","36","238","15","1","19","7","16","10","13","4","31","22","25","28","34","37","8","9","2","3","5","6","20","21","11","12","14","23","24","26","27","29","30","32","33","35","17","18","38","220","222","224","39","40","41","64","42","43","44","45","46","47","48","49","50","51","52","53","54","55","56","57","58","59","60","61","62","63","65","247","248","291","350","351","352","219","341","342","343","354","353","355","27900","27901","9002","9001","22900","22600","22700","22800","29250","229000","23000","2253","2263","5200","5201","22100","22101","248000","10555","10556","1234568","123456878","3070","1223554"];

        $site_articles = Subjects::of('Article')->get();
        foreach($site_articles as $article){
            if(!in_array($article->value, $frontpad_articles)) dump($article->value);
        }

        return $this->html(Template::render('src/index/test'));
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
        // Registry::set('session',[]);



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

        $constructorProducts = Subjects::of('Category')->find(50)->products()->select(function($select){
            $select->where('hide = "0" AND halfHide = "0"');
            $select->order('sort');
        })->get();

        $sizes = [];

        $pizzaSettings = Subjects::of('PizzaSetting')->select(function($select){
            $select->where('(traditionaldough = 1 or thindough = 1)');
            $select->where(['status' => 1]);
        })->get();

        foreach ($pizzaSettings as $item) {
            $sizes[] =  $item['name'];
        }


        return $this->html(Template::render('src/index/index',[
            'pizzaSettings' => $pizzaSettings,
            'constructorProducts' => $constructorProducts,
            'slides'=>$slides,
            'combo'=>$combo,
            'sections'=>$sections,
            'cart'=>$cart,
            'text'=>$text,
            'seo'=>$seo,
            'sizes'=>$sizes]));
    }

}
