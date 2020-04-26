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
require __DIR__.'/../app/Controllers/AboutController.php';


//echo dirname(__DIR__.'/../app/Controllers/AboutController.php');
$app->group('/api/abouts/{num:[0-9]+}', function() {
    $this->put('', 'AboutController:modifyAbout');
    $this->delete('', 'AboutController:deleteAbout');
})->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));
$app->post('/api/abouts', 'AboutController:registerAbout')->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));

$app->get('/api/abouts', 'AboutController:getAbouts')->setName('about.list');
$app->get('/api/abouts/{num:[0-9]+}', 'AboutController:getAbout');