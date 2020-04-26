<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 10.
 * Time: PM 1:51
 */

namespace APP\Controllers;


use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use APP\Models\BasicBoardModel;
use APP\Controllers\Controller;
use APP\Exceptions\SurveyException;
use \Lib\Pagination;


class GreetingController extends Controller
{

    protected $basicBoardModel;
    public $container;
    public function __construct($container)
    {
        $this->container = $container;
        //print_r($container);
        $this->basicBoardModel = new BasicBoardModel($container, 'cook_greet');
    }


    //목록
    public function getGreetings(Request $request, Response $response, $args) {


        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $page = $request->getParam('page', $default = 1);

            $qs = $request->getParam('q');
            $search = (NULL != $qs) ? explode(',', $request->getParam('q')) : array();

            $str = (count($search) > 0) ? $search[1] : '';
            $str_type = (count($search) > 0) ? $search[0] : '';
            //echo(empty($str));
            $key_arr = ["글쓴이" => "name", "제목" => "subject", "내용" => "content"];
            if (!empty($str) && !array_key_exists($str, $key_arr)) {
                $resultData = array('result' => '400', 'message' => 'invalid query string', 'data' => '{"error" : {"text" : "검색어가 정확하지 않습니다."}}');
                throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $type = !empty($str_type) ? $key_arr[$str_type] : '';
            }
            $count = $this->basicBoardModel->getCount($type, $str);
            $pagination = new Pagination($request, $count, $page);
            $data = $pagination->getPagination();
            $data['q'] = $qs;
            //var_dump($this->greetingModel->getList($type, $str, $page));
            $data['list'] = $this->basicBoardModel->getList($type, $str, $page);
            if (count($data) == 0) {
                $resultData = array('result' => '404', 'message' => 'no data', 'data' => '{"error" : {"text" : "리소스가 없습니다."}}');
                throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
            $resultData = array('result' => '200', 'message' => 'greeting list data', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(SurveyException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }

    }

    //가입인사상세
    public function getGreeting(Request $request, Response $response, $args) {

        $num = filter_var($args['num'], FILTER_SANITIZE_NUMBER_INT);;

        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->basicBoardModel->getDetail($num);
            if (count($data) == 0) {
                $resultData = array('result' => '404', 'message' => 'no data', 'data' => '{"error" : {"text" : "리소스가 없습니다."}}');
                throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
            $resultData = array('result' => '200', 'message' => 'greeting data', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(SurveyException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    //가입인사 입력
    public function registerGreeting(Request $request, Response $response, $args) {

        $data = $request->getParsedBody();

        $user = $request->getAttribute('user'); //로그인 사용자정보
        $user = objectToArray($user);

        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('subject', 'content');
        try {       //throw를 던지는 경우는 try catch를 해야함.
            foreach ($key_arr as $key_name) {
                if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
                    $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
                    throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $greeting = [];
            $greeting['subject'] = filter_var($data['subject'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $greeting['name'] = filter_var($user['name'], FILTER_SANITIZE_STRING);
            $greeting['is_html'] = filter_var($data['is_html'], FILTER_SANITIZE_STRING);
            $greeting['nick'] = filter_var($user['nick'], FILTER_SANITIZE_STRING);
            $greeting['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);
            if ($greeting['is_html'] == "y") {
                $greeting['content'] = filter_var($data['content'], FILTER_SANITIZE_SPECIAL_CHARS);
            } else {
                $greeting['content'] = filter_var($data['content'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); //htmlspecialchars
            }

            $data = $this->basicBoardModel->register($greeting);

            $resultData = array('result' => '201', 'message' => 'Resource created', 'data' => $data);
            return $response
                ->withHeader('Location','/cook/api/greetings')
                ->withStatus(201)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(SurveyException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }

        //return
    }

    public function modifyGreeting(Request $request, Response $response, $args) {
        $num = $args['num'];
        $data = $request->getParsedBody();

        $user = $request->getAttribute('user'); //로그인 사용자정보
        $user = objectToArray($user);

        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('subject', 'content');
        try {       //throw를 던지는 경우는 try catch를 해야함.

            if (!$this->basicBoardModel->checkByNum($num)) {
                $resultData = array('result' => '404', 'message' => '', 'data' => '{"error" : {"text" : "리소스가 없습니다.."}}');
                throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            if (!$this->basicBoardModel->checkById($user['id'], $num)) {
                $resultData = array('result' => '403', 'message' => 'Not allowed', 'data' => '{"error" : {"text" : "권한이 없습니다."}}');
                throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            foreach ($key_arr as $key_name) {
                if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
                    $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
                    throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $greeting = [];
            $greeting['num'] = filter_var($num,FILTER_SANITIZE_NUMBER_INT);
            $greeting['subject'] = filter_var($data['subject'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $greeting['name'] = filter_var($user['name'], FILTER_SANITIZE_STRING);
            $greeting['is_html'] = filter_var($data['is_html'], FILTER_SANITIZE_STRING);
            $greeting['nick'] = filter_var($user['nick'], FILTER_SANITIZE_STRING);
            $greeting['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);
            if ($greeting['is_html'] == "y") {
                $greeting['content'] = filter_var($data['content'], FILTER_SANITIZE_SPECIAL_CHARS);
            } else {
                $greeting['content'] = filter_var($data['content'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); //htmlspecialchars
            }

            $data = $this->basicBoardModel->modify($greeting);

            $resultData = array('result' => '200', 'message' => 'The resource has been modified', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(SurveyException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function deleteGreeting(Request $request, Response $response, $args) {
        $num = $args['num'];
        $data = $request->getParsedBody();

        $user = $request->getAttribute('user'); //로그인 사용자정보
        $user = objectToArray($user);

        try {       //throw를 던지는 경우는 try catch를 해야함.

            if (!$this->basicBoardModel->checkByNum($num)) {
                $resultData = array('result' => '404', 'message' => '', 'data' => '{"error" : {"text" : "리소스가 없습니다.."}}');
                throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            if (!$this->basicBoardModel->checkById($user['id'], $num)) {
                $resultData = array('result' => '403', 'message' => 'Not allowed', 'data' => '{"error" : {"text" : "권한이 없습니다."}}');
                throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $greeting = [];
            $greeting['num'] = filter_var($num,FILTER_SANITIZE_NUMBER_INT);
            $greeting['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);

            $data = $this->basicBoardModel->delete($greeting);

            $resultData = array('result' => '200', 'message' => 'Resource deleted', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(SurveyException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }


}
