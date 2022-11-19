<?php namespace Core\Middleware;

use Core\Middleware\Auth\Controller as AuthController;
use Core\Service\Registry;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class Auth extends AuthController
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($this->check()) {

            if ($this->isAuthController()) {

                if (Registry::get('http.request.path') == '/') {

                    return $this->redirectAuthorized();
                }

            } else {//we are middleware auth service, everything is ok, return null

                return null;
            }

        } elseif (!$this->isAuthController()) {//we are middleware auth service, everything is BAD, return redirect here

            Registry::set('session.login-back-reference', $_SERVER['REQUEST_URI']);

            return $this->redirect($this->getLoginPath());
        }

        return parent::process($request, $delegate);
    }

    protected function isAuthController()
    {
        return (Registry::get('http.controller.class') == get_class($this));
    }
}