<?php namespace Core\Service\Validator;

abstract class Rule
{
    protected $error;

    public function __construct($error = null)
    {
        $this->error = $error;
    }

    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    public function getError()
    {
        return $this->error;
    }

    abstract public function isValid($value);
}