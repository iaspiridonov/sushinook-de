<?php namespace Core\Middleware;

use Core\Facade;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\TextResponse;

class CsrfProtection implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if ($request->getMethod() == 'POST') {
            if ((!key_exists(Facade\Csrf::getName(), $request->getParsedBody()) || !Facade\Csrf::isValid($request->getParsedBody()[Facade\Csrf::getName()]))) {
                return new EmptyResponse(403);//new TextResponse('Csrf error', 403);
            }
        }
    }
}