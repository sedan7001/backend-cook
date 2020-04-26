<?php
//header("Access-Control-Allow-Origin: *");
//header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
//header("Access-Control-Max-Age: 3600");
//header("Access-Control-Allow-Headers: Origin,Accept,X-Requested-With,Content-Type,Access-Control-Request-Method,Access-Control-Request-Headers,Authorization");

require './vendor/autoload.php';
require 'lib/db.php';
require 'lib/rsa_encrypt.php';
//require 'IndexController.php';
require 'app/Controllers/Controller.php';
require 'app/Models/Model.php';
require 'lib/Pagination.php';


use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Http\UploadedFile;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use \Firebase\JWT\JWT;
use Tuupola\Base62;
use App\Exceptions\MemberException;
use App\Exceptions\SurveyException;
//use \Index\IndexController;
//use \App\Controller\HomeController;
/*
$config['db']['host']   = 'localhost';
$config['db']['user']   = 'user';
$config['db']['pass']   = 'password';
$config['db']['dbname'] = 'exampleapp';
*/

error_reporting(E_ERROR);   //에러 표시 레벨, 에러만..

//$config['displayErrorDetails'] = true; //오류 표시
//$config['addContentLengthHeader'] = false;

$config = [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
//        'debug' => true,

        'db' => [
            'host' => 'localhost',
            'dbname' => '1234',
            'user' => '1234',
            'password' => '1234',
        ],
        'logger' => [
            'name' => 'slim-app',
            'level' => Monolog\Logger::ERROR,
            'path' => __DIR__ . './logs/app.log',
        ],
    ],
];

//$app = new \Slim\App;
$container = new \Slim\Container($config); //container : DI 추가(pre)
//$app = new \Slim\App(['settings' => $config]);  //설정을 사용가능하도록 한다.
//$container = $app->getContainer();  //container : DI 추가(post)
//$container = new \Slim\Container();

