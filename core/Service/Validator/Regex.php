<?php namespace Core\Service\Validator;

class Regex extends Rule
{
    protected $pattern;

    public function __construct($pattern, $error = null)
    {
        $this->pattern = $pattern;
        parent::__construct($error);
    }

    public function isValid($value)
    {
        return preg_match($this->pattern, $value);
    }
}