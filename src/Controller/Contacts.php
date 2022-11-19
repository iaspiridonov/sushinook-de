<?php namespace Src\Controller;

use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Service\Mail;
use Core\Service\Registry;
use Zend\Db\Sql\Select;
use Src\Model\Order;

class Contacts extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/', 'page');
        $this->POST('/send','send');
    }

    function captcha($response){
        if(empty($response)) return false;
        $secret = '6Lf7WL0ZAAAAAAkHHrzLGK4s75gd1_Uc5Z-C4MAj';
        $captcha_res = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret.'&response='.$response),true);
        if($captcha_res['success']) return true;
        return false;
    }

    public function send(){
        $request = Registry::get('http.request.body');

        if($this->captcha($request['g-recaptcha-response'])){
            unset($request['g-recaptcha-response']);
            $emails = Subjects::of('Variable')->find(1);
            $emails = explode(",", $emails->value);
            if(count($emails)) {
                foreach($emails as $email) {
                    $mail = (new Mail)->subject('Сообщение с формы обратной связи')->body(Template::render('src/mail/request', [
                        'message' => $request
                    ]))->send($email);
                }
            }
            return $this->json(['error'=>'']);
        }else{
            return $this->json(['error'=>'<p style="color:red;">Подтвердите что вы не робот</p>']);
        }
    }

    public function page()
    {
        $branch = Subjects::of('Branch')->select(function($select){
            $select->order('sort');
        })->get();
        $seo = Subjects::of('Seo')->find(15);
        return $this->html(Template::render('src/contacts/index', [
            'branch' => $branch,
            'seo' => $seo
        ]));
    }
}
