<?php namespace Core\Facade;

use Core\Middleware\FindController;
use Core\Service\Locale;
use Core\Service\Registry;
use Core\Gateway\Types;
use Core\Middleware\CsrfProtection;
use Core\Service\Translation;
use Interop\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Application;
use Zend\Stratigility\Middleware\ErrorHandler;
use Zend\Diactoros\Response\HtmlResponse;
use Src\Controller\Index;
/**
 * @method static Application getInstance()
 */
class App extends AbstractFacade
{
    public static function init(ContainerInterface $container)
    {
        define('ROOT_PATH', realpath(__DIR__ . '/../../'));
        define('PUBLIC_PATH', ROOT_PATH.'/public');
        define('SRC_PATH', ROOT_PATH.'/src');
        define('DATA_PATH', ROOT_PATH.'/data');

        static::$instance = $container->get(\Zend\Expressive\Application::class);

        Locale::setLocales($container->get('config')['locales']);
        Locale::setLocale(current(Locale::getLocales()));

        Registry::set('types', (new Types)->get());

        Template::defaults([
            'Registry' => new Registry,
            'Locale' => new Locale,
            'Translation' => new Translation,
            'Csrf' => new Csrf
        ]);
    }

    public static function initSession()
    {
        Session::getInstance()->start();
        Registry::setReference('session', $_SESSION);
    }

    public static function initPipeline()
    {
        static::getInstance()->pipe(ErrorHandler::class);
        //static::getInstance()->pipe(CsrfProtection::class); // we shouldn't protect entire system
        static::getInstance()->pipe(Locale::class);
        static::getInstance()->pipe(FindController::class);

        static::getInstance()->pipe(function () use ($seo) {
            $abs = new Index;
            $abs->bootDispatchTemplateDefaults();
            return new HtmlResponse(Template::render('src/404'), 404);
        });
    }

    public static function stripRouteFromPath(ServerRequestInterface $request, $route)
    {
        return $request->withUri($request->getUri()->withPath($request->getUri()->getPath() == $route ? '/' : substr($request->getUri()->getPath(), strlen($route))));
    }
}