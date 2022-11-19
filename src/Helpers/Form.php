<?php namespace Src\Helpers;

use Core\Gateway\Subjects;
use Core\Service\Registry;

class Form
{
    protected $old_values;

    function __construct() {
        $this->old_values = Registry::pull('session.oldvalues');
    }
    public function getInputVal($input_name, $default_value) {
        $result = $default_value;
        if(isset($this->old_values[$input_name])) 
            $result = $this->old_values[$input_name];
        return 'value ="'.$result.'"';
    }
    public function isSelected($select_name, $value, $default_value) {
        if(isset($this->old_values[$select_name]) && $this->old_values[$select_name] == $value) {
            return 'selected';
        } elseif($value == $default_value) {
            return 'selected';
        }
    }
}