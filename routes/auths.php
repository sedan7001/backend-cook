<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Firebase\JWT\JWT;
use Tuupola\Base62;

// insert memo

//$app->options('/api/siginin', function(Request $request, Response $response) {
//    $token = str_replace('Bearer ', '', $request->getHeader('Authorization'));
//    //$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzM4NCJ9.eyJpYXQiOjE1MzA1NjgwMTMsImV4cCI6MTUzMDU2ODMxMywianRpIjoiNENPQXpuTWdQTXlhQTBNd2ZWTHUiLCJ1c2VyaWQiOiJzZWRhbiIsInNjb3BlIjpbInJlYWQiLCJ3cml0ZSIsImRlbGV0ZSJdLCJpc3MiOiJjb29rLmNvbSJ9.DBYE08LR0f1aDSThCTg1CIfH2-z4EbF4hYJL_dgxfPXk9uNfrveBh1yNY3IzlVd1';
//    //$token = $request->getAttribute('decoded_token_data');
//    //$alg_array =
//    //$token = $request->getAttribute('decoded_token_data');
//    //print_r($token[0]);
//    //exit;
//    //exit;
//    //배열이 된 이유는?
//    //만료되면 $decode에 값이 없음
//    $decoded = JWT::decode(
//        $token[0],
//        $this->jwtKey['secret'],
//        (array)"HS384"
//    );
//    $data = $request->getParsedBody();
//
//    if (empty($decoded)) {
//        if (isset($_SESSION["refresh_token"])) {
//
//        }
//    }
//
//    //return (array) $decoded;
//    //$user = objectToArray($token['user']);
//    print_r($decoded);
//
//});

$app->post('/api/signout' , function(Request $request , Response $response) {
    $_SESSION['refresh_token'] = null;
    session_destroy();

    $resultData = array('result' => '205', 'message' => '', 'data' => '로그아웃되었습니다.');
    //print_r($resultData);
    return $response
        ->withHeader('Location','/cook/api/')
        ->withStatus(205) //204로 하면 body가 넘어가지 않음, nocontent, 205 Reset Content
        //->getBody()
        ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
});

$app->post('/api/signin' , function(Request $request , Response $response) {
    //echo 'CUSTOMERS';

    // GET DB Object
    $db = new \Lib\db();
    // Connect
    $db = $db->connect();


    //필수필드조사
    //업로드가 안되는 경우에는 폼데이타도 넘어오지 않아서 여기서부터 오류가 발생함.
    $data = $request->getParsedBody(); //application/x-www-form-urlencoded, multipart/form-data, post만 됨...
    //$content = (array)json_decode($data->getContents();
    $key_arr = array('id', 'password');
    foreach ($key_arr as $key_name) {
        if (!array_key_exists($key_name, $data) || empty($data[$key_name])) {
            $resultData = array('result' => '400', 'message' => '', 'data' => '{"error" : {"text" : ' . $key_name . '"의 값이 없습니다."}}');
            throw new CookException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    $signin = [];
    $signin['id'] = filter_var($data['id'], FILTER_SANITIZE_STRING); //빈 값이 db에 적용되기 시작해서 필드 유효성 검사 적용
    $signin['password'] = filter_var($data['password'], FILTER_SANITIZE_STRING);

    $sql = "SELECT * FROM cook_member where id = :id";

    try{

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id',$signin['id'], PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetchObject();


        // verify email address.
        if(!$user) {
            $resultData = array('result' => '404', 'message' => 'user_not_found', 'data' => '사용자 정보가 없습니다.');
            throw new CookException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }

        // verify password.
        //if (!password_verify($input['password'],$user->password)) {
        if ($data['password'] !== $user->password) {
            $resultData = array('result' => '404', 'message' => 'user_not_found', 'data' => '사용자 정보가 없습니다.');
            throw new CookException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        }

        $settings = $this->get('jwtKey'); // get settings array.

        /* Here generate and return JWT to the client. */
        //$valid_scopes = [“read”, “write”, “delete”]
        //$requested_scopes = $request->getParsedBody() ?: [];
        //jwt token 생성
        $now = new DateTime();
        $term = new DateInterval('PT5M'); //5miniuts
        $future = (new DateTime())->add($term);
        $term = new DateInterval('P1D'); //1day, refresh
        $future2 = (new DateTime())->add($term);
        $server = $request->getServerParams();
        $jti = (new Base62)->encode(random_bytes(16));

        $user_ip = $_SERVER[“REMOTE_ADDR”];
        $refresh_payload = array (
            'ip' => $user_ip,
            'exp' => $future2->getTimeStamp(),
            'iat' => $now->getTimeStamp(),
            //'jti' => $jti,
            'num' => $user->num,
            //“sub” => $server[“PHP_AUTH_USER”]
            //“sub” => $user
        );

        $payload = array(
            'iss' => 'http://cook.com',
            'iat' => $now->getTimeStamp(),
            'nbf' => $future->getTimeStamp(),
            'jti' => $jti,
            'exp' => $future->getTimeStamp(),
            'user' => array(
                'id' => $user->id,
                'name' => $user->name,
                'nick' => $user->nick,
                'level' => $user->level
            )
        );

        //print_r($payload);
        //exit;
        //refresh T. key 생성
        // jwt token을 생성할 때, 배열을 미리 만들어서 넣는 건 안되는 듯...
        $ref_token = rsa_encrypt(json_encode($refresh_payload), $this->get('rsa_keys')['public_key']);
        //$secret = “123456789helo_secret”;
        //var_dump($now);
        //var_dump($future);
        //$token = JWT::encode($payload, $settings['secret'], “HS384”); // jwt token을 생성할 때, 배열을 미리 만들어서 넣는 건 안되는 듯...
        $token = JWT::encode(['iat' => $now->getTimeStamp(),
                                'exp' => $future->getTimeStamp(),
                                'jti' => $jti,
                                'scope' => ['read', 'write', 'delete'],
                                'iss' => 'cook.com',
                                'user' => array(
                                    'num' => $user->num,
                                    'id' => $user->id,
                                    'name' => $user->name,
                                    'nick' => $user->nick,
                                    'level' => $user->level
                                )
                            ]
                        , $settings['secret'], "HS384"); //exp 이후에는 인증이 끊김.
        $result['token'] = $token;
        $result['expires'] = $future->getTimeStamp();
        $result['auth_type'] = "bearer";
        $result['refresh_token'] = $ref_token ;
        $result['user'] = $user ;

        //$token = JWT::encode(['id' => $user->id, 'email' => $user->email, 'regist_day' => $user->regist_day], $settings['secret'], "HS256");


        //return $this->response->withJson(['token' => $token]);
        $_SESSION['refresh_token'] = $ref_token;
        $db = null;
        $resultData = array('result' => '200', 'message' => '', 'data' => $result);
        return $response
            ->withHeader('Location','/cook/api/abouts')
            ->withStatus(200)
            //->getBody()
            ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

    }catch(CookException $e){
        throw $e;
    }catch(Exception $e){
        //$data = array('result' => '500', 'message' => 'Exception!', 'data' => '');
        $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : '.$e->getMessage().'}}');
        throw new CookException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

    }
});

?>