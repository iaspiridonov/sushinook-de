<?php
/**
 * Created by PhpStorm.
 * User: Time
 * Date: 15.08.17
 * Time: 15:41
 */

namespace Core\Service;


use Core\Service\Validator\Mutator;
use Core\Service\Validator\Rule;

class Validator
{
    protected $rules = [];
    protected $values = [];
    protected $errors = [];

    public function __construct(array $rules, array $values)
    {
        $this->setRules($rules)->setValues($values);
    }

    public function setRules(array $rules)
    {
        $this->rules = $rules;
        return $this;
    }

    public function addRule($key, $value)
    {
        $this->rules[$key] = $value;
        return $this;
    }

    public function setValues(array $values)
    {
        $this->values = $values;
        return $this;
    }

    public function addValue($key, $value)
    {
        $this->values[$key] = $value;
        return $this;
    }

    public function isValid()
    {
        foreach ($this->rules as $key => $rules) {
            $value = &$this->values[$key] ?? null;

            if (is_array($rules)) {

                foreach ($rules as $rule) {/** @var Rule $rule */

                    if (!$this->process($key, $rule, $value)) {
                        break;
                    }
                }
            } else {/** @var Rule $rules */
                $this->process($key, $rules, $value);
            }
        }

        if ($this->errors) {
            return false;
        }

        return true;
    }

    protected function process($key, $rule, &$value)
    {
        if ($rule instanceof Rule) {/** @var Rule $rule */
            if(!$rule->isValid($value)) {
                $this->errors[$key] = $rule->getError();
                return false;
            }
        } else if ($rule instanceof Mutator) {/** @var Mutator $rule */
            $rule->mutate($value);
        } else {
            throw new \Exception('Rule must be instance of Validator\Rule or Validator\Mutator');
        }

        return true;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getData($name = null)
    {
        $rules = array_keys($this->rules);

        $result = array_filter($this->values, function ($key) use ($rules) {
            return in_array($key, $rules);
        }, ARRAY_FILTER_USE_KEY);

        return $name ? ($result[$name] ?? null) : $result;
    }
}