<?php namespace Core\Facade;

use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * @method static TemplateRendererInterface getInstance()
 */
class Template extends AbstractFacade
{
    protected static $instance = TemplateRendererInterface::class;

    protected static $defaults = [];

    public static function render($path, $params = [])
    {
        return static::getInstance()->render($path, array_merge(static::$defaults, $params));
    }

    public static function defaults($params = null)
    {
        if (is_array($params)) {
            static::$defaults = array_merge(static::$defaults, $params);
        }

        return static::$defaults;
    }
}