<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 10.
 * Time: PM 3:34
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//글쓰기, 수정, 삭제, 목록, 검색, 페이징, 상세
require __DIR__.'/../app/Controllers/GreetingController.php';


//echo dirname(__DIR__.'/../app/Controllers/GreetingController.php');
$app->group('/api/greets/{num:[0-9]+}', function() {
    $this->put('', 'GreetingController:modifyGreet');
    $this->delete('', 'GreetingController:deleteGreet');
})->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));
$app->post('/api/grees', 'GreetingController:registerGreeting')->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));

$app->get('/api/greets', 'GreetingController:getGreetings')->setName('greeting.list');
$app->get('/api/greets/{num:[0-9]+}', 'GreetingController:getGreeting');