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
use App\Models\BasicRippleModel;
use APP\Models\QnABoardModel;
use APP\Models\MemoBoardModel;
use APP\Controllers\Controller;
use APP\Exceptions\BasicBoardException;
use \Lib\Pagination;


class MemoController extends Controller
{

    protected $memoBoardModel;
    protected $basicRippleModel;
    public $container;
    public function __construct($container)
    {
        $this->container = $container;
        //print_r($container);
        $this->memoBoardModel = new MemoBoardModel($container, 'cook_memo');
        $this->basicRippleModel = new BasicRippleModel($container, 'cook_memo');
    }


    //목록
    public function getMemos(Request $request, Response $response, $args) {


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
                throw new BasicBoardException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $type = !empty($str_type) ? $key_arr[$str_type] : '';
            }
            $count = $this->memoBoardModel->getCount($type, $str);
            $pagination = new Pagination($request, $count, $page);
            $data = $pagination->getPagination();
            $data['q'] = $qs;
            //var_dump($this->memoModel->getList($type, $str, $page));
            $data['list'] = $this->memoBoardModel->getList($type, $str, $page);
            if (count($data) == 0) {
                $resultData = array('result' => '404', 'message' => 'no data', 'data' => '{"error" : {"text" : "리소스가 없습니다."}}');
                throw new BasicBoardException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }


            foreach ($data['list'] as $memo) { //$memo는 object임..
                //var_dump($memo);
                //$this->container->logger->addDebug(($this->basicRippleModel->getList($memo->num)));
                $memo->ripples = $this->basicRippleModel->getList($memo->num);
                //$data['ripples'] = $this->basicRippleModel->getList($memo->num);
            }
            $resultData = array('result' => '200', 'message' => 'memo list data', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(BasicBoardException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new BasicBoardException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }

    }

    //자료실상세
    public function getMemoRipples(Request $request, Response $response, $args) {

        $num = filter_var($args['num'], FILTER_SANITIZE_NUMBER_INT);

        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->basicRippleModel->getList($num);
            $resultData = array('result' => '200', 'message' => 'memo ripple data', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(BasicBoardException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new BasicBoardException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }


    //낙서장 입력
    public function registerMemo(Request $request, Response $response, $args) {

        $data = $request->getParsedBody();

        $user = $request->getAttribute('user'); //로그인 사용자정보
        $user = objectToArray($user);

        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('content');
        try {       //throw를 던지는 경우는 try catch를 해야함.
            foreach ($key_arr as $key_name) {
                if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
                    $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
                    throw new BasicBoardException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $memo = [];
            $memo['name'] = filter_var($user['name'], FILTER_SANITIZE_STRING);
            $memo['nick'] = filter_var($user['nick'], FILTER_SANITIZE_STRING);
            $memo['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);
            $memo['content'] = filter_var($data['content'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); //htmlspecialchars

            $data = $this->memoBoardModel->register($memo);

            $resultData = array('result' => '201', 'message' => 'Resource created', 'data' => $data);
            return $response
                ->withHeader('Location','/cook/api/memos')
                ->withStatus(201)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(BasicBoardException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new BasicBoardException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }

        //return
    }

    public function deleteMemo(Request $request, Response $response, $args) {
        $num = $args['num'];
        $data = $request->getParsedBody();

        $user = $request->getAttribute('user'); //로그인 사용자정보
        $user = objectToArray($user);

        try {       //throw를 던지는 경우는 try catch를 해야함.
            if (!$this->memoBoardModel->checkByNum($num)) {
                $resultData = array('result' => '404', 'message' => '', 'data' => '{"error" : {"text" : "리소스가 없습니다.."}}');
                throw new BasicBoardException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            if (!$this->memoBoardModel->checkById($user['id'], $num)) {
                $resultData = array('result' => '403', 'message' => 'Not allowed', 'data' => '{"error" : {"text" : "권한이 없습니다."}}');
                throw new BasicBoardException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $memo = [];
            $memo['num'] = filter_var($num,FILTER_SANITIZE_NUMBER_INT);
            $memo['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);

            //게시글이 지워져야 댓글도 지워지게....
            if ($this->memoBoardModel->delete($memo)) {
                $data = $this->basicRippleModel->deleteByParent($memo);
            }

            $resultData = array('result' => '200', 'message' => 'Resource deleted', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(BasicBoardException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new BasicBoardException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    //낙서장 입력
    public function registerMemoRipple(Request $request, Response $response, $args) {

        $parent = $args['num'];
        $data = $request->getParsedBody();

        $user = $request->getAttribute('user'); //로그인 사용자정보
        $user = objectToArray($user);

        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('content');
        try {       //throw를 던지는 경우는 try catch를 해야함.
            foreach ($key_arr as $key_name) {
                if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
                    $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
                    throw new BasicBoardException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $ripple = [];
            $ripple['parent'] = filter_var($parent, FILTER_SANITIZE_NUMBER_INT);
            $ripple['name'] = filter_var($user['name'], FILTER_SANITIZE_STRING);
            $ripple['nick'] = filter_var($user['nick'], FILTER_SANITIZE_STRING);
            $ripple['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);
            $ripple['content'] = filter_var($data['content'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); //htmlspecialchars

            $data = $this->basicRippleModel->register($ripple);

            $resultData = array('result' => '201', 'message' => 'Resource created', 'data' => $data);
            return $response
                ->withHeader('Location', '/cook/api/memos/'.$parent)
                ->withStatus(201)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(BasicBoardException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new BasicBoardException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }

        //return
    }

    public function deleteMemoRipple(Request $request, Response $response, $args) {
        $parent = $args['num'];
        $num = $args['r_num'];

        //var_dump($num);
        $data = $request->getParsedBody();

        $user = $request->getAttribute('user'); //로그인 사용자정보
        $user = objectToArray($user);

        try {       //throw를 던지는 경우는 try catch를 해야함.
            if (!$this->basicRippleModel->checkByNum($num, $parent)) {
                $resultData = array('result' => '404', 'message' => '', 'data' => '{"error" : {"text" : "리소스가 없습니다.."}}');
                throw new BasicBoardException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            if (!$this->basicRippleModel->checkById($user['id'], $num, $parent)) {
                $resultData = array('result' => '403', 'message' => 'Not allowed', 'data' => '{"error" : {"text" : "권한이 없습니다."}}');
                throw new BasicBoardException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $memo = [];
            $memo['num'] = filter_var($num,FILTER_SANITIZE_NUMBER_INT);
            $memo['parent'] = filter_var($parent,FILTER_SANITIZE_NUMBER_INT);
            $memo['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);

            $data = $this->basicRippleModel->delete($memo);

            $resultData = array('result' => '200', 'message' => 'Resource deleted', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(BasicBoardException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new BasicBoardException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }
}
