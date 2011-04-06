<?php

require_once('mongodb.php');

class parseOplog {

    var $_mongodbConnection = null;

    /**
     * @param string|null $host
     * @param array|null $options
     */

    public function __construct($host = null, $options = null) {
        $this->_mongodbConnection = mongodbConnection::getInstance($host, $options);

    }

    /**
     * @return bool
     */

    public function pollOplog() {


        $mongo = $this->_mongodbConnection->getConnection();

        // Very simple minded - only work for master/slave replication

        $oplog = $mongo->selectCollection('local', 'oplog.$main');

        if ($oplog) {

            $results = $oplog->find()->timeout(10000)->tailable(true);
            while (1) {
                if (!$results->hasNext()) {
                    if ($results->dead()) {
                        break;
                    }
                    usleep(100000);
                } else {
                    $logentry = $results->getNext();

                    $this->_handleLog($logentry);
                }
            }
        } else {
            return false; // no oplog to track
        }


        return true;
    }

    private function _handleLog(&$logentry) {
        switch ($logentry['op']) {
            case 'n':
                // do nothing no-op
                break;

            case 'i':
                // insert
                $this->handleObjectInsert($logentry);
                break;

            case 'u':
                // update
                $this->handleObjectUpdate($logentry);
                break;

            case 'd':
                // update
                $this->handleObjectDelete($logentry);
                break;


            default:
                System_Daemon::info("unknown op:" . print_r($logentry, true));
                break;
        }
    }

    // temporary stubs


    public function handleObjectInsert(&$logentry) {
        System_Daemon::info("INSERT:=>" . print_r($logentry, true));
    }


    public function handleObjectUpdate(&$logentry) {
        System_Daemon::info("UPDATE:=>" . print_r($logentry, true));
    }

    public function handleObjectDelete(&$logentry) {
        System_Daemon::info("DELETE:=>" . print_r($logentry, true));
    }

    // Instance management

    public static $instance = null;
    public static $instanceClassName = __CLASS__;

    /**
     * @static
     * @return parseOplog
     */

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self::$instanceClassName();
        }
        return self::$instance;
    }

}