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
use APP\Models\MemberModel;
use APP\Controllers\Controller;
use APP\Exceptions\MemberException;
use \Firebase\JWT\JWT;



class MemberController extends Controller
{

    protected $memberModel;

    public function __construct($container)
    {
        //$container = $container;
        //print_r($container);
        $this->memberModel = new MemberModel($container);
    }

    public function registerMember(Request $request, Response $response, $args) {

        $data = $request->getParsedBody();
        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('id', 'password', 'name', 'nick', 'hp');
        try {       //throw를 던지는 경우는 try catch를 해야함.
            foreach ($key_arr as $key_name) {
                if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
                    $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
                    throw new MemberException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $member = [];
            $member['id'] = filter_var($data['id'], FILTER_SANITIZE_STRING);
            $member['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING); //정규식 적용해야함. 나중
            $member['name'] = filter_var($data['name'], FILTER_SANITIZE_STRING);
            $member['nick'] = filter_var($data['nick'], FILTER_SANITIZE_STRING);
            $member['hp'] = filter_var($data['hp'], FILTER_SANITIZE_STRING);
            $member['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

            $data = $this->memberModel->registerMember($member);

            $resultData = array('result' => '201', 'message' => '', 'data' => $data);
            return $response
                ->withHeader('Location','/cook/api/signin')
                ->withStatus(201)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(MemberException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new MemberException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }

        //return
    }

    public function modifyMember(Request $request, Response $response, $args) {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user'); //로그인 사용자정보
        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('password', 'name', 'nick', 'hp');
        try {       //throw를 던지는 경우는 try catch를 해야함.
            foreach ($key_arr as $key_name) {
                if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
                    $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
                    throw new MemberException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $member = [];
            $member['num'] = filter_var($user->num, FILTER_SANITIZE_NUMBER_INT);
            $member['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING); //정규식 적용해야함. 나중
            $member['name'] = filter_var($data['name'], FILTER_SANITIZE_STRING);
            $member['nick'] = filter_var($data['nick'], FILTER_SANITIZE_STRING);
            $member['hp'] = filter_var($data['hp'], FILTER_SANITIZE_STRING);
            $member['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

            $data = $this->memberModel->modifyMember($member);

            $resultData = array('result' => '200', 'message' => '', 'data' => $data);
            return $response
                ->withStatus(200)
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(MemberException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new MemberException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function getMember(Request $request, Response $response, $args) {

        $user = $request->getAttribute('user');

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $num = filter_var($user->num, FILTER_SANITIZE_NUMBER_INT);

            $data = $this->memberModel->getMember($user);
            $resultData = array('result' => '200', 'message' => 'user data', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(MemberException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new MemberException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function getList(Request $request, Response $response, $args) {

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->memberModel->getList();
            $resultData = array('result' => '200', 'message' => 'user data', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(MemberException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new MemberException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function getCount(Request $request, Response $response, $args) {

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->memberModel->getCount();
            $resultData = array('result' => '200', 'message' => 'user data', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(MemberException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new MemberException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function checkMemberById(Request $request, Response $response, $args) {

        $id = $request->getParam('q');

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.
            if (empty($id)) {
                $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : "id 의 값이 없습니다."}}');
                throw new MemberException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $id = filter_var($id, FILTER_SANITIZE_STRING);

            $check = $this->memberModel->checkMemberById($id);
            if ($check) {
                $data['check'] = true;
                $resultData = array('result' => '200', 'message' => 'ID available', 'data' => $data);
                return $response
                    ->withStatus(200)
                    //->getBody()
                    ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            } else {
                $data['check'] = false;
                $resultData = array('result' => '200', 'message' => 'ID Not available', 'data' => $data);
                throw new MemberException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }

        }catch(MemberException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new MemberException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function checkMemberByNick(Request $request, Response $response, $args) {

        $nick = $request->getParam('q');

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.
            if (empty($nick)) {
                $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : "nick 의 값이 없습니다."}}');
                throw new MemberException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $nick = filter_var($nick, FILTER_SANITIZE_STRING);

            $check = $this->memberModel->checkMemberByNick($nick);
            if ($check) {
                $data['check'] = true;
                $resultData = array('result' => '200', 'message' => 'Nickname available', 'data' => $data);
                return $response
                    ->withStatus(200)
                    //->getBody()
                    ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            } else {
                $data['check'] = false;
                $resultData = array('result' => '200', 'message' => 'Nickname Not available', 'data' => $data);
                throw new MemberException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }

        }catch(MemberException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new MemberException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

}
