<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 10.
 * Time: PM 1:51
 */

namespace APP\Models;

use \PDO as PDO;


class MemberModel extends Model
{
    //public $db;
    public $container;
    public function __construct($container)
    {
        $this->container = $container;
        //$this->db = new \Lib\db();
        //var_dump($this->db);
    }

    public function getMember($user) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "SELECT * FROM cook_member WHERE num = :num";

        //var_dump($user);
        try{

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':num',$user->num, PDO::PARAM_STR);
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

    public function getList() {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "SELECT * FROM cook_member";

        //var_dump($user);
        try{

            $stmt = $db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC); //FETCH_ASSOC

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

    public function getCount() {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "SELECT count(num) AS counts  FROM cook_member";

        //var_dump($user);
        try{

            $stmt = $db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_COLUMN);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]".$stmt->getAttribute());

            if ($stmt->rowCount() == 0) {
                $data[0] = 0;
            }

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

    public function getJoinStatByDay() {
        $db = new \Lib\db();
        $db = $db->connect();

        $join_count_arr[] = array();
        $regist_day_arr[] = array();

        $sql = "select count(num) as join_count_byday, left(regist_day,10) as regist_day from cook_member group by left(regist_day,10) order by regist_day;";

        //var_dump($user);
        try{

            $stmt = $db->prepare($sql);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_OBJ);

            //$this->container->logger->addDebug("[query::".$stmt->queryString."]".$stmt->getAttribute());

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

    public function registerMember(array $data) {


        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "INSERT INTO cook_member (id, name, password, nick, hp, email, level, regist_day)";
        $sql .= " VALUES(:id, :name, :password, :nick, :hp, :email, 9, :regist_day)";


        try{

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id',$data['id'], PDO::PARAM_STR);
            $stmt->bindParam(':name',$data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':password',$data['password'], PDO::PARAM_STR);
            $stmt->bindParam(':nick',$data['nick'], PDO::PARAM_STR);
            $stmt->bindParam(':hp',$data['hp'], PDO::PARAM_STR);
            $stmt->bindParam(':email',$data['email'], PDO::PARAM_STR);
            $stmt->bindParam(':regist_day',date("Y-m-d(H:i)"), PDO::PARAM_STR);
            $db->beginTransaction();
            $stmt->execute();
            //var_dump($db->lastInsertId());
            $data['num'] = $db->lastInsertId(); //commit 전 호출해야함

            $db->commit();

            return $data;

        }catch(\PDOException $e){
            $db->rollBack();
            $db = null;
            throw $e;
        }catch(Exception $e){
            $db->rollBack();
            $db = null;
            throw $e;
        }
    }

    public function modifyMember(array $data) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "UPDATE cook_member SET name=:name, password=:password, nick=:nick, hp=:hp, email=:email, modify_day=:modify_day";
        $sql .= " WHERE num=:num";

        try{

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':num',$data['num'], PDO::PARAM_INT);
            $stmt->bindParam(':name',$data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':password',$data['password'], PDO::PARAM_STR);
            $stmt->bindParam(':nick',$data['nick'], PDO::PARAM_STR);
            $stmt->bindParam(':hp',$data['hp'], PDO::PARAM_STR);
            $stmt->bindParam(':email',$data['email'], PDO::PARAM_STR);
            $stmt->bindParam(':modify_day',date("Y-m-d(H:i)"), PDO::PARAM_STR);
            $db->beginTransaction();
            $stmt->execute();
            //var_dump($db->lastInsertId());

            $db->commit();

            return $data;

        }catch(Exception $e){
            $db->rollBack();
            $db = null;
            throw $e;
        }
    }

    public function checkMemberById(string $id) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "SELECT * FROM cook_member WHERE id = :id";

        try{
            $data=false;

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id',$id, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() == 0) $data = true;
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

    public function checkMemberByNick(string $nick) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "SELECT * FROM cook_member WHERE nick = :nick";

        try{
            $data=false;

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':nick',$nick, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->rowCount() == 0) $data = true;
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