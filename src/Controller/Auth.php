<?php namespace Src\Controller;


use Core\Facade\Template;
use Core\Gateway\Subjects;
use Core\Service\Registry;
use Zend\Db\Sql\Select;
use Src\Controller\Cabinet;
use Core\Service\Translation;
use Core\Service\Locale;
use Src\Model\Account;
use Core\Service\Mail;

class Auth extends AbstractController
{
    public function bootRouting()
    {
        $this->GET('/', 'loginPage');
        $this->POST('/login', 'login');
        $this->POST('/register', 'register');
    }

    public function loginPage() {
    	if($this->auth->check())
    		return $this->redirect('/'.Locale::getLocale().'/cabinet/');

    	$errors = Registry::pull('session.errors');
        return $this->html(Template::render('/cabinet/auth/login', ['errors'=>$errors]));
    }

    public function login() {
        $data = Registry::get('http.request.body');

        $email = $data['phone'];
        $pass = md5($data['password']);

        $account = Subjects::of('Account')->select(['phone'=>$email,'password'=>$pass])->first();

        if(empty($account))
            return $this->json(['error'=>'<p style="color:red; font-size:15px;">Неверные данные.</p>','message'=>'']);

        $user = [
            'id' => $account['id'],
            'name' => $account['name'],
            'email' => $account['email']
        ];
        Registry::set('session.user',$user);
    	return $this->json(['error'=>'','redirect'=>'/'.Locale::getLocale().'/cabinet/']);
    }

    public function register() {
        $data = Registry::get('http.request.body');
        
        if($data['password'] != $data['password_confirm'])
            return $this->json(['error'=>'<p style="color:red; font-size:15px;">Пароли не совпадают.</p>','message'=>'']);

        $isset = Subjects::of('Account')->select(['phone'=>$data['phone']])->first();
        if($isset)
            return $this->json(['error'=>'<p style="color:red; font-size:15px;">Пользователь с указанным телефоном уже существует.</p>','message'=>'']);

        $pass = md5($data['password']);

        ($user = new Account([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $pass
        ]))->save();

        unset($data['password']);
        unset($data['password_confirm']);

        $emails = Subjects::of('Variable')->find(1);
        $emails = explode(",", $emails->value);
        if(count($emails)) {
            foreach($emails as $email) {
                $mail = (new Mail)->subject('Новый пользователь')->body(Template::render('src/mail/user', [
                    'data' => $data
                ]))->send($email);
            }
        }

        $user = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ];

        Registry::set('session.user',$user);

        return $this->json(['error'=>'','message'=>'<h2 style="color:green;">Вы успешно зарегистрировались</h2>']);
    }

}