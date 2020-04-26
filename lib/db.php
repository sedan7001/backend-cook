<?php

namespace Lib;

use \PDO as PDO;

class db{

    // Properties
    private $dbhost = 'localhost';
    private $dbuser = '1234';
    private $dbpass = '1234';
    private $dbname = '1234';

    // Connect
    public function connect(){
        $mysql_connect_str = "mysql:host=$this->dbhost;dbname=$this->dbname";
        $dbConnection = new PDO($mysql_connect_str , $this->dbuser , $this->dbpass);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbConnection;
    }
}
?>