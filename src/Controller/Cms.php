<?php namespace Src\Controller;

class Cms extends \Core\Middleware\Auth
{
    protected $provider_type = 'User';

    protected $session = 'cms';

    protected $login_template = 'cms/auth/login';

    protected $change_password_template = 'cms/auth/change-password';

    protected $login_path = '/cms';

    protected $home_path = '/cms/settings';
}
