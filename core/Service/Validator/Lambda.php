<?php namespace Core\Service\Validator;

class Lambda extends Rule
{
    protected $rule;

    public function __construct(callable $rule, $error = null)
    {
        $this->rule = $rule;
        parent::__construct($error);
    }

    public function isValid($value)
    {
        return call_user_func($this->rule, $value);
    }
}