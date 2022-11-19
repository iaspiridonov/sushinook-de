<?php namespace Core\Service\Validator;

class Email extends Rule
{
    public function isValid($value)
    {
        return preg_match('/^[^@]+@[^@]+\.\w+$/ui', $value);
    }
}