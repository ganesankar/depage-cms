<?php

namespace Depage\WebSocket\Notifications;

use \Depage\Notifications\Notification;

class Application implements \Wrench\Application\DataHandlerInterface,
    \Wrench\Application\ConnectionHandlerInterface,
    \Wrench\Application\UpdateHandlerInterface
{
    // {{{ variables
    private $clients = [];
    private $deltaUpdates = [];
    protected $defaults = array(
        "db" => null,
        "auth" => null,
        'env' => "development",
        'timezone' => "UST",
    );
    // }}}

    // {{{ __construct
    function __construct() {
        $conf = new \Depage\Config\Config();
        $conf->readConfig(__DIR__ . "/../../../conf/dpconf.php");
        $this->options = $conf->getFromDefaults($this->defaults);

        // get database instance
        $this->pdo = new \Depage\Db\Pdo (
            $this->options->db->dsn, // dsn
            $this->options->db->user, // user
            $this->options->db->password, // password
            array(
                'prefix' => $this->options->db->prefix, // database prefix
            )
        );

        $this->timeFormatter = new \Depage\Formatters\TimeNatural();
        $this->lastTaskUpdate = time();
    }
    // }}}
    // {{{ onConnect
    public function onConnect(\Wrench\Connection $client): void
    {
        $id = $client->getId();
        if (empty($this->clients[$id])) {
            $this->clients[$id] = $client;
        }
    }
    // }}}
    // {{{ onDisconnect
    public function onDisconnect(\Wrench\Connection $client): void
    {
        $id = $client->getId();
        if (isset($this->clients[$id])) {
            unset($this->clients[$id]);
        }
    }
    // }}}
    // {{{ onUpdate
    public function onUpdate() {
        $this->sendNotifications();

        // send tasks only once per second
        $sendTaskUpdate = time() - $this->lastTaskUpdate > 0;
        if ($sendTaskUpdate) {
            $this->sendTasks();
        }
        $this->lastTaskUpdate = time();
    }
    // }}}
    // {{{ onData
    public function onData(string $data, \Wrench\Connection $client):void
    {
    }
    // }}}

    // {{{ sendTasks()
    /**
     * @brief sendTasks
     *
     * @return void
     **/
    protected function sendTasks()
    {
        $taskInfo = [];
        $tasks = \Depage\Tasks\Task::loadAll($this->pdo);

        foreach ($tasks as $task) {
            $progress = $task->getProgress();
            $status = sprintf(_("'%s' will finish in %s"), $progress->description, $this->timeFormatter->format($progress->estimated));
            $taskInfo[] = (object) [
                'type' => "task",
                'id' => $task->taskId,
                'name' => $task->taskName,
                'project' => $task->projectName,
                'percent' => $progress->percent,
                'status' => $status,
            ];
        }
        // @todo filter tasks per user
        foreach ($this->clients as $cid => $client) {
            $client->send(json_encode($taskInfo));
        }
    }
    // }}}
    // {{{ sendNotifications()
    /**
     * @brief sendNotifications
     *
     * @return void
     **/
    protected function sendNotifications()
    {
        foreach ($this->clients as $cid => $client) {
            // send notifications
            list($key, $sid) = explode("=", $client->getHeaders()['cookie']);
            $nn = Notification::loadBySid($this->pdo, $sid, "depage.%");

            foreach ($nn as $n) {
                $client->send(json_encode($n));

                $n->delete();
            }
        }
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :