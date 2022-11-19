<?php

use Zend\ConfigAggregator\ConfigAggregator;
use Zend\Expressive\Application;
use Zend\Expressive\Container;
use Zend\Expressive\Middleware;
use Zend\Expressive\Router\FastRouteRouter;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Expressive\Twig\TwigEnvironmentFactory;
use Zend\Expressive\Twig\TwigRendererFactory;

define('DBHOST','localhost');
define('DBNAME','zend');
define('DBUSER','wordpress');
define('DBPASS','bbd19dd1e8c075bdc85abb78ecbdb3caf97d567280218c12');

return [

    'locales' => ['ru', 'en', 'kz'], // first is default , 'en', 'kz'

    'zend-expressive' => [
        'error_handler' => [
            'template_error' => 'error.html.twig'
        ]
    ],

    'session_config' => [
        'name' => 'SID',
        'cookie_httponly' => true,
        'cookie_path' => '/',
        'cookie_secure' => false,
        'use_cookies' => true,
        'cookie_lifetime' => 36000,
        //'save_path' => '/temp/data/session',
    ],
    'session_storage' => [
        'type' => \Zend\Session\Storage\SessionArrayStorage::class
    ],

    ConfigAggregator::ENABLE_CACHE => true,

    'debug' => false,

    'dependencies' => [

        'invokables' => [
            RouterInterface::class => FastRouteRouter::class,
            Zend\Validator\Csrf::class => Zend\Validator\Csrf::class
        ],

        'factories'  => [
            Application::class => Container\ApplicationFactory::class,

            Zend\Stratigility\Middleware\ErrorHandler::class => Container\ErrorHandlerFactory::class,
            Middleware\ErrorResponseGenerator::class         => Container\ErrorResponseGeneratorFactory::class,

            Twig_Environment::class => TwigEnvironmentFactory::class,
            TemplateRendererInterface::class => TwigRendererFactory::class,

            Zend\Db\Adapter\Adapter::class => \Zend\Db\Adapter\AdapterServiceFactory::class
        ],
    ],

    'templates' => [
        'extension' => 'html.twig',
        'paths' => [__DIR__ . '/../../src/View/']
    ],

    'twig' => [
        'cache_dir'      => 'data/cache/twig',
    ],

    'db' => [
        'driver'         => 'Pdo',
        'dsn'            => 'mysql:dbname='.DBNAME.';host='.DBHOST,
        'driver_options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
        ],
        'username' => DBUSER,
        'password' => DBPASS
    ],
];