$container['db'] = function ($container) {
    $db = $container['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$container['temp_uploads'] = __DIR__ . '/temp_uploads';
$container['uploads'] = __DIR__ . '/uploads';
$container['jwtKey'] = ['secret' => 'f8dsayhfiov213fdsa'];
$container['refKey'] = ['secret' => 'fh9bv08hj32hj9ffd'];
$container['jwt'] = function($container) {
    return new stdClass();
};
$container['rsa_keys'] = function($container) {
    return rsa_generate_keys($container['refKey']['secret']);
};

$container['upload_errors'] = array(
    0 => '오류 없이 파일 업로드가 성공했습니다.',
    1 => '업로드한 파일이 php.ini upload_max_filesize 지시어보다 큽니다.',
    2 => '업로드한 파일이 HTML 폼에서 지정한 MAX_FILE_SIZE 지시어보다 큽니다.',
    3 => '파일이 일부분만 전송되었습니다.',
    4 => '파일이 전송되지 않았습니다.',
    6 => '임시 폴더가 없습니다.',
    7 => '디스크에 파일 쓰기를 실패했습니다.',
    8 => '확장에 의해 파일 업로드가 중지되었습니다.',
);  //https://secure.php.net/manual/kr/features.file-upload.errors.php

$container['logger'] = function($container) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('./logs/app.log', Logger::DEBUG);
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['errorHandler'] = function ($container) {
    return new CookExceptionHandler($container->logger);
};

//Override the default Not Found Handler
$container['notFoundHandler'] = function ($container) {     //function($)c로 해도 재정의가 됨...
    return function ($request, $response) use ($container) {
        $resultData = array('result' => '404', 'message' => 'Not Found', 'data' => '{"error" : {"text" : "리소스가 존재하지 않습니다."}}');
        return $container['response']
            ->withStatus(404, 'Not Found')
            ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    };
};
//cors에서 정의되지 않은 메소드는 500에러로 처리됨...
$container['notAllowedHandler'] = function ($container) {
    return function ($request, $response, $methods) use ($container) {
        $resultData = array('result' => '405'
                    , 'message' => 'Method Not Allowed'
                    , 'data' => '{"error" : {"text" : "허용되지 않은 접근입니다. 접근 메소드는 '. implode(',', $methods) . '중 하나여야 합니다."}}');
        return $container['response']
            ->withStatus(405, 'Method Not Allowed')
            ->withHeader('Allow', implode(', ', $methods))
            //->withHeader('Content-type', 'text/html')
            ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    };
};

//정확히 무얼 의미하는지? 인터프리터 문제인지? php 7만 가능하다함.
//https://secure.php.net/manual/en/class.error.php
//이걸로 로그저장이 가능할 듯...
//PHP Fatal error:  Uncaught Error 는 잡히지 않낭?
$container['phpErrorHandler'] = function ($container) {
    return new CookExceptionHandler($container->logger);
//    return function ($request, $response, $error) use ($container) {
//        //var_dump($error);
//        $resultData = array('result' => '500'
//        , 'message' => 'Internal Server Error'
//        , 'data' => '{"error" : {"text" : "['. $error->getCode() .']'. $error->getMessage() . '"}}');
//        return $container['response']
//            ->withStatus(500, 'Internal Server Error')
//            ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
//    };
};

$container['jwtAuth'] = function ($container) {
    return new Tuupola\Middleware\JwtAuthentication ([
    //"path" => "/api", /* or ["/api", "/admin"] */
    //"ignore" => ["/api/signin"],
    "secret" => $container->get('jwtKey')['secret'],
    "attribute" => "decoded_token_data",
    "logger" => $container['logger'],
    "error" => function ($response, $exception)  {
        //refresh 토큰 처리
        //print_r($response);
        //print_r($exception);
        //return $this->refreshAuth;
        return $response->withStatus(200);

        //$resultData = array('result' => '401', 'message' => '', 'data' => '{"error" : {"text" : "인증되지 않았습니다."}}');
        //throw new CookException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    },
    "secure" => true,
    "algorithm" => ["HS256", "HS384"],
    "relaxed" => ["localhost"], //https 체크, 개발인 경우에는 아이피에도 적용하기 위해.
    "rules" => [
        new Tuupola\Middleware\JwtAuthentication\RequestPathRule([
            "path" => "/api/abouts",
            "ignore" => ["/api/signin", "/api/signout"]
        ]),
        new Tuupola\Middleware\JwtAuthentication\RequestMethodRule([
            "ignore" => ["OPTIONS"]
        ])
    ],
    'callback' => function ($request, $response, $arguments) use ($container) {
        $container['jwt'] = $arguments['decoded'];
        //$container['user'] = $decode_data['user'];
    },
]);
};

//$app->add( function ( $Req ,$Res ,$next ){
//    //get token,username from the user
//    $token= $Req->getParsedBodyParam('token');
//    $user_name=$Req->getParsedBodyParam('username');
//    //check for empty of any of them
//    if(empty ($token)|| empty($user_name)  ){
//        $message=array("success"=>false,'message'=>'Some data is empty');
//        return $Res->withStatus(401)
//            -> withJson($message);
//    }
//    else{
//
//        //Validation test for the taken for this user name
//        $decoded_token = $this->JWT::decode($token, 'YourSecret key', array('HS256'));
//        if( isset($decoded_token->data->userName) && $decoded_token->data->userName == $user_Name ){
//            $message=array('message'=>'the token is valid');
////pass through the next API
//            $Res=$next($Req,$Res->withJson($message));
//            return $Res;
//        }
//        else{
//            $message=array("sccess"=>false,"message"=>"Token Validation Error",'code'=>201);
//            return $Res->withStatus(401)
//                ->withJson($message);
//        }
//    }
//});

//refreshtoken, AuthExcepion도 새로 만들어야함.
$container['refreshAuth'] = function ($container) {
    return function ($request, $response, $next) use ($container) {
        $token = str_replace('Bearer ', '', $request->getHeader('Authorization'));


        $data = $request->getParsedBody(); //왜인지 몰라도, postman에서 put으로 받으면 인식을 못함._METHOD로 받아야함...
        $refresh_token = $data['refresh_token'];
        //$refresh_token = $request->getHeader('refresh_token');
        //$headers = apache_request_headers();
//        print_r(apache_request_headers());
//        print_r($headers['refresh_token']);
        //$refresh_token = $headers['refresh_token'];
        //print_r(message->getheaders()); //다 못가져움...흠...

        //var_dump($_SESSION);

        if (!isset($_SESSION["refresh_token"]) or empty($refresh_token)) {
            $resultData = array('result' => '401', 'message' => 'token_not_provided', 'data' => '{"error" : {"text" : "refresh token이 필요합니다."}}');
            throw new CookException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        //실패하면 false, 왜인지 decrypt가 안되므로...이건 나중에. 그러나, 로그인한 사람의 refresh key로 다른 사람의 정보에 접근가능하게 되므로 해야하는 일임
        //근데 너무 느려서...다른 암호화 모듈을 찾아야될지도...
//        $refresh_payload = rsa_decrypt($_SESSION["refresh_token"], $container['rsa_keys']['private_key'], $container['refKey']['secret']);
//        var_dump($refresh_payload);
//        if (!$refresh_payload) {
//            $resultData = array('result' => '401', 'message' => 'refresh token is not valid', 'data' => '{"error" : {"text" : "refresh token이 필요합니다."}}');
//            throw new CookException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//        } else {
//
//        }

        //exit;

        $signin = [];
        $signin['num'] = filter_var($data['num'], FILTER_SANITIZE_STRING); //빈 값이 db에 적용되기 시작해서 필드 유효성 검사 적용

        try {
            $decoded = JWT::decode(
                $token[0],
                $this->jwtKey['secret'],
                (array)"HS384"
            );

            $request = $request->withAttribute('user', $decoded->user);
            //print_r($decoded->user->num);
            if (empty($signin['num']) or ($decoded->user->num !== $signin['num']) ) {
                $resultData = array('result' => '401', 'message' => 'the token is not valid', 'data' => '{"error" : {"text" : "token이 유효하지 않습니다."}}');
                throw new CookException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

        } catch(Exception $e) {
            //print_r($e);
            if ($_SESSION["refresh_token"] == $refresh_token) {
                //토큰 새로 생성
                // GET DB Object
                $db = new \Lib\db();
                // Connect
                $db = $db->connect();

                $sql = "SELECT * FROM cook_member where num = :num";

                $stmt = $db->prepare($sql);
                $stmt->bindParam(':num', $signin['num'], PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetchObject();

                $db = null;
                // verify email address.
                if (!$user) {
                    session_destroy();
                    $resultData = array('result' => '404', 'message' => 'user_not_found', 'data' => '사용자 정보가 없습니다.인증이 필요합니다.');
                    throw new CookException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }

                $now = new DateTime();
                $term = new DateInterval('PT5M'); //5miniuts
                $future = (new DateTime())->add($term);
                $jti = (new Base62)->encode(random_bytes(16));

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
                    , $container['jwtKey']['secret'], "HS384"); //exp 이후에는 인증이 끊김.
                $result['token'] = $token;
                $result['expires'] = $future->getTimeStamp();
                $result['auth_type'] = "bearer";

                //print_r($result);
                $resultData = array('result' => '201', 'message' => 'token is regenerated', 'data' => $result);
                //return $next($request, $response
                return $response
                    ->withStatus(201)
                    //->getBody()
                    ->write(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                session_destroy();
                $resultData = array('result' => '400', 'message' => 'token_invalid', 'data' => '{"error" : {"text" : "refresh token이 다릅니다.인증이 필요합니다."}}');
                throw new CookException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }
        return $next($request, $response);

    };
};

//refreshtoken, AuthExcepion도 새로 만들어야함.
$container['adminAuth'] = function ($container) {
    return function ($request, $response, $next) use ($container) {
        $user = $request->getAttribute('user'); //로그인 사용자정보

        //var_dump($user);
        if ($user->level > 0) {
            $resultData = array('result' => '401', 'message' => 'not authorized', 'data' => '{"error" : {"text" : "관리자가 아닙니다."}}');
            throw new CookException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return $next($request, $response);

    };
};


$container['checkAdmin'] = function($container) {
    return function($request, $response, $next) use ($container) {
        $user = $request->getAttribute("user");
        if ($user->level > 0) {
            $resultData = array('result' => '403', 'message' => 'Not allowed', 'data' => '{"error" : {"text" : "권한이 없습니다."}}');
            throw new CookException(json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        return $next($request, $response);
    };
};

$container['HomeController'] = function($container) {
    //var_dump(\App);
    return new App\Controllers\HomeController();
};
$container['MemberController'] = function($container) {
    //var_dump($container);
    return new App\Controllers\MemberController($container);
};
$container['GreetingController'] = function($container) {
    //var_dump($container);
    return new App\Controllers\GreetingController($container);
};
$container['DownloadController'] = function($container) {
    return new App\Controllers\DownloadController($container);
};
$container['AboutController'] = function($container) {
    return new App\Controllers\AboutController($container);
};
$container['FreeController'] = function($container) {
    return new App\Controllers\FreeController($container);
};
$container['MemoController'] = function($container) {
    return new App\Controllers\MemoController($container);
};
$container['QnAController'] = function($container) {
    return new App\Controllers\QnAController($container);
};
$container['SurveyController'] = function($container) {
    return new App\Controllers\SurveyController($container);
};
$container['AdminController'] = function($container) {
    return new App\Controllers\AdminController($container);
};





$app = new \Slim\App($container);  //설정을 사용가능하도록 한다.
//에러처리 해제
//unset($app->getContainer()['errorHandler']); //php fatal error 등.. Uncaught RuntimeException..
//unset($app->getContainer()['phpErrorHandler']); //exeception으로 잡히는 에러를 처리하는 듯, unset을 하면 slim에서 response를 클라이언트로 보내지 않음.

//이걸 쓰면 존재하지 않는 리소스에도 405를 적용하게 됨. 아마도... 모든 라우트에 적용되게 되어 있어서인 듯.
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        //->withHeader('Access-Control-Allow-Credentials', 'true') //origin이 * 일 때는 안됨
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

});




//인증 적용(token)
//$app->add(new Tuupola\Middleware\JwtAuthentication([
//    //"path" => "/api", /* or ["/api", "/admin"] */
//    //"ignore" => ["/api/signin"],
//    "secret" => "supersecretkeyyoushouldnotcommittogithub",
//    "attribute" => "decoded_token_data",
//    "logger" => $container['logger'],
//    "error" => function ($response, $exception) {
//        $resultData = array('result' => '401', 'message' => '', 'data' => '{"error" : {"text" : "인증되지 않았습니다."}}');
//        throw new CookException(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
//    },
//    "secure" => true,
//    "algorithm" => ["HS256", "HS384"],
//    "relaxed" => ["localhost", "192.168.0.147"], //https 체크, 개발인 경우에는 아이피에도 적용하기 위해.
//    "rules" => [
//        new Tuupola\Middleware\JwtAuthentication\RequestPathRule([
//            "path" => "/api/abouts",
//            "ignore" => ["/api/signin"]
//        ]),
//        new Tuupola\Middleware\JwtAuthentication\RequestMethodRule([
//            "ignore" => ["OPTIONS", "GET"]
//        ])
//    ],
//    'callback' => function ($request, $response, $arguments) use ($container) {
//        $container['user'] = $arguments['decoded'];
//        //$container['user'] = $decode_data['user'];
//    },
//]));

//임시며 middleware는 나중에 적용
session_start();

//$app->get('/', function($request, $response, $args) {
$app->get('/', 'HomeController:home');

$app->get('/test', function($request, $response, $args) {
    return $response->withStatus(200)->write('Hello world!');
});
//$app->get('/hello/{name}', function(Request $request, Response $response) {
$app->get('/hello/{name}', function(Request $request, Response $response, $args) {
    //$this->logger->addInfo('Something interesting happened');
    //$name = $request->getAttribute('name');
    $name = $args['name'];
    $response->withStatus(200)->write("Hello, $name");
    //$response = $this->view->render($response, 'tickets.phtml', ['tickets' => $tickets]);
    return $response;
});
$app->get('/hello/pathFor/{name}', function ($request, $response, $args) {
    echo "Hello, " . $args['name'];
})->setName('hi');


/*
$app->post('/ticket/new', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $ticket_data = [];
    $ticket_data['title'] = filter_var($data['title'], FILTER_SANITIZE_STRING);
    $ticket_data['description'] = filter_var($data['description'], FILTER_SANITIZE_STRING);
    // ...*/

/*
$app->get('/ticket/{id}', function (Request $request, Response $response, $args) {
    // ...
})->setName('ticket-detail');

$response = $this->view->render($response, 'tickets.phtml', ['tickets' => $tickets, 'router' => $this->router]);
*/


//test....
// abouts Routes

require __DIR__.'/app/Models/BasicBoardModel.php';
require __DIR__.'/app/Models/MemoBoardModel.php';
require __DIR__.'/app/Models/QnABoardModel.php';
require __DIR__.'/app/Models/BasicRippleModel.php';
require __DIR__.'/app/Models/SurveyModel.php';
require __DIR__.'/app/Models/AdminModel.php';
require __DIR__ . '/app/Exceptions/BasicBoardException.php';
require __DIR__ . '/app/Exceptions/SurveyException.php';
require __DIR__ . '/app/Exceptions/AdminException.php';

require 'routes/member.php';
require 'routes/auths.php';
require 'routes/about.php';
require 'routes/greeting.php';
require 'routes/download.php';
require 'routes/free.php';
require 'routes/memo.php';
require 'routes/qna.php';
require 'routes/survey.php';
require 'routes/admin.php';

/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param UploadedFile $uploaded file uploaded file to move
 * @return string filename of moved file
 */
function moveUploadedFile($directory, $b_type, UploadedFile $uploadedFile)
{
//    echo var_dump($directory);
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION); //확장자를 가져온다.
    //https://secure.php.net/manual/en/function.bin2hex.php
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadDir = $directory . DIRECTORY_SEPARATOR . $b_type. DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 777, true);
    }

    $uploadedFile->moveTo($uploadDir.$filename);

    return $filename;
}

/**
 * Class
 */
Class CookException extends Exception {

    //http 상태 코드, 오류 메시지
    //protected
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}

//Exception ha
class CookExceptionHandler {

    private $logger;
    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function __invoke($request, $response, $exception) {

        $this->logger->addDebug("[".$exception->getFile()."][Line:: ".$exception->getLine()."]"."[code:: ".$exception->getCode() ."]"."[message:: ".$exception->getMessage() ."]".PHP_EOL);
        $resultData = json_decode($exception->getMessage(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if ($exception instanceof CookException) {
            //$resultData = json_decode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } else if ($exception instanceof MemberException) {
            //$resultData = json_decode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } else if ($exception instanceof SurveyException) {
            //$resultData = json_decode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } else if ($exception instanceof AdminException) {
            //$resultData = json_decode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } else {
            $resultData = array('result' => '500', 'message' => '', 'data' => '{"error" : {"text" : "오류가 발생했습니다."}}');
        }

        $statusCode = $resultData['result'];
        //var_dump((int)$statusCode);
        //echo $exception->getMessage();
        return $response
            ->withStatus((int)$statusCode)  //숫자만 가능
            ->write(json_encode($resultData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }
}

//$jwtAuth = (new JwtAuthentication(
//    [
//        "secret" => "secret",
//        "secure" => "secure",
//        "cookie" => "cookie",
//        "error" => function ($request, $response, $arguments) use ($container) {
//            return $response->withRedirect( $container->get("router")->pathFor("index"),301 );
//        },
//    ]
//))->withRules([
//    new RequestPathRule([
//        "path" => "/lookbooks",
//        "ignore" => ["/backend/login"],
//    ]),
//    new RequestMethodRule([
//        "ignore" => ["OPTIONS"],
//    ])
//]);

function objectToArray($d) {
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }

    if (is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return array_map(__FUNCTION__, $d);
    }
    else {
        // Return array
        return $d;
    }
}
//Array -> stdClass
function arrayToObject($d) {
    if (is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return (object) array_map(__FUNCTION__, $d);
    }
    else {
        // Return object
        return $d;
    }
}

//class HomeController {
////
////    protected $container;
////
////    // constructor receives container instance
////    public function __construct(ContainerInterface $container) {
////        $this->container = $container;
////    }
//
//    public function index() {
////        $this->logger->addInfo('Something interesting happened');
//        return 'Home';
//    }
//}

//$app->add($jwtAuth);
$app->run();