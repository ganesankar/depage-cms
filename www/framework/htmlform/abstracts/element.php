<?php

/**
 * The abstract element class contains the basic attributes of container and input
 * elements.
 **/

namespace depage\htmlform\abstracts;

use depage\htmlform\exceptions;

abstract class element {
    /**
     * Element name.
     **/
    protected $name;
    /**
     * Contains element validation status/result.
     **/
    protected $valid;
    /**
     * True if the element has been validated before.
     **/
    protected $validated = false;
    /**
     * Log object reference
     **/
    protected $log;

    public function __construct($name, $parameters, $form) {
        $this->checkName($name);
        $this->checkParameters($parameters);

        $this->name = $name;

        $this->setDefaults();
        $parameters = array_change_key_case($parameters);
        foreach ($this->defaults as $parameter => $default) {
            $this->$parameter = isset($parameters[strtolower($parameter)]) ? $parameters[strtolower($parameter)] : $default;
        }
    }

    /**
     * collects initial values across subclasses.
     **/
    protected function setDefaults() {
        $this->defaults['log'] = null;
    }

    /**
     * Returns respective HTML escaped attributes for element rendering.
     **/
    public function __call($functionName, $functionArguments) {
        if (substr($functionName, 0, 4) === 'html') {
            $attribute = str_replace('html', '', $functionName);
            $attribute{0} = strtolower($attribute{0});

            return $this->htmlEscape($this->$attribute);
        } else {
            trigger_error("Call to undefined method $functionName", E_USER_ERROR);
        }
    }

    /**
     * Returns the element name.
     *
     * @return $this->name
     **/
    public function getName() {
        return $this->name;
    }
    /**
     * Throws an exception if $parameters isn't of type array.
     *
     * @param $parameters parameters for input element constructor
     * @return void
     **/
    protected function checkParameters($parameters) {
        if ((isset($parameters)) && (!is_array($parameters))) {
            throw new exceptions\elementParametersNoArrayException('Element "' . $this->getName() . '": parameters must be of type array.');
        }
    }

    /**
     * Checks that element name is of type string, not empty and doesn't
     * contain invalid characters. Otherwise throws an exception.
     *
     * @param   $name (mixed) element name
     * @return  void
     **/
    private function checkName($name) {
        if (
            !is_string($name)
            || trim($name) === ''
            || preg_match('/[^a-zA-Z0-9_\-]/', $name)
        )  {
            throw new exceptions\invalidElementNameException('"' . $name . '" is not a valid element name.');
        }
    }

    protected function log($argument, $type = null) {
        if (is_callable(array($this->log, 'log'))) {
            $this->log->log($argument, $type);
        } else {
            error_log($argument);
        }
    }

    /**
     * Escapes HTML in strings and arrays of strings
     *
     * @param   $options        (mixed) value to be HTML escaped
     * @return  $htmlOptions    (mixed) HTML escaped value
     **/
    protected function htmlEscape($options = array()) {
        if (is_string($options)) {
            $htmlOptions = htmlspecialchars($options);
        } elseif (is_array($options)) {
            $htmlOptions = array();

            foreach($options as $index => $option) {
                if (is_string($index))  $index  = htmlspecialchars($index, ENT_QUOTES);
                if (is_string($option)) $option = htmlspecialchars($option, ENT_QUOTES);

                $htmlOptions[$index] = $option;
            }
        } else {
            $htmlOptions = $options;
        }
        return $htmlOptions;
    }
}
