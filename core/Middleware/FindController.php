<?php namespace Core\Middleware;

use Core\Facade\App;
use Core\Service\Locale;
use Core\Service\Registry;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class FindController implements MiddlewareInterface
{
    protected static $controllerClassPath = 'Src\Controller\\';

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $controllerClassPath = static::$controllerClassPath;
        $controllerClassName = 'Index';
        $controllerPath = '/';

        if (($path = $request->getUri()->getPath()) != '/' && preg_match_all('#(?P<routes>\w[\w\d\-]+)#u', $path, $matched) && ($routes = $matched['routes'])) {

            $path = [];
            $found = [];

            while (($route = current($routes)) && class_exists(static::$controllerClassPath.($found ? join('\\', $found).'\\' : '').($className = $this->controllerClassName($route)))) {

                $path[]= $route;
                $found[]= $className;
                next($routes);
            }

            if ($found) {
                $controllerClassName = array_pop($found);
                $controllerClassPath .= $found ? join('\\', $found).'\\' : '';
                $controllerPath = '/'.join('/', $path);
            }

        }

        $controllerClass = $controllerClassPath.$controllerClassName;

        if ($controllerPath != '/') {
            $request = App::stripRouteFromPath($request, $controllerPath);
        }

        Registry::set('http.request.method', $request->getMethod());
        Registry::set('http.request.path', $request->getUri()->getPath());
        Registry::set('http.request.headers', $request->getHeaders());
        Registry::set('http.request.cookies', $request->getCookieParams());
        Registry::set('http.request.query', $request->getQueryParams());
        Registry::set('http.request.body', $request->getParsedBody());
        Registry::set('http.request.files', $request->getUploadedFiles());
        Registry::set('http.request.server', $request->getServerParams());

        Registry::set('http.controller.path', Locale::getLocalePath().$controllerPath);
        Registry::set('http.controller.class', $controllerClass);

        return (new $controllerClass)->process($request, $delegate);
    }

    private function controllerClassName($name)
    {
        return str_replace('-', '', ucwords($name, '-'));
    }
}