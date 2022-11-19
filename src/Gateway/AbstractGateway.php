<?php namespace Src\Gateway;

use Core\Gateway\Subjects;

abstract class AbstractGateway extends Subjects
{
    final public function __construct($where = null)
    {
        parent::__construct((new \ReflectionClass($this))->getShortName());

        if (!is_null($where)) {
            call_user_func([$this, 'select'], $where);
        }

        if (method_exists($this, 'boot')) {
            $this->boot();
        }
    }
}