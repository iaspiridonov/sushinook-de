<?php namespace Src\Model;

use Core\Model\Subject;

abstract class AbstractModel extends Subject
{
    final public function __construct($data = null)
    {
        parent::__construct((new \ReflectionClass($this))->getShortName());

        if (!is_null($data)) {
            call_user_func([$this, 'populate'], $data);
        }

        if (method_exists($this, 'boot')) {
            $this->boot();
        }
    }
}