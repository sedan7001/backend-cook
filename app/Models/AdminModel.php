<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 10.
 * Time: PM 1:51
 */

namespace APP\Models;

use APP\Exceptions\SurveyException;
use \PDO as PDO;


class AdminModel extends Model
{
    //public $db;
    public $container;
    public function __construct($container) //Di, 게시판타입
    {
        //  var_dump($container->logger);
        $this->container = $container;
        //$this->db = new \Lib\db();
        //var_dump($this->db);
    }

    public function getDBUsage() {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "SELECT table_schema, ";
        $sql .= " round(SUM((data_length+index_length)/1024/1024),2) MB";
        $sql .= " FROM information_schema.tables";
        $sql .= " where table_schema = ''";

        //var_dump($greeting);
        try{

            $stmt = $db->prepare($sql);
            $this->container->logger->addDebug("[query::".$stmt->queryString."]");
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC); //FETCH_ASSOC

            $db = null;

            return $data;

        }catch(\PDOException $e){
            $db = null;
            throw $e;
        }catch(Exception $e){
            $db = null;
            throw $e;
        }
    }

    public function getTableUsage() {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "SELECT table_name, ";
        $sql .= " round(((data_length+index_length)/1024),2) KB";
        $sql .= " FROM information_schema.TABLES";
        $sql .= " where table_name like 'cook%'";


        //var_dump($greeting);
        try{

            $stmt = $db->prepare($sql);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_OBJ); //FETCH_ASSOC

            $db = null;

            return $data;

        }catch(\PDOException $e){
            $db = null;
            throw $e;
        }catch(Exception $e){
            $db = null;
            throw $e;
        }
    }

}