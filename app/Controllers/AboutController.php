<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 10.
 * Time: PM 1:51
 */

namespace APP\Controllers;


use APP\Models\BasicBoardModel;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
//use APP\Models\BasicBoardModel;
use APP\Controllers\Controller;
use APP\Exceptions\SurveyException;
use \Lib\Pagination;


class AboutController extends Controller
{

    protected $basicBoardModel;
    public $container;
    public function __construct($container)
    {
        $this->container = $container;
        //print_r($container);
        $this->basicBoardModel = new BasicBoardModel($container, 'cook_about');
    }


    //목록
    public function getAbouts(Request $request, Response $response, $args) {


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

    //자료실상세
    public function getAbout(Request $request, Response $response, $args) {

        $num = filter_var($args['num'], FILTER_SANITIZE_NUMBER_INT);

        try {       //throw를 던지는 경우는 try catch를 해야함.

            $data = $this->basicBoardModel->getDetail($num);
            if (count($data) == 0) {
                $resultData = array('result' => '404', 'message' => 'no data', 'data' => '{"error" : {"text" : "리소스가 없습니다."}}');
                throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            }
            $resultData = array('result' => '200', 'message' => 'about data', 'data' => $data);
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

    //자료실 입력
    public function registerAbout(Request $request, Response $response, $args) {

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

            $about = [];
            $about['subject'] = filter_var($data['subject'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $about['name'] = filter_var($user['name'], FILTER_SANITIZE_STRING);
            $about['is_html'] = filter_var($data['is_html'], FILTER_SANITIZE_STRING);
            $about['nick'] = filter_var($user['nick'], FILTER_SANITIZE_STRING);
            $about['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);
            if ($about['is_html'] == "y") {
                $about['content'] = filter_var($data['content'], FILTER_SANITIZE_SPECIAL_CHARS);
            } else {
                $about['content'] = filter_var($data['content'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); //htmlspecialchars
            }

            $about['files'] = array();

            //파일업로드
            //임시 디렉토리에서 업로드한 파일정보를 가져온다.
            //파일은 반드시 업로드되어야한다.
            //업로드할 때 에러가 나타나면 400으로 클라이언트로 돌려준다.
            //파일크기가 5000000byte 이상인 경우도 400으로 클라이언트로 돌려보낸다.
            //업로드한 파일을 upload 폴더로 옮긴다. 각 게시판 폴더 아래에 위치한다.
            //업로드한 파일은 slim에 있는 함수를 적용해서 이름붙인다.
            $directory = $this->container->get('uploads'); ///uploads/about
            $upload_errors = $this->container->get('upload_errors');
            $uploadedFiles = $request->getUploadedFiles();

            $image_type_array = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/bmp', 'image/png');

            foreach ($uploadedFiles['upfile'] as $upfile) { ///private/var/tmp/phpkAnv90
                //return $response->getBody()->write(var_dump($upfile));

                if (is_string($upfile)) {
                    $resultData = array('result' => '400', 'message' => '', 'data' => '{"error" : {"text" : "파일명을 upfile[] 로 업로드해주세요."}}');
                    throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }

                if ($upfile->getError() === UPLOAD_ERR_OK) { //https://securr/features.file-upload.errors.php
                    //$size = getimagesize($upfile); //파일경로까지 존재해야함...이건 안됨
                    //$mime_type = $size['mime'];
                    $mime_type = $upfile->getClientMediaType();
                    $org_filename = $upfile->getClientFilename();
                    $filename = moveUploadedFile($directory, 'about', $upfile);
                    $file_size = $upfile->getSize();

                    if (!in_array(strtolower($mime_type), $image_type_array)) {
                        $resultData = array('result' => '415', 'message' => '', 'data' => '{"error" : {"text" : "JPG,BMP,PNG,GIF 이미지 파일만 업로드 가능합니다!"}}');
                        throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
                    }
                    if ($file_size > 100000000) {
                        $resultData = array('result' => '413', 'message' => '', 'data' => '{"error" : {"text" : "업로드된 파일크기가 5Mbyte를 넘습니다.}}');
                        throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    }
                    $file_infos = array("org_filename" => $org_filename
                    , "filename" => $filename
                    , "mime_type" => $mime_type
                    , "file_size" => $file_size
                    , "b_type"=> "cook_about"
                    , "id" => $user['id']
                    , "is_del"=>"N");
                    array_push($about['files'], $file_infos);
                } else {
                    $upload_error_code = $upfile->getError();
                    //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
                    $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : ' . in_array($upload_error_code, $upload_errors) . '}}');
                    throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }

            $data = $this->basicBoardModel->register($about);

            $resultData = array('result' => '201', 'message' => 'Resource created', 'data' => $data);
            return $response
                ->withHeader('Location','/cook/api/abouts')
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

    public function modifyAbout(Request $request, Response $response, $args) {
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

            $about = [];
            $about['num'] = filter_var($num,FILTER_SANITIZE_NUMBER_INT);
            $about['subject'] = filter_var($data['subject'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $about['name'] = filter_var($user['name'], FILTER_SANITIZE_STRING);
            $about['is_html'] = filter_var($data['is_html'], FILTER_SANITIZE_STRING);
            $about['nick'] = filter_var($user['nick'], FILTER_SANITIZE_STRING);
            $about['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);
            if ($about['is_html'] == "y") {
                $about['content'] = filter_var($data['content'], FILTER_SANITIZE_SPECIAL_CHARS);
            } else {
                $about['content'] = filter_var($data['content'], FILTER_SANITIZE_FULL_SPECIAL_CHARS); //htmlspecialchars
            }

            $about['files'] = array();
            for ($i=0 ; $i < count($data['file_num']) ; $i++) {
                $file_num =filter_var($data['file_num'][$i], FILTER_SANITIZE_NUMBER_INT);
                $is_del =filter_var($data['is_del'][$i], FILTER_SANITIZE_STRING);
                $file_infos = array("num"=>$file_num
                ,"b_type"=> "cook_about"
                , "b_num" => $about['num']
                , "is_del"=>$is_del);
                array_push($about['files'], $file_infos);
            }

            //파일업로드
            //임시 디렉토리에서 업로드한 파일정보를 가져온다.
            //파일은 반드시 업로드되어야한다.
            //업로드할 때 에러가 나타나면 400으로 클라이언트로 돌려준다.
            //파일크기가 5000000byte 이상인 경우도 400으로 클라이언트로 돌려보낸다.
            //업로드한 파일을 upload 폴더로 옮긴다. 각 게시판 폴더 아래에 위치한다.
            //업로드한 파일은 slim에 있는 함수를 적용해서 이름붙인다.
            $directory = $this->container->get('uploads'); ///uploads/about
            $upload_errors = $this->container->get('upload_errors');
            $uploadedFiles = $request->getUploadedFiles();

            $image_type_array = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/bmp', 'image/png');
            foreach ($uploadedFiles['upfile'] as $upfile) { ///private/var/tmp/phpkAnv90
                //return $response->getBody()->write(var_dump($upfile));

                if (is_string($upfile)) {
                    $resultData = array('result' => '400', 'message' => '', 'data' => '{"error" : {"text" : "파일명을 upfile[] 로 업로드해주세요."}}');
                    throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }

                if ($upfile->getError() === UPLOAD_ERR_OK) { //https://securr/features.file-upload.errors.php
                    //$size = getimagesize($upfile); //파일경로까지 존재해야함...이건 안됨
                    //$mime_type = $size['mime'];
                    $mime_type = $upfile->getClientMediaType();
                    $org_filename = $upfile->getClientFilename();
                    $filename = moveUploadedFile($directory, 'about', $upfile);
                    $file_size = $upfile->getSize();

                    if (!in_array(strtolower($mime_type), $image_type_array)) {
                        $resultData = array('result' => '415', 'message' => '', 'data' => '{"error" : {"text" : "JPG,BMP,PNG,GIF 이미지 파일만 업로드 가능합니다!"}}');
                        throw new SurveyException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
                    }
                    if ($file_size > 100000000) {
                        $resultData = array('result' => '413', 'message' => '', 'data' => '{"error" : {"text" : "업로드된 파일크기가 5Mbyte를 넘습니다.}}');
                        throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    }
                    $file_infos = array("org_filename" => $org_filename
                    , "filename" => $filename
                    , "mime_type" => $mime_type
                    , "file_size" => $file_size
                    , "b_type"=> "cook_about"
                    , "b_num" => $num
                    , "id" => $user['id']
                    , "regist_day"=>date("Y-m-d(H:i)")
                    , "is_del"=>"N");
                    array_push($about['files'], $file_infos);
                } else {
                    $upload_error_code = $upfile->getError();
                    //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
                    $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : ' . in_array($upload_error_code, $upload_errors) . '}}');
                    throw new SurveyException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
            }


            $data = $this->basicBoardModel->modify($about);

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

    public function deleteAbout(Request $request, Response $response, $args) {
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

            $about = [];
            $about['num'] = filter_var($num,FILTER_SANITIZE_NUMBER_INT);
            $about['id'] = filter_var($user['id'], FILTER_SANITIZE_STRING);

            $data = $this->basicBoardModel->delete($about);

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
