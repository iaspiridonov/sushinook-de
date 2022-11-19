<?php namespace Core\Middleware\Auth;

use Core\Facade\Template;
use Core\Middleware\CsrfProtection;
use Core\Model\Subject;
use Core\Service\Locale;
use Core\Service\Registry;
use Core\Service\Translation;
use Zend\Diactoros\Response\HtmlResponse;
use Core\Gateway\Subjects;
use Zend\Diactoros\Response\RedirectResponse;
use Core\Service\Validator;

abstract class Controller extends \Core\Middleware\Controller
{
    protected $prefix = 'auth';

    protected $login = 'email';

    protected $password = 'password';

    protected $remember_token = 'remember_token';

    protected static $user;

    public function __construct()
    {
        $this->GET('/', 'loginPage');
        $this->POST('/', 'login');

        if ($this->check()) {

            $this->middleware(new CsrfProtection);

            $this->GET('/logout', 'logout');
            $this->GET('/change-password', 'changePasswordPage');
            $this->POST('/change-password', 'changePassword');
        }
    }

    public function check()
    {
        if ($this->getSession() && ($authSession = Registry::get('session.'.$this->getSession()))) {

            if ($authSession['token'] == static::createToken($_SERVER['HTTP_USER_AGENT'], $authSession['time'], $authSession['id'])) {

                return true;
            }

            Registry::delete('session.'.$this->getSession());

        } elseif (($cookieToken = Registry::get('http.request.cookies.'.$this->getCookie()))) {

            if (($user = $this->getProvider()->select([$this->getRememberToken().' = ?' => $cookieToken])->first())) {

                if ($cookieToken == static::createToken($_SERVER['HTTP_USER_AGENT'], $user['id'])) {

                    $this->authUser($user);

                    return true;
                }
            }

            setcookie($this->getCookie(), 0, time()-3600*24*365, '/');//duncan mclaud dies sometimes
        }

        return false;
    }

    /** @return Subject */
    public function user()
    {
        if (!static::$user) {
            if ($this->check()) {
                static::$user = $this->getProvider()->find(Registry::get('session.'.$this->getSession().'.id'));
            }
        }

        return static::$user;
    }

    public static function createToken(...$params)
    {
        return sha1(join('', $params));
    }

    public function authUser(Subject $user)
    {
        $token = static::createToken($_SERVER['HTTP_USER_AGENT'], time(), $user['id']);

        Registry::set('session.'.$this->getSession(), [
            'id' => $user['id'],
            'root'=>$user['root'],
            'manager'=>$user['manager'],
            'time' => time(),
            'token' => $token
        ]);

        return true;
    }

    protected function loginPage()
    {
        if (!($template = $this->getLoginPageTemplate())) {
            throw new \Exception("Set login_template property");
        }

        return $this->html(Template::render($template, [
            'alert' => Registry::pull('session.alert'),
            'old'   => Registry::pull('session.old'),
           // 'breadcrumbs'=>[''=>Translation::of('Login for tenants')]
        ]));
    }

    protected function login()
    {
        $alert = null;

        if (!(new Validator\NotEmpty())->isValid(($login = Registry::get('http.request.body.'.$this->getLogin()))) || !(new Validator\NotEmpty())->isValid(($password = Registry::get('http.request.body.'.$this->getPassword())))) {
            $alert = "Can't be empty";

        }elseif (!($user = $this->getProvider()->select([$this->getLogin().' = ?' => $login, $this->getPassword().' = ?' => sha1($password)])->first())) {
            $alert = "Wrong login or password";
        }

        if ($alert) {
            Registry::set('session.alert', $alert);
            Registry::set('session.old.'.$this->getLogin(), $login);

            return $this->redirect($_SERVER['REQUEST_URI']);
        }

        $this->authUser($user);

        if (!empty(Registry::get('http.request.body.remember')) && $this->getRememberToken()) {
            $rememberToken = static::createToken($_SERVER['HTTP_USER_AGENT'], $user['id']);
            $user->offsetSet($this->getRememberToken(), $rememberToken);
            $user->save();
            setcookie($this->getCookie(), $rememberToken, time()+3600*24*365*10, '/');
        }

        return $this->redirectAuthorized();
    }

