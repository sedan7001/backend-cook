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


class MemoBoardModel extends Model
{
    //public $db;
    public $container;
    private $b_type;
    public function __construct($container, $b_type) //Di, 게시판타입
    {
        //  var_dump($container->logger);
        $this->container = $container;
        $this->b_type = $b_type;
        //$this->db = new \Lib\db();
        //var_dump($this->db);
    }

    public function getCount($type, $str) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "select count(num) as counts from ". $this->b_type ;
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

        $sql = "select * from ". $this->b_type ;
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

    public function register(array $data) {


        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "INSERT INTO ".$this->b_type." (id, name, nick, content, regist_day)";
        $sql .= " VALUES(:id, :name, :nick, :content, :regist_day)";

        try{

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id',$data['id'], PDO::PARAM_STR);
            $stmt->bindParam(':name',$data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':nick',$data['nick'], PDO::PARAM_STR);
            $stmt->bindParam(':content',$data['content'], PDO::PARAM_STR);
            $stmt->bindParam(':regist_day',date("Y-m-d(H:i)"), PDO::PARAM_STR);

            $db->beginTransaction();
            //$this->container->logger->addDebug("[query1::".$stmt->queryString."]");
            $stmt->execute();
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

    public function delete(array $data) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "DELETE FROM ".$this->b_type ;
        $sql .= " WHERE num = :num AND id = :id";

        try{

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':num',$data['num'], PDO::PARAM_INT);
            $stmt->bindParam(':id',$data['id'], PDO::PARAM_STR);
            //$this->container->logger->addDebug("[query1::".$stmt->queryString."]");
            $db->beginTransaction();
            $stmt->execute();

            //여러개가 업로드되면 안됨, 보통은 일어나지 않음.
            if ($stmt->rowCount() > 1) {
                $db->rollBack();
                throw new \Exception();
            }

            $db->commit();
            $db = null;

            return true;
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

    //권한체크
    public function checkById($id, $num) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "SELECT id FROM ".$this->b_type;
        $sql .= " WHERE num = :num AND id = :id";

        try{
            $data=true;

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':num',$num, PDO::PARAM_INT);
            $stmt->bindParam(':id',$id, PDO::PARAM_STR);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");
            $stmt->execute();
            if ($stmt->rowCount() == 0) $data = false;
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

    //존재여부체크
    public function checkByNum($num) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "SELECT id FROM ".$this->b_type;
        $sql .= " WHERE num = :num ";

        try{
            $data=true;

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':num',$num, PDO::PARAM_INT);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");
            $stmt->execute();
            if ($stmt->rowCount() == 0) $data = false;
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