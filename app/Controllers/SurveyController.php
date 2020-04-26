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
use APP\Models\SurveyModel;
use APP\Controllers\Controller;
use APP\Exceptions\SurveyException;
use \Lib\Pagination;


class SurveyController extends Controller
{

    protected $surveyModel;
    public $container;
    public function __construct($container)
    {
        $this->container = $container;
        $this->surveyModel = new SurveyModel($container, 'cook_survey');
    }

    //설문상세
    public function getSurvey(Request $request, Response $response, $args) {

        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->surveyModel->getDetail();
            if (count($data) == 0) {
                $resultData = array('result' => '404', 'message' => 'no data', 'data' => '{"error" : {"text" : "리소스가 없습니다."}}');
                throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
            $resultData = array('result' => '200', 'message' => 'survey data', 'data' => $data);
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

    //설문 입력
    public function registerSurvey(Request $request, Response $response, $args) {

        $data = $request->getParsedBody();

        $user = $request->getAttribute('user'); //로그인 사용자정보
        $user = objectToArray($user);

        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('subject', 'sub1');
        try {       //throw를 던지는 경우는 try catch를 해야함.

            if (count($this->surveyModel->getDetail()) > 0) {
                $resultData = array('result' => '400', 'message' => 'Resource already exists', 'data' => '{"error" : {"text" : "설문데이타가 있습니다. 삭제 후, 입력바랍니다."}}');
                throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
            foreach ($key_arr as $key_name) {
                if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
                    $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
                    throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $survey = [];
            $survey['subject'] = filter_var($data['subject'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $survey['sub1'] = filter_var($data['sub1'], FILTER_SANITIZE_STRING);
            $survey['sub2'] = filter_var($data['sub2'], FILTER_SANITIZE_STRING);
            $survey['sub3'] = filter_var($data['sub3'], FILTER_SANITIZE_STRING);
            $survey['sub4'] = filter_var($data['sub4'], FILTER_SANITIZE_STRING);

            $data = $this->surveyModel->register($survey);

            $resultData = array('result' => '201', 'message' => 'Resource created', 'data' => $data);
            return $response
                ->withHeader('Location','/cook/api/survey')
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

    //설문 입력
    public function updateAnswer(Request $request, Response $response, $args) {

        $data = $request->getParsedBody();

        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('ans');
        try {       //throw를 던지는 경우는 try catch를 해야함.
            foreach ($key_arr as $key_name) {
                if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
                    $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
                    throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $data = $this->surveyModel->updateAnswer($data['ans']);

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

        //return
    }

    public function modifySurvey(Request $request, Response $response, $args) {
        $data = $request->getParsedBody();

        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('subject', 'sub1');
        try {       //throw를 던지는 경우는 try catch를 해야함.

            if (!$this->surveyModel->getDetail()) {
                $resultData = array('result' => '404', 'message' => '', 'data' => '{"error" : {"text" : "리소스가 없습니다.."}}');
                throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            foreach ($key_arr as $key_name) {
                if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
                    $resultData = array('result' => '400', 'message' => 'parameter is empty', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
                    throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $survey = [];
            $survey['subject'] = filter_var($data['subject'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $survey['sub1'] = filter_var($data['sub1'], FILTER_SANITIZE_STRING);
            $survey['sub2'] = filter_var($data['sub2'], FILTER_SANITIZE_STRING);
            $survey['sub3'] = filter_var($data['sub3'], FILTER_SANITIZE_STRING);
            $survey['sub4'] = filter_var($data['sub4'], FILTER_SANITIZE_STRING);

            $data = $this->surveyModel->modify($survey);

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

    public function deleteSurvey(Request $request, Response $response, $args) {

        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->surveyModel->delete();

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
