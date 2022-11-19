<?php namespace Core\Facade;

use Zend\Session\SessionManager;

/**
 * @method static SessionManager getInstance()
 */
class Session extends AbstractFacade
{
    protected static $instance = SessionManager::class;
}