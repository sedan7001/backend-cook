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


class BasicBoardModel extends Model
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
            ////$this->container->logger->addDebug("[query::".$stmt->queryString."]".$stmt->getAttribute());

            if ($stmt->rowCount() == 0) {
                $data[0] = 0;
            }
            ////$this->container->logger->addDebug("[data::".$data[0]."]".$stmt->getAttribute());
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
        ////$this->container->logger->addDebug("[start_limit::".$start_limit."]");

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
            ////$this->container->logger->addDebug("[query::".$stmt->queryString."]");
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

    public function getListPlusRippleCount($type, $str, $page, $offset=10) {
        $db = new \Lib\db();
        $db = $db->connect();

        $start_limit = ($page - 1) * $offset;
        ////$this->container->logger->addDebug("[start_limit::".$start_limit."]");

        $sql = "select *, ";
        $sql .= "(SELECT count(*) From ". $this->b_type. "_ripple b WHERE b.parent = a.num) as rippleCount from ". $this->b_type. " a " ;
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

    public function getDetail($num) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "select * from ". $this->b_type ." where num=:num";
        $sql_hit_update = "UPDATE ". $this->b_type ." SET hit = (nullif(hit,0) + 1) WHERE num = :num";

        $sql_file = "select *, concat('/api/".str_replace("cook_", "", $this->b_type)."s/', b_num, '/files/', num ) as file_url ";
        $sql_file .= ", concat('/uploads/".str_replace("cook_", "", $this->b_type)."/', filename) as file_path ";
        $sql_file .= " from cook_file where b_type=:b_type and b_num =:b_num and is_del = 'N'";
        //$this->container->logger->addDebug("[query::".$sql_file."]");
        

        //var_dump($greeting);
        try{

            $stmt_hit_update = $db->prepare($sql_hit_update);
            $stmt_hit_update->bindParam(":num", $num, PDO::PARAM_INT);
            //$stmt_hit_update->bindParam(":id", $_SESSION['userid'], PDO::PARAM_STR);
            $stmt_hit_update->execute();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':num',$num, PDO::PARAM_INT);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");

            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC); //FETCH_ASSOC

            ////$this->container->logger->addDebug(var_dump($data));

            $stmt_file = $db->prepare($sql_file);
            $stmt_file->bindValue(':b_type', $this->b_type);
            $stmt_file->bindValue(':b_num', $num, PDO::PARAM_INT);
            $stmt_file->execute();

            $data['files'] = $stmt_file->fetchAll(PDO::FETCH_ASSOC);

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

        $sql = "INSERT INTO ".$this->b_type." (id, name, nick, subject, content, regist_day, is_html, hit)";
        $sql .= " VALUES(:id, :name, :nick, :subject, :content, :regist_day, :is_html, 0)";

        $sql_file = "INSERT INTO cook_file (b_type, id, b_num, org_filename, filename, mime_type, file_size, regist_day)";
        $sql_file .= " VALUES(:b_type, :id, :b_num, :org_filename, :filename, :mime_type, :file_size, :regist_day) ";
        //sql_file .= " ON DUPLICATE KEY UPDATE b_type=:b_type, b_num = :b_num, num=:num";

        try{

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id',$data['id'], PDO::PARAM_STR);
            $stmt->bindParam(':name',$data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':nick',$data['nick'], PDO::PARAM_STR);
            $stmt->bindParam(':subject',$data['subject'], PDO::PARAM_STR);
            $stmt->bindParam(':content',$data['content'], PDO::PARAM_STR);
            $stmt->bindParam(':regist_day',date("Y-m-d(H:i)"), PDO::PARAM_STR);
            $stmt->bindParam(':is_html',$data['is_html'], PDO::PARAM_STR);

            $db->beginTransaction();
            //$this->container->logger->addDebug("[query1::".$stmt->queryString."]");
            $stmt->execute();
            $data['num'] = $db->lastInsertId(); //commit 전 호출해야함


            //파일데이타의 멀티-insert
            //https://code.i-harness.com/ko/q/11f320
            //데이타가 많지 않으므로...
            $stmt_file = $db->prepare($sql_file);
            $file_array = array();
            foreach($data['files'] as $file_infos) {
                //return $response->write(print_r($file_infos));
                $file_infos['b_num'] = $data['num'];
                $stmt_file->bindParam(':b_type', $file_infos["b_type"],  PDO::PARAM_STR);
                $stmt_file->bindParam(':b_num', $file_infos["b_num"], PDO::PARAM_INT);
                $stmt_file->bindParam(':id', $file_infos["id"], PDO::PARAM_STR);
                $stmt_file->bindParam(':org_filename', $file_infos["org_filename"], PDO::PARAM_STR);
                $stmt_file->bindParam(':filename', $file_infos["filename"], PDO::PARAM_STR);
                $stmt_file->bindParam(':mime_type', $file_infos["mime_type"], PDO::PARAM_STR);
                $stmt_file->bindParam(':file_size', $file_infos["file_size"],PDO::PARAM_INT);
                $stmt_file->bindParam(':regist_day',date("Y-m-d(H:i)"), PDO::PARAM_STR);
                //$stmt_file->bindParam(':is_del',$file_infos["is_del"], PDO::PARAM_STR);
                //$this->container->logger->addDebug("[query2::".$stmt_file->queryString."]");
                $stmt_file->execute();
                $file_infos['num'] = $db->lastInsertId(); //commit 전 호출해야함;
                $file_infos['file_url'] = '/api/'.str_replace("cook_", "", $this->b_type).'s/'.$data['num'].'/files/'.$file_infos['num'];
                $file_infos['file_path'] = '/uploads/'.str_replace("cook_", "", $this->b_type).'/'.$file_infos['filename'];
                array_push($file_array, $file_infos);
            }
            $data['files'] = array_replace($data['files'], $file_array);

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

    public function modify(array $data) {
        $db = new \Lib\db();
        $db = $db->connect();

        $sql = "UPDATE ".$this->b_type." SET is_html = :is_html, subject = :subject, content = :content";
        $sql .= " WHERE num = :num";

        //이미 존재한다면, 삭제여부만 업데이트, 아니면 인서트, 비교는 pk인 num으로 함.
        $sql_file = "INSERT INTO cook_file (num, b_type, b_num, id, org_filename, filename, mime_type, file_size, regist_day, is_del)";
        $sql_file .= " VALUES(:num, :b_type, :b_num, :id, :org_filename, :filename, :mime_type, :file_size, :regist_day, :is_del) ";
        $sql_file .= " ON DUPLICATE KEY UPDATE IS_DEL = :is_del";

        try{

            $stmt = $db->prepare($sql);
            $stmt->bindParam(':num',$data['num'], PDO::PARAM_INT);
            $stmt->bindParam(':subject',$data['subject'], PDO::PARAM_STR);
            $stmt->bindParam(':is_html',$data['is_html'], PDO::PARAM_STR);
            $stmt->bindParam(':content',$data['content'], PDO::PARAM_STR);
            //$this->container->logger->addDebug("[query::".$stmt->queryString."]");
            $db->beginTransaction();
            $stmt->execute();

            //여러개가 업로드되면 안됨, 보통은 일어나지 않음.
            if ($stmt->rowCount() > 1) {
                $db->rollBack();
                throw new \Exception();
            }

            //기존 파일목록 데이타를 받는다. 클라이언트에서 삭제여부를 보내주는 걸로 처리

            $file_array = array();
            $stmt_file = $db->prepare($sql_file);
            foreach($data['files'] as $file_infos) {
                //return $response->write(print_r($file_infos));
                $stmt_file->bindParam(':num', $file_infos['num'],  PDO::PARAM_INT);
                $stmt_file->bindParam(':b_type', $file_infos['b_type'],  PDO::PARAM_STR);
                $stmt_file->bindParam(':b_num', $file_infos['b_num'], PDO::PARAM_INT);
                $stmt_file->bindParam(':id', $file_infos['id'], PDO::PARAM_STR);
                $stmt_file->bindParam(':org_filename', $file_infos["org_filename"], PDO::PARAM_STR);
                $stmt_file->bindParam(':filename', $file_infos["filename"], PDO::PARAM_STR);
                $stmt_file->bindParam(':mime_type', $file_infos["mime_type"], PDO::PARAM_STR);
                $stmt_file->bindParam(':file_size', $file_infos["file_size"],PDO::PARAM_INT);
                $stmt_file->bindParam(':regist_day',date("Y-m-d(H:i)"), PDO::PARAM_STR);
                $stmt_file->bindParam(':is_del',$file_infos["is_del"], PDO::PARAM_STR);
                //$this->container->logger->addDebug("[query::".$stmt_file->queryString."]");
                $stmt_file->execute();

                $file_infos['num'] = empty($file_infos['num']) ? $db->lastInsertId() : $file_infos['num'] ; //commit 전 호출해야함;
                $file_infos['file_url'] = '/api/'.str_replace("cook_", "", $this->b_type).'s/'.$data['num'].'/files/'.$file_infos['num'];
                $file_infos['file_path'] = '/uploads/'.str_replace("cook_", "", $this->b_type).'/'.$file_infos['filename'];
                array_push($file_array, $file_infos);
            }
            $data['files'] = array_replace($data['files'], $file_array);

            $db->commit();
            $db = null;

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

        $sql_file = "DELETE FROM cook_file" ;
        $sql_file .= " WHERE b_type = :b_type AND b_num = :b_num";

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

            //파일 삭제
            $stmt_file=$db->prepare($sql_file);
            $stmt_file->bindValue(':b_type', $this->b_type);
            $stmt_file->bindValue(':b_num', $data['num'], PDO::PARAM_INT);
            //$this->container->logger->addDebug("[query2::".$stmt_file->queryString."]");
            $stmt_file->execute();

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