<?php

namespace Depage\Notifications;

/**
 * brief Notfication
 * Class Notfication
 */
class Notification extends \Depage\Entity\Entity
{
    // {{{ variables
    /**
     * @brief fields
     **/
    static protected $fields = array(
        "id" => null,
        "uid" => null,
        "sid" => null,
        "tag" => "",
        "title" => "",
        "message" => "",
        "options" => "",
    );

    /**
     * @brief primary
     **/
    static protected $primary = array("id");

    /**
     * @brief pdo object for database access
     **/
    protected $pdo = null;
    // }}}

    // {{{ constructor()
    /**
     * constructor
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     *
     * @return      void
     */
    public function __construct(\Depage\Db\Pdo $pdo) {
        parent::__construct($pdo);

        $this->pdo = $pdo;
    }
    // }}}

    // {{{ loadBySid()
    /**
     * gets a user-object by id directly from database
     *
     * @public
     *
     * @param       Depage\Db\Pdo     $pdo        pdo object for database access
     * @param       int     $id         id of the user
     *
     * @return      auth_user
     */
    static public function loadBySid($pdo, $sid) {
        $fields = "n." . implode(", n.", self::getFields());

        $query = $pdo->prepare(
            "SELECT $fields
            FROM
                {$pdo->prefix}_notifications AS n,
                {$pdo->prefix}_auth_sessions AS s
            WHERE
                n.sid = :sid1 OR
                (s.sid = :sid2 AND n.uid = s.userId)"
        );
        $query->execute(array(
            ':sid1' => $sid,
            ':sid2' => $sid,
        ));

        // pass pdo-instance to constructor
        $query->setFetchMode(\PDO::FETCH_CLASS, get_called_class(), array($pdo));
        $n = $query->fetchAll();

        return $n;
    }
    // }}}

    // {{{ save()
    /**
     * save a user object
     *
     * @public
     */
    public function save() {
        $fields = array();
        $primary = self::$primary[0];
        $isNew = $this->data[$primary] === null;

        $dirty = array_keys($this->dirty, true);

        if (count($dirty) > 0) {
            if ($isNew) {
                $query = "INSERT INTO {$this->pdo->prefix}_notifications";
            } else {
                $query = "UPDATE {$this->pdo->prefix}_notifications";
            }
            foreach ($dirty as $key) {
                $fields[] = "$key=:$key";
            }
            $query .= " SET " . implode(",", $fields);

            if (!$isNew) {
                $query .= " WHERE $primary=:$primary";
                $dirty[] = $primary;
            }

            $params = array_intersect_key($this->data,  array_flip($dirty));

            $cmd = $this->pdo->prepare($query);
            $success = $cmd->execute($params);

            if ($isNew) {
                $this->$primary = $this->pdo->lastInsertId();
            }

            if ($success) {
                $this->dirty = array_fill_keys(array_keys(static::$fields), false);
            }
        }
    }
    // }}}
    // {{{ delete()
    /**
     * @brief delete
     *
     * @param mixed
     * @return void
     **/
    public function delete()
    {
        $primary = self::$primary[0];
        $isNew = $this->data[$primary] === null;

        if (!$isNew) {
            $query = $this->pdo->prepare("DELETE FROM {$this->pdo->prefix}_notifications WHERE $primary=:primary");
            $sucess = $query->execute(array(
                'primary' => $this->data[$primary],
            ));
        }

        return true;
    }
    // }}}
    // {{{ updateSchema()
    /**
     * @brief updateSchema
     *
     * @return void
     **/
    public static function updateSchema($pdo)
    {
        $schema = new \Depage\Db\Schema($pdo);

        $schema->setReplace(
            function ($tableName) use ($pdo) {
                return $pdo->prefix . $tableName;
            }
        );
        $schema->loadGlob(__DIR__ . "/Sql/*.sql");
        $schema->update();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */