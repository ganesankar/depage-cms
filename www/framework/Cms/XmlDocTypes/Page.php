<?php

namespace Depage\Cms\XmlDocTypes;

class Page extends Base
{
    use Traits\MultipleLanguages;

    private $table_nodetypes;
    private $pathXMLtemplate = "";

    // {{{ constructor
    public function __construct($xmlDb, $document) {
        parent::__construct($xmlDb, $document);

        $this->pathXMLtemplate = $this->xmlDb->options['pathXMLtemplate'];
        $this->table_nodetypes = $xmlDb->table_nodetypes;

        $types = $this->getNodeTypes();
        $this->availableNodes = [];
        $this->validParents = [];

        foreach ($this->getNodeTypes() as $id => $t) {
            $doc = new \DOMDocument();
            $success = $doc->load("{$this->pathXMLtemplate}/{$t->xmlTemplate}");

            if (!$success) continue;

            $contentElement = $doc->documentElement->firstChild;
            while ($contentElement && $contentElement->nodeType != \XML_ELEMENT_NODE) {
                $contentElement = $contentElement->nextSibling;
            }
            if ($contentElement) {
                $nodeName = $contentElement->nodeName;

                $this->validParents[$nodeName] = explode(",", $t->validParents);

                $t->validParents = $this->validParents[$nodeName];
                $this->availableNodes[$t->xmlTemplate] = $t;
            }
        }
    }
    // }}}

    // {{{ onAddNode
    /**
     * On Add Node
     *
     * @param \DomNode $node
     * @param $target_id
     * @param $target_pos
     * @param $extras
     * @return null
     */
    public function onAddNode(\DomNode $node, $target_id, $target_pos, $extras) {
        $this->testNodeLanguages($node);

        list($doc, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $xpath = new \DOMXPath($doc);

        $nodelist = $xpath->query("./edit:date[@value = '@now']", $node);
        if ($nodelist->length > 0) {
            // search for languages used in document
            for ($i = 0; $i < $nodelist->length; $i++) {
                $nodelist->item($i)->setAttribute('value', date('Y/m/d'));
            }
        }

        $nodelist = $xpath->query("./edit:text_singleline[@value = '@author']", $node);
        if ($nodelist->length > 0) {
            // search for languages used in document
            for ($i = 0; $i < $nodelist->length; $i++) {
                if (!empty($this->xmlDb->options['userId'])) {
                    $user = \Depage\Auth\User::loadById($this->xmlDb->pdo, $this->xmlDb->options['userId']);
                    $nodelist->item($i)->setAttribute('value', $user->fullname);
                } else {
                    $nodelist->item($i)->setAttribute('value', "");
                }
            }
        }
    }
    // }}}
    // {{{ onDocumentChange()
    /**
     * @brief onDocumentChange
     *
     * @param mixed
     * @return void
     **/
    public function onDocumentChange()
    {
        $this->xmlDb->getDoc("pages")->clearCache();

        parent::onDocumentChange();

        return true;

    }
    // }}}
    // {{{ onHistorySave()
    /**
     * @brief onHistorySave
     *
     * @param mixed $param
     * @return void
     **/
    public function onHistorySave()
    {
        parent::onHistorySave();

        $doc = $this->xmlDb->getDoc("pages");
        $doc->clearCache();

        $pageInfo = $this->project->getXmlNav()->getPageInfo($this->document->getDocInfo()->name);
        $parentPageId = $doc->getParentIdById($pageInfo->pageId);

        $prefix = $this->xmlDb->pdo->prefix . "_proj_" . $this->project->name;
        $deltaUpdates = new \Depage\WebSocket\JsTree\DeltaUpdates($prefix, $this->xmlDb->pdo, $this->xmlDb, $doc->getDocId(), $this->project->name);

        $deltaUpdates->recordChange($parentPageId);
    }
    // }}}

    // {{{ addNodeType
    public function addNodeType($name, $xml, $validParents, $pos) {
        $filename = \Depage\Html\Html::getEscapedUrl($name) . ".xml";

        $rootNode = $xml->documentElement;
        $rootNode->setAttribute("valid-parents", $validParents);
        $rootNode->setAttribute("pos", $pos);

        $xml->save($this->pathXMLtemplate . $filename);
    }
    // }}}
    // {{{ getNodeTypes
    public function getNodeTypes() {
        $nodetypes = [];
        $templates = $this->project->getXmlTemplates();

        foreach ($templates as $id => $t) {
            $xml = new \Depage\Xml\Document();
            if (!$xml->load($this->pathXMLtemplate . $t)) {
                continue;
            }
            if ($xml->documentElement->getAttribute("valid-parents") == "") {
                continue;
            }
            $data = (object)[
                'id' => $t,
                'icon' => "",
                'xmlTemplate' => $t,
                'validParents' => str_replace(" ", "", $xml->documentElement->getAttribute("valid-parents")),
                'pos' => (int) $xml->documentElement->getAttribute("pos"),
                'lastchange' => filemtime($this->pathXMLtemplate . $t),
                'name' => $xml->documentElement->getAttribute("name"),
                'newName' => '',
                'nodeName' => '',
            ];
            $data->xmlTemplateData = "";
            $names = [];
            foreach ($xml->documentElement->childNodes as $node) {
                if ($node->nodeType != \XML_COMMENT_NODE) {
                    $data->xmlTemplateData .= $xml->saveXML($node);
                }
                if ($node->nodeType == \XML_ELEMENT_NODE) {
                    $data->nodeName = $node->nodeName;
                    $names[] = $node->getAttribute("name");
                }
            }
            if (empty($data->name)) {
                $data->name = implode(" / ", $names);
            }
            $data->newName = $data->name;

            $nodetypes[$id] = $data;
        }

        uasort($nodetypes, function($a, $b) {
            return $a->pos <=> $b->pos;
        });

        return $nodetypes;
    }
    // }}}
    // {{{ getPageSubtypes()
    /**
     * @brief getPageSubtypes
     *
     * @param mixed
     * @return void
     **/
    public function getSubtypes($for)
    {
        $subtypes = [];

        foreach ($this->availableNodes as $node) {
            if (in_array($for, $node->validParents)) {
                $subtypes[] = $node;
            }
        }

        return $subtypes;
    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        $changed = $this->testNodeLanguages($node);

        $this->addReleaseStatusAttributes($node->firstChild);

        return $changed;
    }
    // }}}
    // {{{ testDocumentForHistory
    public function testDocumentForHistory($xml) {
        parent::testDocumentForHistory($xml);

        $xml->firstChild->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "true");
    }
    // }}}
    // {{{ addReleaseStatusAttributes()
    /**
     * @brief addReleaseStatusAttributes
     *
     * @param mixed $
     * @return void
     **/
    public function addReleaseStatusAttributes($node)
    {
        $info = $this->document->getDocInfo();
        $versions = array_values($this->document->getHistory()->getVersions(true, 1));

        // @todo fix not to get from timestamp when sha1 did no change
        if (count($versions) > 0 && $info->lastchange->getTimestamp() < $versions[0]->lastsaved->getTimestamp()) {
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "true");
        } else {
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:released", "false");
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
