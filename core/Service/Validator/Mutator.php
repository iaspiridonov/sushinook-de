<?php namespace Core\Service\Validator;


class Mutator
{
    protected $mutator;

    public function __construct(callable $mutator)
    {
        $this->mutator = $mutator;
    }

    public function mutate(&$value)
    {
        $value = call_user_func($this->mutator, $value);
    }
}