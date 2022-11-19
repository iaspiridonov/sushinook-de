<?php namespace Core\Middleware;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;

class LambdaMiddleware implements MiddlewareInterface
{
    protected $callback;

    public function __construct(Callable $callback)
    {
        $this->callback = $callback;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        call_user_func_array($this->callback, [$request, $delegate]);
    }
}