    protected function logout()
    {
        setcookie($this->getCookie(), 0, time()-3600*24*365, '/');
        Registry::delete('session.'.$this->getSession());

        return $this->redirect($this->getLoginPath());
    }

    protected function changePasswordPage()
    {
        if (!($template = $this->getChangePasswordPageTemplate())) {
            throw new \Exception("Set change_password_template property");
        }

        return $this->html(Template::render($template, [
            'user' => $this->user(),
            'errors' => Registry::pull('session.errors'),
            'alert' => Registry::pull('session.alert'),
            'old'   => Registry::pull('session.old')
        ]));
    }

    protected function changePassword()
    {
        $rules = [
            'old_'.$this->getPassword() => [
                new Validator\NotEmpty(Translation::of("Can't be empty", 'cms')),
                new Validator\Lambda(function ($value) {
                    return ($user = $this->user()) && ($user->{$this->getPassword()} == sha1($value));
                }, Translation::of("Wrong password", 'cms'))
            ],

            $this->getPassword() => [
                new Validator\NotEmpty(Translation::of("Can't be empty", 'cms')),
                new Validator\Lambda(function ($value) {
                    return $value !=  Registry::get('http.request.body.old_'.$this->getPassword());
                }, Translation::of("Password is old", 'cms'))
            ],

            'confirm_'.$this->getPassword() => [
                new Validator\NotEmpty(Translation::of("Can't be empty", 'cms')),
                new Validator\Lambda(function ($value) {
                    return $value ==  Registry::get('http.request.body.'.$this->getPassword());
                }, Translation::of("Passwords missmatch", 'cms'))
            ]
        ];

        if (!($validator = new Validator($rules, Registry::get('http.request.body')))->isValid()) {
            Registry::set('session.errors', $validator->getErrors());
            Registry::set('session.old', Registry::get('http.request.body'));

            return $this->redirect($_SERVER['REQUEST_URI']);
        }

        if ($this->user()->offsetSet($this->getPassword(), sha1($validator->getData($this->getPassword())))->save()) {
            Registry::set('session.alert', Translation::of('Password was saved successfully', 'cms'));
        }

        return $this->redirect($_SERVER['REQUEST_URI']);
    }

    protected function redirectAuthorized()
    {
        if (($path = Registry::pull('session.login-back-reference'))) {
            return $this->redirect($path);
        }

        return $this->redirect($this->getHomePath());
    }

    protected function getLoginPageTemplate()
    {
        return property_exists($this, 'login_template') ? $this->login_template : null;
    }

    protected function getChangePasswordPageTemplate()
    {
        return property_exists($this, 'change_password_template') ? $this->change_password_template : null;
    }

    protected function getLoginPath()
    {
        return Locale::getLocalePath().(property_exists($this, 'login_path') ? $this->login_path : null);
    }

    protected function getHomePath()
    {
        return Locale::getLocalePath().(property_exists($this, 'home_path') ?  $this->home_path : null);
    }

    /**
     * @return Subjects
     * @throws \Exception
     */
    protected function getProvider()
    {
        if (!property_exists($this, 'provider_type')) {

            throw new \Exception('Set "provider_type" property');

        } else if (!Registry::get('types')->has($this->provider_type)) {

            throw new \Exception('Type of provider_type "'.$this->provider_type.'" doesnt exists');
        }

        return Subjects::of($this->provider_type);
    }

    /**
     * @return mixed
     */
    protected function getSession()
    {
        return property_exists($this, 'session') ? ($this->prefix ? $this->prefix.'.' : '' ).$this->session : null;
    }

    /**
     * @return mixed
     */
    protected function getCookie()
    {
        return property_exists($this, 'session') ? ($this->prefix ? $this->prefix.'_' : '' ).$this->session : null;
    }

    /**
     * @return mixed
     */
    protected function getLogin()
    {
        return property_exists($this, 'login') ? $this->login : null;
    }

    /**
     * @return string
     */
    protected function getPassword()
    {
        return property_exists($this, 'password') ? $this->password : null;
    }

    /**
     * @return mixed
     */
    protected function getRememberToken()
    {
        return property_exists($this, 'remember_token') ? $this->remember_token : null;
    }
}