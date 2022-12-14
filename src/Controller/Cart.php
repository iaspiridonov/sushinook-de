<?php namespace Src\Controller;


use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Service\Mail;
use Core\Service\Registry;
use Src\Helpers\Form;
use Src\Integrations\ERP\Client;
use Zend\Db\Sql\Select;
use Src\Model\Order;

class Cart extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/', 'cartPage');
        $this->POST('/add','add');
        $this->POST('/remove','remove');
        $this->GET('/info', 'info');
        $this->POST('/change','change');
        $this->POST('/send','send');
        $this->POST('/sendcode','sendcode');
        $this->POST('/comboadd','comboadd');
        $this->POST('/comboremove','comboremove');
        $this->POST('/combochange','combochange');
        $this->POST('/halfpizzaadd','halfpizzaadd');
        $this->POST('/halfpizzachange','halfpizzachange');
        $this->POST('/halfpizzaremove','halfpizzaremove');
        $this->POST('/promo','promo');
        $this->POST('/resetpromo','resetpromo');
        $this->POST('/testq','testq');
        $this->POST('/bonuses','bonuses');
    }

    public function bonuses() {
        if(!empty($_POST['cod_sms']) && !empty($_POST['phone'])) {
            $phone = $_POST['phone'];
            $phone = preg_replace("/[^0-9]/", '', $phone);

            $countBonuses = $this->getBonuses('+' . $phone);

            return $this->json(['result' => true, 'count' => $countBonuses]);
        }

        return $this->json(['result' => false, 'error' => 'Der WhatsApp-Code konnte nicht gesendet werden']);
    }

    public function getBonuses($phone) {
        $phone = str_replace('+4', '', $phone);
        $result = Client::getBonuses($phone)->result;

        $bonuses = null;
        if (isset($result->data->data)) {
            $bonuses = $result->data->data->bonuses;
        }

        if ($bonuses === 0 || $bonuses){
            return $result->data->data->bonuses / 100;
        } else {
            return 0;
        }
    }

    public function resetSessionData()
    {
        Registry::set('session.cart',[]);
        Registry::set('session.combo',[]);
        Registry::set('session.half',[]);
        Registry::set('session.promo','');
        Registry::set('session.promoamount','');
        Registry::set('session.promoGifts','');
        Registry::set('session.certificateId','');
    }

    public function resetpromo(){
        Registry::set('session.promo','');
        Registry::set('session.promoamount','');
        Registry::set('session.promoGifts', '');
        Registry::set('session.certificateId', '');
        return $this->html('1');
    }

    function format_name($name){
        $exceptions = ['35??????','35??????','30??????','25????','30????','35????','25','30','35'];
        foreach($exceptions as $ex){
            $name = str_replace($ex, '', $name);
        }
        return trim($name);
    }

    function getProdByArticle($id){
        if($article = Subjects::of('Article')->select(['value'=>$id])->first()){
            $basename = $this->format_name($article->name);
            $prod = Subjects::of('Product')->select(['name'=>$basename])->first();
            return $prod;
        }
        return [];
    }

    static function getArticleIdByName($name): ?int
    {
        try {
            $article = (int) Subjects::of('Article')->select(['name'=>$name])->first()->value;
        } catch (\Throwable $e) {
            $name = str_replace('  ', ' ', $name);
            try {
                $article = (int) Subjects::of('Article')->select(['name'=>$name])->first()->value;
            } catch (\Throwable $e) {
                return null;
            }
        }

        return $article;
    }


    static function getIngsByCartItem(array $cartItem): array
    {
        $ings = [];
        $ingsAddList = explode(",", $cartItem['ingsAdd']);
        foreach($ingsAddList as $ingName){
            $ingName = trim($ingName);
            $ingridient = Subjects::of('Ingridients')->select(['name' => $ingName])->first();
            $ings[] = $ingridient;
        }

        return $ings;
    }

    function getProdSize($val){
        $exceptions = ['35??????','35??????','30??????','25????','30????','35????','25','30','35'];
        foreach($exceptions as $ex){
            if(strpos($val, $ex)) return (int)$ex;
        }
        return '';
    }

    function promocheck($code)
    {
        return $this->json(true);
    }

    public function testq()
    {
    }

    public function promo($code = ''){
        $result = Client::checkPromo($code)->result;

        if ($result->success !== true) {
            return $this->json(['result' => false]);
        }

        $finalPrice = 0;
        $originalPrice = 0;
        $freeProducts = [];
        foreach ($result->data->products as $product) {
            if ($product->price_discounted === 0) {
                $freeProducts[] = $product->title;
                continue;
            }
            $finalPrice += $product->price_discounted * ($product->amount / 1000);
            $originalPrice += $product->price_original * ($product->amount / 1000);
        }

        $promoAmount = $originalPrice - $finalPrice;

        if ($result->success === true) {
            Registry::set('session.promo', $result->data->certificate->title);
            Registry::set('session.promoamount', $promoAmount);
            Registry::set('session.certificateId', $result->data->certificate->id);
        }

        if ($freeProducts) {
            Registry::set('session.promoGifts', $freeProducts);
        }

        return $this->json([
            'result' => $result->success,
            'freeProducts' => $freeProducts
        ]);
    }

    public function halfpizzaremove(){
        $data = Registry::get('http.request.body');
        $id = $data['id'];

        $cart = Registry::get('session.half');
        unset($cart[$id]);

        $count = count($cart);
        if($count == 0) $cart = [];

        Registry::set('session.half', $cart);

        return $this->json(['count'=>$count]);
    }

    public function halfpizzachange(){
        $cart = Registry::get('session.half');
        $request = Registry::get('http.request.body');
        $id = $request['id'];
        $count = $request['count'];
        $cart[$id]['count'] = $count;
        Registry::set('session.half', $cart);
        return $this->html('<b>'.$cart[$id]['price']/100 * $count.'</b> EUR');
    }

    public function halfpizzaadd(){
        $cart = Registry::get('session.half');
        $request = Registry::get('http.request.body');

        // $price = $request['price'];
        // if((int)$price == 0) return $this->html('0');

        $left = $request['left'];
        $right = $request['right'];
        if(!$left || !$right) return $this->json('0');

        $size = $request['size'];
        $type = $request['type'];
        $typeCfg = ['????????????????????????','????????????'];
        if(!in_array($type, $typeCfg)) return $this->json('0');

        $left = trim($left);
        $leftProd = Subjects::of('Product')->select(['name'=>$left])->first();
        if(!$leftProd) return $this->json('0');
        $leftProdName = trim($leftProd->name);

        $leftProdPriceName = $leftProdName.' '.$size.'??????';
        $leftProdPrice = Subjects::of('Price')->select(['name'=>$leftProdPriceName])->first();
        if(!$leftProdPrice) return $this->json('0');

        $right = trim($right);
        $rightProd = Subjects::of('Product')->select(['name'=>$right])->first();
        if(!$rightProd) return $this->json('0');
        $rightProdName = trim($rightProd->name);

        $rightProdPriceName = $rightProdName.' '.$size.'??????';
        $rightProdPrice = Subjects::of('Price')->select(['name'=>$rightProdPriceName])->first();
        if(!$rightProdPrice) return $this->json('0');

        $price = (int)$leftProdPrice->value + (int)$rightProdPrice->value;

        // $leftIngs = $request['leftIngs'];
        // $rightIngs = $request['rightIngs'];

        $cartID = $left.$right.$type;

        $cartIDFirst = md5($left.$right.$type);
        $cartIDSecond = md5($right.$left.$type);
        if(isset($cart[$cartIDFirst])) $cartID = $left.$right.$type;
        elseif(isset($cart[$cartIDSecond])) $cartID = $right.$left.$type;

        $cartID = md5($cartID);
        $count = 1;

        if(isset($cart[$cartID])){
            $count = (int)$cart[$cartID]['count'] + $count;
            $cart[$cartID]['count'] = $count;
        }else{
            $cart[$cartID] = [
                'left' => $left,
                'right' => $right,
                // 'leftIngs' => $leftIngs,
                // 'rightIngs' => $rightIngs,
                'size' => $size,
                'type' => $type,
                'price' => str_replace(' ', '', $price),
                'count' => $count
            ];
        }

        Registry::set('session.half',$cart);
        return $this->html('1');
    }

    public function combochange(){
        $cart = Registry::get('session.combo') ? Registry::get('session.combo') : [];
        $request = Registry::get('http.request.body');
        $id = $request['id'];
        $count = $request['count'];
        $cart[$id]['count'] = $count;
        Registry::set('session.combo', $cart);
        return $this->html('<b>'.($cart[$id]['total']/100) * $count.'</b> EUR');
    }

    public function comboremove(){
        $data = Registry::get('http.request.body');
        $id = $data['id'];

        $cart = Registry::get('session.combo') ? Registry::get('session.combo') : [];
        unset($cart[$id]);

        $count = count($cart);
        if($count == 0) $cart = [];

        Registry::set('session.combo', $cart);

        return $this->json(['count'=>$count]);
    }

    public function comboadd(){
        $data = Registry::get('http.request.body');

        $id = @$data['comboID'];
        if(!$id) return $this->json('0');

        $combo = Subjects::of('Combo')->find($id);
        if(!$combo) return $this->json('0');

        $total = (int)$combo->price;

        $cart = Registry::get('session.combo') ? Registry::get('session.combo') : [];
        $comboList = json_decode($data['comboList'], true, 512, JSON_UNESCAPED_UNICODE);
        $cart[$data['comboID']] = [
            'details' => $comboList,
            'total' => $total,
            'name' => $combo->name,
            'desc' => $combo->description,
            'count' => 1,
            'image' => $combo->image,
            'code'  => $combo->article
        ];

        Registry::set('session.combo',$cart);

        return $this->json('1');
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

    function frontpadorder($message){
        $products = Registry::get('session.cart');
        $combo = Registry::get('session.combo');
        $half = Registry::get('session.half');

        $article_list = [];
        $count_list = [];
        $product_mod = [];

        $desc = '';

        if($products){
            $iter = 0;
            foreach($products as $id => $data){
                if(isset($data['countChange']) && $data['countChange'] == false) continue;

                $id = $data['id'];
                $name = $data['name'];
                $size = $data['size'];
                $type = $data['type'];
                $count = $data['count'];
                $parent = (int)$data['parent_id'];

                $articleName = $name.' '.$size.'????';
                if($type == '????????????') $articleName .= '??';

                if($parent != 50) $articleName = $name;

                $prod = Subjects::of('Product')->find($id);
                $article = $prod->articles()->select(['name'=>$articleName])->first();
                $article_list[$iter] = $article->value;
                $count_list[$iter] = $count;

                // if($data['ingsRemove']) $desc .= $articleName.' : '.$data['ingsRemove'];

                if($data['ingsAdd']){
                    $ings = explode(",",$data['ingsAdd']);
                    $prodIter = $iter;
                    foreach($ings as $ing){
                        $ingName = str_replace('+', '', $ing);
                        $ingName = trim($ingName);
                        $ingName .= ' '.$size;
                        $iter++;
                        if($ing_subject = Subjects::of('Article')->select(['name'=>$ingName])->first()){
                            $article_list[$iter] = $ing_subject->value;
                            $count_list[$iter] = '1';
                            $product_mod[$iter] = $prodIter;
                        }
                    }
                }
                $iter++;
            }
        }

        if($combo){
            foreach($combo as $id => $data){
                $count = $data['count'];

                $combo_subject = Subjects::of('Combo')->find($id);
                $article = $combo_subject->article;

                $article_list[] = $article;
                $count_list[] = $count;
            }
        }

        if($half){
            foreach($half as $id => $data){
                $left = $data['left'];
                $right = $data['right'];

                $articleNameLeft = $left.' 35??????';
                $article = Subjects::of('Article')->select(['name'=>$articleNameLeft])->first();
                $article_list[] = $article->value;
                $count_list[] = '1';

                $articleNameRight = $right.' 35??????';
                $article = Subjects::of('Article')->select(['name'=>$articleNameRight])->first();
                $article_list[] = $article->value;
                $count_list[] = '1';
            }
        }

        $secret = 'fn5BikeSTkArBYt8z2Q7sSss3zGiE9fKnaaBkee6tFiYrFSNtyHKa454kAaSAhsNhtbzAhsTRDD67hGnEe2E7Syhdz4DFfdRBea3rsG6T93Qfz5KDSdAzBHBZB5sH778b72tnyk7tbKDBDb5ffrEG36tnZKZTFYYDHbR6rKktiRTNSKY34nySfQ76kTNTEzhaB36SBf9KZTkEt7shhQ4ssZiFEZk7hz8Hi5nK9niZ8zrrtHBDkrYEb68FS';
        $info = ['secret'=>$secret];

        if($message['name']){
            $desc .= ' Name: '.$message['name'];
            $info['name'] = $message['name'];
        }
        $formatted_phone = str_replace([' ','(',')','-'], '', $message['phone']);
        $formatted_phone = urlencode($formatted_phone);
        $info['phone'] = $formatted_phone;
        if($message['email']){
            $desc .= ' E-mail: '.$message['email'];
            $info['mail'] = $message['email'];
        }

        $delivery = $message['delivery'];
        if($delivery == '????????????????'){
            if($message['street']){
                $desc .= ' ??????????: '.$message['street'];
                $info['street'] = $message['street'];
            }
            if($message['house']){
                $desc .= ' ??????: '.$message['house'];
                $info['home'] = $message['house'];
            }
            if($message['porch']){
                $desc .= ' ??????????????: '.$message['porch'];
                $info['pod'] = $message['porch'];
            }
            if($message['code']) $desc .= ' ?????? ??????????: '.$message['code'];
            if($message['level']){
                $desc .= ' ????????: '.$message['level'];
                $info['et'] = $message['level'];
            }
            if($message['flat']){
                $desc .= ' ????????????????: '.$message['flat'];
                $info['apart'] = $message['flat'];
            }
        }elseif($delivery == 'Abholung') $desc .= ' ????????????????: '.$message['branch'];

        $surrender = $message['surrender'];
        if($surrender) $desc .= ' ?????????? ??: '.$surrender;

        if($message['preorder'] != "Sofort"){
            $desc .= ' ?????????????????? '.$message['preorder'];
        }

        $info['descr'] = $desc;

        $payment = $message['payment'];
        // if($payment) $desc .= ' ????????????: '.$payment;

        $pay_cfg = [
            '??????????????????' => '1',
            '???????????? ??????????????' => '2',
            '????????????' => '696'
        ];

        if($payment) $info['pay'] = $pay_cfg[$payment];

        $rycleInfo = $this->rycleinfo();

        if($rycleInfo['promoamount']) $info['sale_amount'] = $rycleInfo['promoamount'];

        $promo = $message['promo'];
        if($promo){
            $info['certificate'] = $promo;
        }

        // $info['score'] = 300;

        $date = date('Y-m-d');

        if($message['time_from'] != '' && $message['time_to'] != ''){
            $timeArr = explode(':', $message['time_from']);

            $leftTime = '';
            if($timeArr[1] == 30){
                $leftTime = $timeArr[0].':'.'00';
            }else{
                $leftTime = ($timeArr[0] - 1).':'.'30';
            }

            $datetime = date('Y-m-d H:i:s',strtotime($date.' '.$leftTime));
            $info['datetime'] = $datetime;
        }else{
            $datetime = date('Y-m-d H:i:s',strtotime($date.' '.$message['time_from']));
            $info['datetime'] = $datetime;
        }

        $reqdata = '';
        foreach($info as $k => $v){
            $reqdata .= '&'.$k.'='.$v;
        }

        foreach($article_list as $k => $v){
            $reqdata .= '&product['.$k.']='.$v;
            $reqdata .= '&product_kol['.$k.']='.$count_list[$k];
        }

        foreach($product_mod as $k => $v){
            $reqdata .= '&product_mod['.$k.']='.$v;
        }

        $req_res = $this->req('https://app.frontpad.ru/api/index.php?new_order',$reqdata);

        $req_res_file_path = $_SERVER['DOCUMENT_ROOT'].'/frontpad/';
        $req_res_file_name = date('Y-m-d H:i:s').'.txt';
        file_put_contents($req_res_file_path.$req_res_file_name, $req_res);

        $req_res = json_decode($req_res,true);
        return $req_res;
    }

    public function send() {
        $result = Client::sendOrder()->result;

        if ($result === 'code_error') {
            return $this->json('code_error');
        }

        if ($result->success != true) {
            return $this->json(['status'=>false]);
        }

        $this->resetSessionData();
        return $this->json(['status'=>true]);
    }

    public function sendcode()
    {
        if(!empty($_POST['cod_sms']) && !empty($_POST['phone'])) {
            // ???????????????? ???????? ?? WhatsApp

            $whatsAppMessageWasSent = false;
            $cod_sms = $_POST['cod_sms'];
            $telephone_sms = $_POST['phone'];

            $phoneForWhatsApp = preg_replace("/[^0-9]/", '', $telephone_sms);

            $dataWhatsApp = [
                "phone" => $phoneForWhatsApp,
                "body"  => $cod_sms
            ];
            $data_string = json_encode($dataWhatsApp, JSON_UNESCAPED_UNICODE);
            $curl = curl_init('https://api.1msg.io/449094/sendMessage?token=cpg2g9sd2526wa81');
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
            // ?????????????????? ?? ???????? ??????????????. (false - ?? ???????? ??????????????)
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
            );

            $resultWhatsApp = json_decode(curl_exec($curl));
    
            curl_close($curl);

            if (isset($resultWhatsApp->sent)) {
                 $whatsAppMessageWasSent = $resultWhatsApp->sent != false;
            }

            if ($whatsAppMessageWasSent) {
                $bonusResult = $this->getBonuses('+' . $phoneForWhatsApp);
                return $this->json(['status' => true, 'count' => $bonusResult]);
            } else {
                return $this->json(['status' => false, 'error' => 'Der WhatsApp-Code konnte nicht gesendet werden']);
            }
        }

        return $this->json(['status' => false, 'error' => 'Der WhatsApp-Code konnte nicht gesendet werden']);
    }

    public function change() {
        $cart = Registry::get('session.cart') ? Registry::get('session.cart') : [];
        $request = Registry::get('http.request.body');
        $id = $request['id'];
        $count = $request['count'];
        $cart[$id]['count'] = $count;

        Registry::set('session.cart', $cart);
        $code = Registry::get('session.promo');
        if(!$code) return $this->json('');

        return $this->promocheck($code);
    }

    public function info() {
        $sum = 0;
        $total_count = 0;
        $cart = Registry::get('session.cart',[]);
        if($cart){
            foreach($cart as $id=>$product) {
                $sum += $product['price'] * $product['count'];
                $total_count += $product['count'];
            }
        } else {
            $cart = [];
        }
        $combo = Registry::get('session.combo',[]);
        if($combo){
            foreach($combo as $id=>$item){
                $sum += $item['total'] * $item['count'];
                $total_count += $item['count'];
            }
        } else {
            $combo = [];
        }
        $half = Registry::get('session.half',[]);
        if($half){
            foreach($half as $id=>$item){
                $sum += $item['price'] * $item['count'];
                $total_count += $item['count'];
            }
        } else {
            $half = [];
        }

        $count = count($cart) + count($combo) + count($half);
        $sum = number_format($sum,0,'',' ');

        $total = str_replace(' ', '', $sum);
        $promoamount = Registry::get('session.promoamount');
        if($promoamount) $promoamount = (int)$total - (int)$promoamount;

        return $this->json(['sum'=>$sum,'count'=>$count,'promoamount'=>$promoamount,'popup'=>$this->popup(),'check'=>$this->checkBlock(),'totalcount'=>$total_count]);
    }

    function checkBlock(){
        $cart = Registry::get('session.cart',[]);
        $combo = Registry::get('session.combo',[]);
        $half = Registry::get('session.half',[]);
        $info = $this->rycleinfo();
        return Template::render('src/cart/check',['products'=>$cart,'combo'=>$combo,'half'=>$half,'info'=>$info]);
    }

    function correct($data,$session){
        if(!$data) return '';
        foreach($data as $k => $v){
            if(!$v['name']) unset($data[$k]);
        }
        Registry::set($session,$data);
        return $data;
    }

    public function cartPage() {
        $breadcrumbs[] = 'Warenkorb';
        $purchases = Subjects::of('Purchases')->find(1);
        if($purchases->status == 1){
            return $this->html(Template::render('src/cart/error',['breadcrumbs'=>$breadcrumbs, 'purchases' => $purchases]));
        }
        $products = Registry::get('session.cart');
        $products = $this->correct($products,'session.cart');

        $combo = Registry::get('session.combo');
        // $combo = $this->correct($combo,'session.combo');

        $half = Registry::get('session.half');
        // $half = $this->correct($half,'session.half');

        $promo = Registry::get('session.promo');
        // Registry::set('session.promo','');

        $promoGifts = Registry::get('session.promoGifts');

        $info = $this->rycleinfo();

        $promoamount = null;
        if($info['promoamount']) $promoamount = (int)$info['sum'] - (int)$info['promoamount'];


        $recommended = Subjects::of('Product')->select(function($select){
            $select->where(' recommended = "1" AND hide = "0" ');
            $select->order('sort');
        })->get();
        $sauces = Subjects::of('Product')->select(function($select){
            $select->where(' isSauce = "1" AND hide = "0" ');
            $select->order('sort');
        })->get();
        $user = Registry::get('session.user') ?? ['id' => null];
        $account = Subjects::of('Account')->find($user['id']);

        $branches = Subjects::of('Branch')->get();

        $currentWeekNumber = date('N');
        $workTime = Subjects::of('WorkTime')->select('weeks LIKE "%'.$currentWeekNumber.'%"')->first();

        // $workTime = Subjects::of('WorkTime')->find(1);

        $successText = Subjects::of('VariableEditor')->find(1);


        $currentTime = date('H:i');

        $currentHour = date('H') + 1;

        $currentMinute = date('i');

        if($currentMinute <= 20) {
            $currentMinute = '00';
        }elseif($currentMinute > 20 && $currentMinute <= 40){
            $currentMinute = 30;
        }elseif($currentMinute > 40){
            $currentHour = date('H') + 2;
            $currentMinute = '00';
        }

        return $this->html(Template::render('src/cart/index',[
            'products'=>$products,
            'combo'=>$combo,
            'half'=>$half,
            'breadcrumbs'=>$breadcrumbs,
            'recommended'=>$recommended,
            'sauces'=>$sauces,
            'info'=>$info,
            'account'=>$account,
            'promo'=>$promo,
            'promoamount'=>$promoamount,
            'branches'=>$branches,
            'workTime'=>$workTime,
            'workTimeActive' => $this->workTimeCheck(),
            'successText' => $successText,
            'todayDate' => date('d.m.Y'),
            'orderHour' => $currentHour,
            'orderMinute' => $currentMinute,
            'currentTime' => $currentTime,
            'promoGifts' => $promoGifts
        ]));
    }

    public function add() {
        $request = Registry::get('http.request.body');

        $count = (int)$request['count'];
        if($count == 0) return $this->json('0');

        $id = $request['id'];
        $product = Subjects::of('Product')->find($id);
        if(!$product) return $this->json('0');

        $size = $request['size'];
        $type = $request['type'];

        $typeIng = '';
        if($type == '????????????') $typeIng = '??';

        $productName = trim($product->name);
        $price = (int)$product->price;

        $ingsAdd = $request['ingsAdd'];

        if($product->addIngridients->count() && $ingsAdd){
            $ingsAddList = explode(",",$ingsAdd);
            foreach($ingsAddList as $ingName){
                $ingName = trim($ingName);
                $ingridient = Subjects::of('Ingridients')->select(['name' => $ingName])->first();
                $price += (int) $ingridient->price;
            }
        }

        $cart = Registry::get('session.cart') ? Registry::get('session.cart') : [];

        $cartID = $productName.'added:'.$ingsAdd.$typeIng.$size;
        $cartID = md5($cartID);
        if(isset($cart[$cartID])) $count = (int)$cart[$cartID]['count'] + $count;

        $blockID = str_replace('-block', '', $product->parent->blockID);

        $cart[$cartID]  = [
            'name'=>$productName,
            'desc'=>$product->description,
            'price'=>$price,
            'count'=>$count,
            'image'=>$product->previewImage(),
            'id'=>$product->id,
            'parent_id'=>$product->parent->id,
            'block_id'=>$blockID,
            'link'=>$product->url(),
            'type'=>$type,
            'size'=>$size,
            'ingsAdd'=>$ingsAdd
        ];

        Registry::set('session.cart', $cart);

        return $this->json('1');
    }

    public function remove() {
        $request = Registry::get('http.request.body');
        $id = $request['id'];

        $cart = Registry::get('session.cart') ? Registry::get('session.cart') : [];
        unset($cart[$id]);

        if(!$cart) $cart = [];

        Registry::set('session.cart', $cart);

        $count = count($cart);

        if($code = Registry::get('session.promo')) $this->promocheck($code);

        return $this->json(['count'=>$count]);
    }

    public function generateUniqueGuid()
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}