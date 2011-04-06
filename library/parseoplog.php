<?php

require_once('mongodb.php');

class parseOplog {

    var $_mongodbConnection = null;
    var $lastts = null;

    public function __construct($host = null, $options = null) {
        $this->_mongodbConnection = mongodbConnection::getInstance($host, $options);

        //$this->lastts = new MongoTimestamp(0);
    }

    // Poll Oplog

    public function pollOplog() {


        $mongo = $this->_mongodbConnection->getConnection();


        $oplog = $mongo->selectCollection('local', 'oplog.$main');

        if ($oplog) {

            //$query = array("ts" => array('$gt' => $this->lastts));

            $results = $oplog->find()->timeout(10000)->tailable(true);
            while (1) {
                if (!$results->hasNext()) {
                    if ($results->dead()) {
                        break;
                    }
                    usleep(100000);
                } else {
                    $logentry = $results->getNext();

                    // $this->lastts = $logentry['ts'];

                    $this->_handleLog($logentry);
                }
            }
        }


        return true;
    }

    private function _handleLog(&$logentry) {
        switch ($logentry['op']) {
            case 'n':
                // do nothingÊno-op
                break;

            case 'i':
                // insert
                $this->_handleObjectInsert($logentry);
                break;

            case 'u':
                // update
                $this->_handleObjectUpdate($logentry);
                break;

            case 'd':
                // update
                $this->_handleObjectDelete($logentry);
                break;


            default:
                System_Daemon::info("unknown op:" . print_r($logentry, true));
                break;
        }
    }


    private function _handleObjectInsert(&$logentry) {
        System_Daemon::info("INSERT:=>" . print_r($logentry, true));
    }


    private function _handleObjectUpdate(&$logentry) {
        System_Daemon::info("UPDATE:=>" . print_r($logentry, true));
    }

    private function _handleObjectDelete(&$logentry) {
        System_Daemon::info("DELETE:=>" . print_r($logentry, true));
    }

    // Instance management

    public static $instance = null;
    public static $instanceClassName = __CLASS__;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self::$instanceClassName();
        }
        return self::$instance;
    }

}