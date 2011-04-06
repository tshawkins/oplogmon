<?php

/**
 *
 */


class mongodbConnection {

    var $_mongodbConnection = null;
    var $_replicasetname = null;

    /**
     * @param  string|null $host
     * @param  array|null $options
     */

    public function __construct($host=null, $options=null){
        $_host =  '127.0.0.1:27017';
        if(!empty($host)){
            $_host=$host;
        }
        $_options = array();
        if(!empty($options)){
            $_options=$options;
        }
        $this->_mongodbConnection = new Mongo($_host, $_options);

    }

    public function getConnection(){
        return $this->_mongodbConnection;
    }

    /**
     * @return bool
     */


    public function isReplicSet(){
        return !empty($this->_replicasetname);
    }

    /**
     * @return string|false
     */

     public function getReplicSetName(){
        if(!$this->isReplicSet()){
            return false;
        }
        return $this->_replicasetname;
    }

    // Instance management

    public static $instance = null;
    public static $instanceClassName = __CLASS__;

    /**
     * @static
     * @param string|null $host
     * @param array|null $options
     * @return mongodbconnection
     */

       public static function getInstance($host=null, $options=null) {
           if (self::$instance === null) {
               self::$instance = new self::$instanceClassName($host, $options);
           }
           return self::$instance;
       }

}