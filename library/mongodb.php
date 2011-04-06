<?php


class mongodbConnection {

    var $_mongodbConnection = null;


    public function __construct($host, $options){
        if($host){
            if($options){
                $this->_mongodbConnection = new Mongo($host, $options);
            } else {
                $this->_mongodbConnection = new Mongo($host);
            }
        } else {
            if($options){
                $this->_mongodbConnection = new Mongo('127.0.0.1:27017', $options);
            } else {
                $this->_mongodbConnection = new Mongo('127.0.0.1:27017');
            }
        }

    }

    public function getConnection(){
        return $this->_mongodbConnection;
    }

    // Instance management

    public static $instance = null;
    public static $instanceClassName = __CLASS__;

       public static function getInstance($host=null, $options=null) {
           if (self::$instance === null) {
               self::$instance = new self::$instanceClassName($host, $options);
           }
           return self::$instance;
       }

}