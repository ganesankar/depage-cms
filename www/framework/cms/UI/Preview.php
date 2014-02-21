<?php
/**
 * @file    framework/cms/UI/Preview.php
 *
 * preview ui handler
 *
 *
 * copyright (c) 2013-2014 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\cms\UI;

class Preview extends \depage_ui {
    protected $html_options = array();
    protected $basetitle = "";
    protected $cached = false;
    protected $projectName = "";
    protected $template = "";
    protected $lang = "";
    protected $urlsByPageId = array();
    protected $pageIdByUrl = array();
    public $routeThroughIndex = true;

    // {{{ _init
    public function _init(array $importVariables = array()) {
        parent::_init($importVariables);

        if (empty($this->pdo)) {
            // get database instance
            $this->pdo = new \db_pdo (
                $this->options->db->dsn, // dsn
                $this->options->db->user, // user
                $this->options->db->password, // password
                array(
                    'prefix' => $this->options->db->prefix, // database prefix
                )
            );
        }

        // get auth object
        $this->auth = \auth::factory(
            $this->pdo, // db_pdo 
            $this->options->auth->realm, // auth realm
            DEPAGE_BASE, // domain
            $this->options->auth->method // method
        );

        // set html-options
        $this->html_options = array(
            'template_path' => __DIR__ . "/../tpl/",
            'clean' => "space",
            'env' => $this->options->env,
        );
        $this->basetitle = \depage::getName() . " " . \depage::getVersion();
    }
    // }}}
    // {{{ _package
    /**
     * gets a list of projects
     *
     * @return  null
     */
    public function _package($output) {
        return $output;
    }
    // }}}
    // {{{ _send_time
    protected function _send_time($time, $content = null) {
        echo("<!-- $time sec -->");
    }
    // }}}
    
    // {{{ index
    /**
     * function to route all previews through
     *
     * @return  null
     */
    public function index()
    {
        $args = func_get_args();

        // get parameters 
        $this->projectName = $this->urlSubArgs[0];
        $this->template = array_shift($args);
        $this->cached = array_shift($args) == "cached" ? true : false;
        $this->lang = array_shift($args);

        $urlPath = implode("/", $args);

        // set basic variables
        $this->prefix = $this->pdo->prefix . "_proj_" . $this->projectName;

        $this->xsltPath = "projects/" . $this->projectName . "/xslt/";
        $this->xmlPath = "projects/" . $this->projectName . "/xml/";

        // get cache instance
        $this->cache = \depage\cache\cache::factory("xmldb");

        // create xmldb-project
        $this->xmldb = new \depage\xmldb\xmldb ($this->prefix, $this->pdo, $this->cache, array(
            'pathXMLtemplate' => $this->xmlPath,
            //'userId' => $this->auth_user->id,
        ));

        return $this->preview($urlPath);
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

        $h = new \html("box.tpl", array(
            'id' => "error",
            'class' => "first",
            'content' => new \html(array(
                'content' => $content,
            )),
        ), $this->html_options);

        return $this->_package($h);
    }
    // }}}
    
    // {{{ preview
    /**
     * @return  null
     */
    protected function preview($urlPath)
    {
        $urlPath = "/$urlPath";
        $this->currentPath = $urlPath;
        list($pageId, $pagedataId) = $this->getPageIdFor($urlPath);
        $xslDOM = $this->getXsltFor($this->template);

        $pageXml = $this->xmldb->getDocXml($pagedataId);
        
        libxml_disable_entity_loader(false);
        libxml_use_internal_errors(true);

        $xslt = new \XSLTProcessor();

        $this->registerStreams($xslt);
        $this->registerFunctions($xslt);

        $xslt->setParameter("", array(
            "currentLang" => $this->lang,
            "currentPageId" => $pageId,
            "depageVersion" => \depage::getVersion(),
        ));
        $xslt->setProfiling('logs/xslt-profiling.txt');
        $xslt->importStylesheet($xslDOM);

        if ($pageXml === false) {   
            throw new \exception("no page data");
        } elseif (!$html = $xslt->transformToXml($pageXml)) {   
            $errors = libxml_get_errors();
            foreach($errors as $error) {
                $this->log->log($error);
                var_dump($error);
            }
            
            $error = libxml_get_last_error();
            $error = empty($error) ? 'Could not transform the navigation XML document.' : $error->message;
            
            throw new \exception($error);
        }

        return $html;
    }
    // }}}
    // {{{ registerStreams
    /**
     * @return  null
     */
    protected function registerStreams($proc)
    {
        /*
         * @todo
         * get:css -> replace with transforming css directly
         * get:redirect -> analogous to css
         * get:atom -> analogous to css
         *
         * @todo dp:functions ?
         * call:fileinfo -> replaced with call://fileinfo
         *
         * @done but @thinkabout
         * get:page -> replaced with dp:getpage function -> better replace manualy in template
         *
         * @done
         * pageref:
         * libref:
         * get:xslt -> replaced with xslt://
         * get:template -> deleted
         * get:navigation -> replaced with $navigation
         * get:colors -> replaced with $colors
         * get:settings -> replaced with $settings
         * get:languages -> replaced with $languages
         * call:doctype -> not necessary anymore
         * call:getversion -> replaced by $depageVersion
         */
        // register stream to get documents from xmldb
        \depage\cms\Streams\Xmldb::registerStream("xmldb", array(
            "xmldb" => $this->xmldb,
            "currentPath" => $this->currentPath,
        ));
        
        // register stream to get global xsl templates
        \depage\cms\Streams\Xslt::registerStream("xslt");

        // register stream to get page-links
        \depage\cms\Streams\Pageref::registerStream("pageref", array(
            "urls" => $this->urlsByPageId,
            "preview" => $this,
            "lang" => $this->lang,
        ));

        // register stream to get links to library
        \depage\cms\Streams\Libref::registerStream("libref", array(
            "preview" => $this,
        ));

        // register stream for various php calls
        \depage\cms\Streams\Call::registerStream("call", array(
            "preview" => $this,
        ));
    }
    // }}}
    // {{{ registerFunctions
    /**
     * @return  null
     */
    protected function registerFunctions($proc)
    {
        /*
         * @todo
         * call:changesrc
         * call:atomizetext
         * call:urlencode
         * call:phpescape
         * call:formatdate
         * call:replaceEmailChars
         *
         * @todo dp:functions ?
         * call:fileinfo -> replaced with call://fileinfo
         */

        \depage\cms\xslt\FuncDelegate::registerFunctions($proc, array(
            "changesrc" => array($this, "xsltCallChangeSrc"),
        ));
    }
    // }}}
    
    // {{{ getXslFor
    /**
     * @return  null
     */
    protected function getXsltFor($template)
    {
        $files = glob("{$this->xsltPath}{$template}/*.xsl");

        $xslt = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<xsl:stylesheet xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\"  xmlns:dp=\"http://cms.depagecms.net/ns/depage\" xmlns:db=\"http://cms.depagecms.net/ns/database\" xmlns:proj=\"http://cms.depagecms.net/ns/project\" xmlns:pg=\"http://cms.depagecms.net/ns/page\" xmlns:sec=\"http://cms.depagecms.net/ns/section\" xmlns:edit=\"http://cms.depagecms.net/ns/edit\" version=\"1.0\" extension-element-prefixes=\"xsl db proj pg sec edit \">";

        $xslt .= "<xsl:include href=\"xslt://functions.xsl\" />";

        // add basic paramaters and variables
        $params = array(
            'currentLang' => null,
            'currentPageId' => null,
            'depageIsLive' => "'false'",
            // @todo complete baseurl this in a better way
            'baseurl' => "'" . DEPAGE_BASE . 'project/' . $this->projectName . "/preview/" . $this->template . "/noncached/" . "'",
        );
        $variables = array(
            'navigation' => "document('xmldb://pages')",
            'settings' => "document('xmldb://settings')",
            'colors' => "document('xmldb://colors')",
            'languages' => "\$settings//proj:languages",
            'currentPage' => "\$navigation//pg:page[@status = 'active']",
            'currentColorscheme' => "dp:choose(//pg:meta[1]/@colorscheme, //pg:meta[1]/@colorscheme, \$colors//proj:colorscheme[@name]/@name)",
        );
        
        // add variables from settings
        $settings = $this->xmldb->getDocXml("settings");

        $xpath = new \DOMXPath($settings);
        $nodelist = $xpath->query("//proj:variable");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $variables["var-" . $node->getAttribute("name")] = "'" . htmlspecialchars($node->getAttribute("value")) . "'";
        }

        // now add to xslt
        foreach ($params as $key => $value) {
            if (!empty($value)) {
                $xslt .= "\n<xsl:param name=\"$key\" select=\"$value\" />";
            } else {
                $xslt .= "\n<xsl:param name=\"$key\" />";
            }
        }
        foreach ($variables as $key => $value) {
            $xslt .= "\n<xsl:variable name=\"$key\" select=\"$value\" />";
        }

        
        foreach ($files as $file) {
            $xslt .= "\n<xsl:include href=\"" . htmlentities($file) . "\" />";
        }
        $xslt .= "\n</xsl:stylesheet>";

        //die($xslt);

        $doc = new \depage\xml\Document();
        $doc->loadXML($xslt);

        return $doc;
    }
    // }}}
    // {{{ getPageIdFor
    /**
     * @return  null
     */
    protected function getPageIdFor($urlPath)
    {
        $pages = $this->xmldb->getDoc("pages");

        $xmlnav = new \depage\cms\xmlnav();
        list($this->urlsByPageId, $this->pageIdByUrl) = $xmlnav->getAllUrls($pages->getXml());

        $pageId = $this->pageIdByUrl[$urlPath];
        $pagedataId = $pages->getAttribute($pageId, "db:docref");

        return array($pageId, $pagedataId);
    }
    // }}}
    // {{{ getRelativePathTo
    /**
     * gets relative path to path of active page
     *
     * @public
     *
     * @param    $targetPath (string) path to target file
     *
     * @return    $path (string) relative path
     */
    public function getRelativePathTo($targetPath, $currentPath = null) {
        if ($currentPath === null) {
            $currentPath = $this->lang . $this->currentPath;
        }

        // link to self by default
        $path = '';
        if ($targetPath != '' && $targetPath != $currentPath) {
            $currentPath = explode('/', $currentPath);
            $targetPath = explode('/', $targetPath);

            $i = 0;
            while ((isset($currentPath[$i]) && $targetPath[$i]) && $currentPath[$i] == $targetPath[$i]) {
                $i++;
            }
            
            if (count($currentPath) - $i >= 1) {
                $path = str_repeat('../', count($currentPath) - $i - 1) . implode('/', array_slice($targetPath, $i));
            }
        }
        return $path;
    }
    // }}}
    
    // {{{ xsltCallFileinfo
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltCallFileinfo($path) {
        $xml = "";
        $path = "projects/" . $this->projectName . "/lib" . substr($path, 8);

        $fileinfo = new \depage\media\mediainfo();

        $info = $fileinfo->getInfo($path);
        $info['date'] = $info['date']->format("Y-m-d H:i:s");

        $xml = "<file";
        foreach ($info as $key => $value) {
            $xml .= " $key=\"" . htmlspecialchars($value) . "\"";
        }
        $xml .= " />";

        return $xml;
    }
    // }}}
    // {{{ xsltCallChangeSrc()
    /**
     * gets fileinfo for libref path
     *
     * @public
     *
     * @param    $path (string) libref path to target file
     *
     * @return    $xml (xml) file info as xml string
     */
    public function xsltCallChangeSrc($source) {
        $newSource = "";
        $posOffset = 0;
        while (($startPos = strpos($source, '"libref:/', $posOffset)) !== false) {
            $newSource .= substr($source, $posOffset, $startPos - $posOffset) . '"';
            $posOffset = $startPos + strlen("libref:/") + 3;
            $endPos = strpos($source, "\"", $posOffset);
            $newSource .= $this->getRelativePathTo('/lib' . substr($source, $startPos + 8, $endPos - ($startPos + 8)));
            $posOffset = $endPos;
        }
        $newSource .= substr($source, $posOffset);

        return $newSource;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */