<?php
/**
 * @file    validators/closure.php
 * @brief   closure validator
 **/
namespace depage\htmlform\validators;

/**
 * @brief customizable validator for input elements
 **/
class closure extends validator
{
    // {{{ variables
    /**
     * @brief function to call
     **/
    protected $validatorFunction;
    // }}}

    // {{{ validate()
    /**
     * @brief   validates value with a callable function/closure
     *
     * @param  string $value      value to be validated
     * @param  array  $parameters validation parameters
     * @return bool   validation result
     **/
    public function validate($value, $parameters = array())
    {
        return call_user_func($this->validatorFunction, $value, $parameters);
    }
    // }}}

    // {{{ setClosure()
    /**
     * @brief   sets the validators validator function
     *
     * @param  closure $validatorFunction function
     * @return void
     **/
    public function setFunc($validatorFunction)
    {
        $this->validatorFunction = $validatorFunction;
    }
    // }}}
}
