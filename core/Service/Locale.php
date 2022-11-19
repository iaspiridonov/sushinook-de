<?php namespace Core\Service;

use Core\Facade\App;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;

class Locale implements MiddlewareInterface
{
    protected static $locales = [];
    protected static $locale;
    protected static $default;

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if (($locale = substr($request->getUri()->getPath(), 1, 2)) && in_array($locale, static::getLocales()) && in_array(substr($request->getUri()->getPath(), 3, 1), ['/', false, ''])) {
            static::setLocale($locale);
            $request = App::stripRouteFromPath($request, '/'.$locale);
        } elseif (($localePath = static::getLocalePath())) {// to prevent doubled-content for google
            return new RedirectResponse($localePath.$request->getUri()->getPath());
        }

        return $delegate->process($request);
    }

    /**
     * @param string $locale
     * @throws \Exception
     */
    public static function setLocales(array $locales)
    {
        self::$locales = $locales;
        self::$default = current($locales);
    }

    /**
     * @return array
     */
    public static function getLocales()
    {
        return self::$locales;
    }

    /**
     * @return string
     */
    public static function getDefaultLocale()
    {
        return self::$default;
    }

    /**
     * @param string $locale
     * @throws \Exception
     */
    public static function setLocale($locale)
    {
        if (!in_array($locale, self::$locales)) {
            throw new \Exception('There is no "'.$locale.'" language at config.');
        }

        self::$locale = $locale;
    }

    /**
     * @return string
     */
    public static function getLocale()
    {
        return self::$locale;
    }

    /**
     * @return string
     */
    public static function getLocalePath()
    {
        return count(self::$locales)>1 ? '/'.self::$locale : '';
    }

    public function __toString()
    {
        return static::getLocalePath();
    }
}