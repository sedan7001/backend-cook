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
use APP\Models\QnABoardModel;
use APP\Models\BasicBoardModel;
use APP\Models\MemoBoardModel;
use APP\Models\SurveyModel;
use APP\Models\AdminModel;
use APP\Controllers\Controller;
use APP\Exceptions\MemberException;
use \Firebase\JWT\JWT;



class AdminController extends Controller
{

    protected $memberModel;
    protected $aboutBoardModel;
    protected $freeBoardModel;
    protected $downloadBoardModel;
    protected $greetingBoardModel;
    protected $memoBoardModel;
    protected $qnaBoardModel;
    protected $surveyModel;
    protected $adminModel;

    public function __construct($container)
    {
        $this->memberModel = new MemberModel($container);
        $this->aboutBoardModel = new BasicBoardModel($container, 'cook_about');
        $this->downloadBoardModel = new BasicBoardModel($container, 'cook_download');
        $this->freeBoardModel = new BasicBoardModel($container, 'cook_free');
        $this->greetingBoardModel = new BasicBoardModel($container, 'cook_greet');
        $this->memoBoardModel = new MemoBoardModel($container, 'cook_memo');
        $this->qnaBoardModel = new QnABoardModel($container, 'cook_qna');
        $this->surveyModel = new SurveyModel($container, 'cook_survey');
        $this->adminModel = new AdminModel($container);
    }

    public function getMemberCount(Request $request, Response $response, $args) {

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->memberModel->getCount();
            $resultData = array('result' => '200', 'message' => 'total user count', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(AdminException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new AdminException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function getMemberList(Request $request, Response $response, $args) {

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->memberModel->getList();
            $resultData = array('result' => '200', 'message' => 'total user list data', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(AdminException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new AdminException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function getMemberJoinStat(Request $request, Response $response, $args) {

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->memberModel->getJoinStatByDay();

            $idx = 0;
            foreach($data as $row) {

                $join_count_arr[$idx] = $row->join_count_byday;
                $regist_day_arr[$idx] = $row->regist_day;
                $idx++;
            }
            $data = array();
            $data['join_count_byday'] = $join_count_arr;
            $data['regist_days'] = $regist_day_arr;

            $resultData = array('result' => '200', 'message' => 'user join count by day', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(AdminException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new AdminException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function getBoardsCount(Request $request, Response $response, $args) {

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.
            //$data[] = array();

            $data['about'] = $this->aboutBoardModel->getCount('','');
            $data['download'] = $this->downloadBoardModel->getCount('','');
            $data['free'] = $this->freeBoardModel->getCount('','');
            $data['greet'] = $this->greetingBoardModel->getCount('','');
            $data['memo'] = $this->memoBoardModel->getCount('','');
            $data['qna'] = $this->qnaBoardModel->getCount('','');

            $resultData = array('result' => '200', 'message' => 'total board count', 'data' => $data);

            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(AdminException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new AdminException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    //설문상세
    public function getSurvey(Request $request, Response $response, $args) {

        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->surveyModel->getDetail();
            if (count($data) == 0) {
                $resultData = array('result' => '404', 'message' => 'no data', 'data' => '{"error" : {"text" : "리소스가 없습니다."}}');
                throw new AdminException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
            $resultData = array('result' => '200', 'message' => 'survey data', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(AdminException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new AdminException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function modifySurvey(Request $request, Response $response, $args) {
        $data = $request->getParsedBody();

        //validation, 이미 가입된 상태 검사, 유효한 형식인지 검사, 중복된 id와 nick이 있는지 검사, 필수값 검사
        //필수값 검사
        $key_arr = array('sub1');
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
            //$survey['subject'] = filter_var($data['subject'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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

    public function getDBUsages(Request $request, Response $response, $args) {

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->adminModel->getDBUsage();

            $resultData = array('result' => '200', 'message' => 'db usage', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(AdminException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new AdminException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function getTableUsages(Request $request, Response $response, $args) {

        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->adminModel->getTableUsage();

            $resultData = array('result' => '200', 'message' => 'table usage', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(AdminException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new AdminException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

    public function getTableUsagesStat(Request $request, Response $response, $args) {

        $table_name_arr[] = array();
        $usage_arr[] = array();
        //필수값 검사
        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->adminModel->getTableUsage();

            $idx = 0;
            foreach($data as $row) {
                $table_name_arr[$idx] = $row->table_name;
                $usage_arr[$idx] = $row->KB;
                $idx++;
            }
            $data = array();
            $data['table_name'] = $table_name_arr;
            $data['usage'] = $usage_arr;

            $resultData = array('result' => '200', 'message' => 'table usage stat', 'data' => $data);
            return $response
                ->withStatus(200)
                //->getBody()
                ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }catch(AdminException $e){
            throw $e;
        }catch(Exception $e){
            //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
            $resultData = array('result' => '500', 'message' => 'error!', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
            throw new AdminException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

        }
    }

}
