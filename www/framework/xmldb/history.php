<?php
/**
 * @file    modules/xmldb/history.php
 *
 * cms xmldb module
 *
 *
 * copyright (c) 2002-2011 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author   Ben Wallis
 *
 */

namespace depage\xmldb;

class history {

    // {{{ variables
    private $pdo;
    private $db_ns;

    private $document;

    private $table_history;
    // }}}

    // {{{ constructor()
    public function __construct(\db_pdo $pdo, $table_prefix, document $document) {
        $this->document = $document;

        $this->pdo = $pdo;

        $this->db_ns = new xmlns("db", "http://cms.depagecms.net/ns/database");

        $this->table_history = $table_prefix . "_history";
    }
    // }}}

    // {{{ getVersions
    /**
     * getVersions
     *
     * Gets versions of the docment in the history.
     * returns array of time, user_id, published, and hash per version in array indexed on the last change time.
     *
     * @param null $published
     * @return mixed
     */
    public function getVersions($published = null) {
        $query = "SELECT h.lastchange, h.lastchange_uid, h.released
            FROM {$this->table_history} AS h
            WHERE h.data_id = :doc_id";

        $params = array(
            'doc_id' => $this->document->getDocId(),
        );

        if ($published !== null) {
            $query .= " AND h.released = :published";
            $params['published'] = $published == true;
        }

        $query .= "
            ORDER BY h.lastchange DESC;";

        $sth = $this->pdo->prepare($query);

        $versions = array();

        if ($sth->execute($params)) {
            $results = $sth->fetchAll();
            foreach($results as &$result) {
                $versions[strtotime($result['lastchange'])] = array(
                    'saved' => $result['lastchange'],
                    'user_id' => $result['lastchange_uid'],
                    'published' => $result['released'],
                    // 'digest' => $result['digest'],
                );
            }
        }

        return $versions;
    }
    // }}}

    // {{{ getXml
    /**
     * getXml
     *
     * @param null $timestamp
     * @return bool|\DOMDocument|object
     */
    public function getXml($timestamp = null) {
        $xml_doc = new \DOMDocument();

        $query = $this->pdo->prepare(
            "SELECT h.value
            FROM {$this->table_history} AS h
            WHERE h.lastchange = :timestamp"
        );

        $params = array(
            'timestamp' => date('Y-m-d H:i:s', $timestamp),
        );

        if ($query->execute($params)) {
            $result = $query->fetchObject();

            $xml_doc->loadXML($result->value);
        }

        return $xml_doc;
    }
    // }}}

    // {{{ getLastPublishedXml
    /**
     * getLastPublishedXml
     *
     * load last published version from history
     *
     */
    public function getLastPublishedXml() {
        $latest = reset($this->getVersions(true)->first());
        return $this->getXml($latest['date']);
    }
    // }}}

    // {{{
    /**
     *
     * gets the current docuemnt xml and saves a version to the history
     * add SHA hash column for data integrity
     *
     * @param $user_id
     * @param bool $published
     *
     * @return timestamp
     */
    public function save($user_id, $published = false) {

        // TODO ADD SHA1 hash
        $query = $this->pdo->prepare(
            "INSERT INTO {$this->table_history} (data_id, value, lastchange, lastchange_uid, released)
             VALUES(:doc_id, :xml, :timestamp, :user_id, :published);"
        );

        $timestamp = time();

        $params = array(
            'doc_id' => $this->document->getDocId(),
            'xml' => $this->document->getXml()->saveXml(),
            'timestamp' => date('Y-m-d H:i:s', $timestamp),
            'user_id' => $user_id,
            'published' => $published,
        );

        if ($query->execute($params)) {
            return $timestamp;
        }

        return false;
    }
    // }}}

    // {{{ restore
    /**
     * Restores the document to a previous state
     *
     */
    public function restore($timestamp) {
        $xml_doc = $this->getXml($timestamp);
        if ($this->document->save($xml_doc)) {
            return $xml_doc;
        };

        return false;
    }
    // }}}

    // delete {{{
    /**
     * Delete
     *
     * @param $timestamp
     */
    public function delete($timestamp) {
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->table_history}
             WHERE data_id = :doc_id AND lastchange = :timestamp;"
        );

        $params = array(
            'doc_id' => $this->document->getDocId(),
            'timestamp' => date('Y-m-d H:i:s', $timestamp),
        );

        if ($query->execute($params)) {
            return $query->rowCount() > 0;
        }

        return false;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */