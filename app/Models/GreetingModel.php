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


class GreetingModel extends Model
{
    //public $db;
    public $container;
    public function __construct($container)
    {
        //  var_dump($container->logger);
        $this->container = $container;
        //$this->db = new \Lib\db();
        //var_dump($this->db);
    }

    public function getCount($type, $str) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "select count(num) as counts from cook_greet ";
        $sql .= (!empty($type) && !empty($str)) ? " where " .$type. " like :str" : "" ;
        //$sql .= " order by num desc";

        $str = '%'.$str.'%';
        //var_dump($str);
        try{

            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); //limit(동적쿼리) 로 인해 사용, true로 하면 오류

            $stmt = $db->prepare($sql);
            if ((!empty($type) && !empty($str))) {
                $stmt->bindParam(':str', $str, PDO::PARAM_STR);
            }
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_COLUMN);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]".$stmt->getAttribute());

            if ($stmt->rowCount() == 0) {
                $data[0] = 0;
            }
            //$this->container->logger->addDebug("[data::".$data[0]."]".$stmt->getAttribute());
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

    public function getList($type, $str, $page, $offset=10) {
        $db = new \Lib\db();
        $db = $db->connect();

        $start_limit = ($page - 1) * $offset;
        //$this->container->logger->addDebug("[start_limit::".$start_limit."]");

        $sql = "select * from cook_greet";
        $sql .= (!empty($type) && !empty($str)) ? " where " .$type. " like :str" : "" ;
        $sql .= " order by num desc limit :start_limit, :offset";

        $str = '%'.$str.'%';
        try{

            //http://servedev.tistory.com/42
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); //limit(동적쿼리) 로 인해 사용, true로 하면 오류

            $stmt = $db->prepare($sql);
            if ((!empty($type) && !empty($str))) {
                $stmt->bindParam(':str', $str, PDO::PARAM_STR);
            }
            $stmt->bindParam(':start_limit',$start_limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset',$offset, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_OBJ);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");
            //$this->container->logger->addDebug("[offset::".$offset."]");
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

    public function getGreeting($num) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "select * from cook_greet where num=:num";
        $sql_hit_update = "UPDATE cook_greet SET hit = (nullif(hit,0) + 1) WHERE num = :num";

        //var_dump($greeting);
        try{

            $stmt_hit_update = $db->prepare($sql_hit_update);
            $stmt_hit_update->bindParam(":num", $num, PDO::PARAM_INT);
            //$stmt_hit_update->bindParam(":id", $_SESSION['userid'], PDO::PARAM_STR);
            $stmt_hit_update->execute();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':num',$num, PDO::PARAM_INT);
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
//
//    public function registerGreeting(array $data) {
//
//
//        $db = new \Lib\db();
//        $db = $db->connect();
//
//        $sql = "INSERT INTO cook_member (id, name, password, nick, hp, email, level, regist_day)";
//        $sql .= " VALUES(:id, :name, :password, :nick, :hp, :email, 9, :regist_day)";
//
//
//        try{
//
//            $stmt = $db->prepare($sql);
//            $stmt->bindParam(':id',$data['id'], PDO::PARAM_STR);
//            $stmt->bindParam(':name',$data['name'], PDO::PARAM_STR);
//            $stmt->bindParam(':password',$data['password'], PDO::PARAM_STR);
//            $stmt->bindParam(':nick',$data['nick'], PDO::PARAM_STR);
//            $stmt->bindParam(':hp',$data['hp'], PDO::PARAM_STR);
//            $stmt->bindParam(':email',$data['email'], PDO::PARAM_STR);
//            $stmt->bindParam(':regist_day',date("Y-m-d(H:i)"), PDO::PARAM_STR);
//            $db->beginTransaction();
//            $stmt->execute();
//            //var_dump($db->lastInsertId());
//            $data['num'] = $db->lastInsertId(); //commit 전 호출해야함
//
//            $db->commit();
//
//            return $data;
//
//        }catch(\PDOException $e){
//            $db->rollBack();
//            $db = null;
//            throw $e;
//        }catch(Exception $e){
//            $db->rollBack();
//            $db = null;
//            throw $e;
//        }
//    }
//
//    public function modifyGreeting(array $data) {
//        $db = new \Lib\db();
//        $db = $db->connect();
//
//        $sql = "UPDATE cook_member SET name=:name, password=:password, nick=:nick, hp=:hp, email=:email, modify_day=:modify_day";
//        $sql .= " WHERE num=:num";
//
//        try{
//
//            $stmt = $db->prepare($sql);
//            $stmt->bindParam(':num',$data['num'], PDO::PARAM_INT);
//            $stmt->bindParam(':name',$data['name'], PDO::PARAM_STR);
//            $stmt->bindParam(':password',$data['password'], PDO::PARAM_STR);
//            $stmt->bindParam(':nick',$data['nick'], PDO::PARAM_STR);
//            $stmt->bindParam(':hp',$data['hp'], PDO::PARAM_STR);
//            $stmt->bindParam(':email',$data['email'], PDO::PARAM_STR);
//            $stmt->bindParam(':modify_day',date("Y-m-d(H:i)"), PDO::PARAM_STR);
//            $db->beginTransaction();
//            $stmt->execute();
//            //var_dump($db->lastInsertId());
//
//            $db->commit();
//
//            return $data;
//
//        }catch(Exception $e){
//            $db->rollBack();
//            $db = null;
//            throw $e;
//        }
//    }
//
//    public function checkGreetingById(string $id) {
//        $db = new \Lib\db();
//        $db = $db->connect();
//
//        $sql = "SELECT * FROM cook_member WHERE id = :id";
//
//        try{
//            $data=false;
//
//            $stmt = $db->prepare($sql);
//            $stmt->bindParam(':id',$id, PDO::PARAM_STR);
//            $stmt->execute();
//            if ($stmt->rowCount() == 0) $data = true;
//            $db = null;
//
//            return $data;
//
//        }catch(\PDOException $e){
//            $db = null;
//            throw $e;
//        }catch(Exception $e){
//            $db = null;
//            throw $e;
//        }
//
//    }
//
//    public function checkGreetingByNick(string $nick) {
//        $db = new \Lib\db();
//        $db = $db->connect();
//
//        $sql = "SELECT * FROM cook_member WHERE nick = :nick";
//
//        try{
//            $data=false;
//
//            $stmt = $db->prepare($sql);
//            $stmt->bindParam(':nick',$nick, PDO::PARAM_STR);
//            $stmt->execute();
//            if ($stmt->rowCount() == 0) $data = true;
//            $db = null;
//
//            return $data;
//
//        }catch(\PDOException $e){
//            $db = null;
//            throw $e;
//        }catch(Exception $e){
//            $db = null;
//            throw $e;
//        }
//
//    }

}