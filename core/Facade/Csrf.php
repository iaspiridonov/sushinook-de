<?php namespace Core\Facade;

/**
 * @method static \Zend\Validator\Csrf getInstance()
 */
class Csrf extends AbstractFacade
{
    protected static $instance = \Zend\Validator\Csrf::class;

    protected static $name = '_csrf_token';

    public static function setName($name)
    {
        static::$name = $name;
    }

    public static function getName()
    {
        return static::$name;
    }

    public static function token()
    {
        return static::getInstance()->getHash();
    }

    public static function isValid($token)
    {
        return static::getInstance()->isValid($token);
    }

    public static function hiddenInput()
    {
        return '<input type="hidden" name="'.static::getName().'" value="'.static::token().'">';
    }

    public function __toString()
    {
        return static::hiddenInput();
    }
}