<?php namespace Core\Middleware;

use Core\Facade\App;
use Core\Facade\Template;
use Core\Service\Registry;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use RuntimeException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Expressive\Router\Route;
use Zend\Expressive\Router\RouterInterface;

abstract class Controller implements ServerMiddlewareInterface
{
    protected $middlewares = [];

    /** @var RouterInterface $router */
    private $router;

    /** @var ServerRequestInterface */
    protected $request;

    /** @var DelegateInterface */
    protected $delegate;

    private function boot($part)
    {
        foreach (get_class_methods($this) as $method) {
            if (strpos($method, $part) === 0) {
                call_user_func([$this, $method]);
            }
        }
    }

    protected function middleware($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->boot('bootProcess');

        foreach($this->middlewares as $middleware) {
            if(($return = $middleware->process($request, $delegate)) !== null) {//if anything returned
                return $return;
            }
        }

        return $this->processController($request, $delegate);
    }

    private function processController(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->boot('bootRouting');

        if (!$this->router || ($result = $this->router->match($request))->isFailure()) {
            return $delegate->process($request);
        }

        $this->request = $request;
        $this->delegate = $delegate;

        Template::defaults([
            'this' => $this
        ]);

        Registry::set('http.controller.object', $this);
        Registry::set('http.request.attributes', $result->getMatchedParams());

        $this->boot('bootDispatch');
        return call_user_func($result->getMatchedMiddleware());
    }

    protected function path($route = null)
    {
        return Registry::get('http.controller.path').$route;
    }

    protected function response($status, $headers = [])
    {
        return new EmptyResponse($status, $headers);
    }

    protected function redirect($url, $status = 302, array $headers = [])
    {
        return new RedirectResponse($url, $status, $headers);
    }

    protected function html($html, $status = 200, array $headers = [])
    {
        return new HtmlResponse($html, $status, $headers);
    }

    protected function text($text, $status = 200, array $headers = [])
    {
        return new TextResponse($text, $status, $headers);
    }

    protected function json($text, $status = 200, array $headers = [])
    {
        return $this->text(json_encode($text), $status, $headers);
    }

    protected function GET($route, $callback)
    {
        return $this->addRoute($route, 'GET', $callback);
    }

    protected function POST($route, $callback)
    {
        return $this->addRoute($route, 'POST', $callback);
    }

    private function addRoute($route, $method, $callback)
    {
        if (is_callable($callback)) {

            $middleware = function () use ($callback) {
                return call_user_func_array($callback, Registry::pull('http.request.attributes'));
            };

        } elseif (is_string($callback)) {

            if (!method_exists($this, $callback)) {
                throw new RuntimeException(
                    'No method '.get_class($this).'::'.$method.'() for route "'.$route.'"'
                );
            }

            $middleware = function () use ($callback) {
                return call_user_func_array([$this, $callback], Registry::pull('http.request.attributes'));
            };

        } else {
            throw new RuntimeException(
                '$callback shoud be callable or string type'
            );
        }

        if (!$this->router) {
            $this->router = App::getInstance()->getContainer()->build(RouterInterface::class);
        }

        $this->router->addRoute(new Route($route, $middleware, [$method]));
    }
}
