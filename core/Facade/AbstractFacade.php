<?php namespace Core\Facade;

abstract class AbstractFacade
{
    protected static $instance = null;

    public static function getInstance()
    {
        if (is_string(static::$instance)) {
            static::$instance = App::getInstance()->getContainer()->get(static::$instance) ?: null;
        }

        if (is_null(static::$instance)) {
            throw new \Exception('Facade instance is null');
        }

        return static::$instance;
    }

    /*static public function __callStatic($method, $arguments){

        if(method_exists(static::getInstance(), $method)){
            return call_user_func_array([static::getInstance(), $method], $arguments);
        }

        throw new \Exception('No facade method found '. $method);
    }*/
}