<?php
/**
 * @file    framework/db/db_pdo.php
 *
 * depage database module
 *
 *
 * copyright (c) 2002-2010 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

class db_pdo extends PDO {
    /* {{{ variables*/
    public $prefix;
    /* }}} */

    /* {{{ constructor */
    /**
     * constructor for PDO object with an additional prefix-parameter in driver-options
     *
     * @param   string  dsn                 dsn for pdo-object
     * @param   string  username            username for database
     * @param   string  password            password for database
     * @param   array   $driver_options     database-driver options with additional prefix-entry
     *
     * @return  void
     */
    public function __construct($dsn, $username = '', $password = '', $driver_options = array()) {
        if (isset($driver_options['prefix'])) {
            $this->prefix = $driver_options['prefix'];
            unset($driver_options['prefix']);
        }

        parent::__construct($dsn, $username, $password, $driver_options);
    }
    /* }}} */
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */