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


class SurveyModel extends Model
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

    public function getDetail() {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "select * from ". $this->b_type ;

        //var_dump($greeting);
        try{

            $stmt = $db->prepare($sql);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");
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

    public function register(array $data) {


        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "INSERT INTO ".$this->b_type." (sub1, ans1, sub2, ans2, sub3, ans3, sub4, ans4)";
        //$sql = "INSERT INTO ".$this->b_type." (subject, sub1, ans1, sub2, ans2, sub3, ans3, sub4, ans4)";
        //$sql .= " VALUES(:subject, :sub1, :ans1, :sub2, :ans2, :sub3, :ans3, :sub4, :ans4)";
        $sql .= " VALUES(:sub1, :ans1, :sub2, :ans2, :sub3, :ans3, :sub4, :ans4)";

        try{

            $stmt = $db->prepare($sql);
            //$stmt->bindParam(':subject',$data['subject'], PDO::PARAM_STR);
            $stmt->bindParam(':sub1',$data['sub1'], PDO::PARAM_STR);
            $stmt->bindValue(':ans1',0, PDO::PARAM_INT);
            $stmt->bindParam(':sub2',$data['sub2'], PDO::PARAM_STR);
            $stmt->bindValue(':ans2',0, PDO::PARAM_INT);
            $stmt->bindParam(':sub3',$data['sub3'], PDO::PARAM_STR);
            $stmt->bindValue(':ans3',0, PDO::PARAM_INT);
            $stmt->bindParam(':sub4',$data['sub4'], PDO::PARAM_STR);
            $stmt->bindValue(':ans4',0, PDO::PARAM_INT);

            $db->beginTransaction();
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");
            $stmt->execute();

            $db->commit();
            $db = null;

        }catch(\PDOException $e){
            $db->rollBack();
            $db = null;
            throw $e;
        }catch(Exception $e){
            $db->rollBack();
            $db = null;
            throw $e;
        }
        return $this->getDetail();

    }

    public function updateAnswer($ans_type) {


        $db = new \Lib\db();
        $db = $db->connect();

        //$this->container->logger->addDebug("[ans_type::".$ans_type."]");
        //sqj injection으로 인해
        $type_arr = ['ans1', 'ans2', 'ans3', 'ans4'];
        $sql = "UPDATE ".$this->b_type." SET ".$type_arr[$ans_type-1]. "=".$type_arr[$ans_type-1]."+1";

        try{

            $stmt = $db->prepare($sql);
            $db->beginTransaction();
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");
            $stmt->execute();

            $db->commit();

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
        return $this->getDetail();
    }

    public function modify(array $data) {
        $db = new \Lib\db();
        $db = $db->connect();

        //$sql = "UPDATE ".$this->b_type." SET subject = :subject, ";
        $sql = "UPDATE ".$this->b_type ;
        $sql .= " SET sub1=:sub1, sub2=:sub2, sub3=:sub3, sub4=:sub4";

        try{

            $stmt = $db->prepare($sql);
            //$stmt->bindParam(':subject',$data['subject'], PDO::PARAM_STR);
            $stmt->bindParam(':sub1',$data['sub1'], PDO::PARAM_STR);
            $stmt->bindParam(':sub2',$data['sub2'], PDO::PARAM_STR);
            $stmt->bindParam(':sub3',$data['sub3'], PDO::PARAM_STR);
            $stmt->bindParam(':sub4',$data['sub4'], PDO::PARAM_STR);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");
            $db->beginTransaction();
            $stmt->execute();

            $db->commit();
            $db = null;

        }catch(\PDOException $e){
            $db->rollBack();
            $db = null;
            throw $e;
        }catch(Exception $e){
            $db->rollBack();
            $db = null;
            throw $e;
        }
        return $this->getDetail();
    }

    public function delete() {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "DELETE FROM ".$this->b_type ;

        try{

            $stmt = $db->prepare($sql);
            //$this->container->logger->addDebug("[query1::".$stmt->queryString."]");
            $db->beginTransaction();
            $stmt->execute();
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

}