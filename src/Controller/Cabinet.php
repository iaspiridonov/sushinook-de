<?php namespace Src\Controller;

use Core\Service\Validator;
use Core\Service\Registry;
use Core\Service\Translation;
use Core\Facade\Template;
use Core\Service\Locale;
use Core\Gateway\Subjects;
use Src\Model\Account;

class Cabinet extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/', 'cabinetPage');
        $this->POST('/save', 'save');
        $this->GET('/logoutget','logoutget');
        $this->POST('/logout','logout');
        $this->POST('/passchange','passchange');
        $this->GET('/order/{id:\d+}', 'orderPage');
        $this->POST('/replyorder','replyorder');
        $this->GET('/orders','ordersList');
        $this->POST('/auth','auth');
    }

    public function auth(){
        $user = Registry::get('session.user');
        if(!$user) return $this->html('0');
        return $this->html('1');
    }

    public function ordersList(){
        $user = Registry::get('session.user');
        if(!$user) return $this->redirect('/'.Locale::getLocale().'/');
        $account = Subjects::of('Account')->find($user['id']);
        $orders = Subjects::of('Order')->select(function($select) use ($user){
            $select->where(['userID'=>$user['id']]);
            $select->order('date DESC');
        })->get();
        return $this->html(Template::render('src/cabinet/orders-page',['account'=>$account,'orders'=>$orders,'orderPage'=>true]));
    }

    public function replyorder(){
        $data = Registry::get('http.request.body');
        $order = Subjects::of('Order')->find($data['id']);

        $cartData = json_decode($order['details'], true, 512, JSON_UNESCAPED_UNICODE);
        Registry::set('session.cart',$cartData);
        $comboData = json_decode($order['comboDetails'], true, 512, JSON_UNESCAPED_UNICODE);
        Registry::set('session.combo',$comboData);

        return $this->json('1');
    }

    public function orderPage($id){
        $user = Registry::get('session.user');
        if(!$user) return $this->redirect('/'.Locale::getLocale().'/');
        $account = Subjects::of('Account')->find($user['id']);
        $order = Subjects::of('Order')->find($id);
        $orderDetails = json_decode($order['details'], true, 512, JSON_UNESCAPED_UNICODE);
        $comboDetails = json_decode($order['comboDetails'], true, 512, JSON_UNESCAPED_UNICODE);
        return $this->html(Template::render('src/cabinet/order',['account'=>$account,'order'=>$order, 'orderDetails' => $orderDetails, 'comboDetails' => $comboDetails, 'halfDetails' => $halfDetails,'orderPage'=>true]));
    }

    public function passchange(){
        $data = Registry::get('http.request.body');
        $user = Registry::get('session.user');

        $old_pass = md5($data['pass_old']);
        $account = Subjects::of('Account')->select(['email'=>$user['email'],'password'=>$old_pass])->first();
        if(!$account) return $this->json(['error'=>'<p style="color:red; font-size:15px;">Неверные данные.</p>','message'=>'']);

        $pass = md5($data['pass_new']);
        $pass_re = md5($data['pass_re']);

        if($pass != $pass_re) return $this->json(['error'=>'<p style="color:red; font-size:15px;">Неверные данные.</p>','message'=>'']);

        $account->offsetSet('password',$pass);
        $account->save();

        return $this->json(['error'=>'','message'=>'<p style="color:green; font-size:15px;">Пароль изменён.</p>']);
    }

    public function logoutget(){
        Registry::set('session.user',[]);
        return $this->html('1');
    }

    public function logout(){
        Registry::set('session.user',[]);
        return $this->html('1');
    }

    public function cabinetPage(){
        $user = Registry::get('session.user');
        if(!$user) return $this->redirect('/'.Locale::getLocale().'/');
        $account = Subjects::of('Account')->find($user['id']);
        $orders = Subjects::of('Order')->select(['userID'=>$user['id']])->get();
        return $this->html(Template::render('src/cabinet/page',['account'=>$account,'orders'=>$orders,'orderPage'=>false]));
    }

    public function save(){
    	$data = Registry::get('http.request.body');
    	$user = Registry::get('session.user');

    	if($data['date']){
    		$dateformatted = str_replace(' ','',$data['date']);
    		$date_parts = explode("/",$dateformatted);
    		$data['date'] = $date_parts[2].'-'.$date_parts[1].'-'.$date_parts[0];
    	}

    	$account = Subjects::of('Account')->find($user['id']);
    	foreach($data as $key => $val){
            $account->offsetSet($key,$val);
        }
        $account->save();

    	return $this->json('1');
    }
}
