<?php
/**
 * @file    framework/CMS/UI/Base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\CMS\UI;

use \html;

class Base extends \depage_ui
{
    protected $htmlOptions = array();
    protected $basetitle = "";
    protected $autoEnforceAuth = true;

    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        if (empty($this->pdo)) {
            // get database instance
            $this->pdo = new \depage\DB\PDO (
                $this->options->db->dsn, // dsn
                $this->options->db->user, // user
                $this->options->db->password, // password
                array(
                    'prefix' => $this->options->db->prefix, // database prefix
                )
            );
        }

        // get auth object
        $this->auth = \depage\Auth\Auth::factory(
            $this->pdo, // db_pdo
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        );

        // set html-options
        $this->htmlOptions = array(
            'template_path' => __DIR__ . "/../tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        );
        $this->basetitle = \depage::getName() . " " . \depage::getVersion();

        // establish if the user is logged in
        if (empty($this->authUser)) {
            if ($this->autoEnforceAuth) {
                $this->authUser = $this->auth->enforce();
            } else {
                $this->authUser = $this->auth->enforceLazy();
            }
        }
    }
    // }}}
    // {{{ _package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function _package($output) {
        // pack into base-html if output is html-object
        if (!isset($_REQUEST['ajax']) && is_object($output) && is_a($output, "html")) {
            // pack into body html
            $output = new html("html.tpl", array(
                'title' => $this->basetitle,
                'subtitle' => $output->title,
                'content' => $output,
            ), $this->htmlOptions);
        }

        return $output;
    }
    // }}}

    // {{{ toolbar
    protected function toolbar() {
        if ($user = $this->auth->enforceLazy()) {
            $h = new html("toolbar_main.tpl", array(
                'title' => $this->basetitle,
                'username' => $user->name,
            ), $this->htmlOptions);
        } else {
            $h = new html("toolbar_plain.tpl", array(
                'title' => $this->basetitle,
            ), $this->htmlOptions);
        }

        return $h;
    }
    // }}}

    // {{{ notfound
    /**
     * function to call if action/function is not defined
     *
     * @return  null
     */
    public function notfound($function = "") {
        parent::notfound();

        $h = new html("box.tpl", array(
            'id' => "error",
            'class' => "first",
            'title' => "Error",
            'content' => new html(array(
                'content' => 'url not found' . $function,
            )),
        ), $this->htmlOptions);

        return $h;
    }
    // }}}
    // {{{ error
    /**
     * function to show error messages
     *
     * @return  null
     */
    public function error($error, $env) {
        $content = parent::error($error, $env);

        $h = new html("box.tpl", array(
            'id' => "error",
            'class' => "first",
            'content' => new html(array(
                'content' => $content,
            )),
        ), $this->htmlOptions);

        return $this->_package($h);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */