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
require __DIR__.'/../app/Controllers/AdminController.php';

//echo dirname(__DIR__.'/../app/Controllers/FreeController.php');
$app->group('/admin/api', function() {
    $this->get('/members', 'AdminController:getMemberList');
    $this->get('/members/count', 'AdminController:getMemberCount');
    $this->get('/members/joinStatByDay', 'AdminController:getMemberJoinStat');
    $this->get('/boards/count', 'AdminController:getBoardsCount');
    $this->get('/survey', 'AdminController:getSurvey');
    $this->put('/survey', 'AdminController:modifySurvey');
    $this->get('/dbUsages', 'AdminController:getDBUsages');
    $this->get('/tableUsages', 'AdminController:getTableUsages');
    $this->get('/tableUsagesStat', 'AdminController:getTableUsagesStat');
//    $this->delete('', 'FreeController:deleteFree');
//    $this->post('/ripples', 'FreeController:registerFreeRipple');
//    $this->delete('/ripples/{r_num:[0-9]+}', 'FreeController:deleteFreeRipple');
})->add($container->get('adminAuth'))->add($container->get('refreshAuth'))->add($container->get('jwtAuth'));   //admin 권한체크 mw 생성해야함.
//$app->post('/api/frees', 'FreeController:registerFree')->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));

//$app->get('/api/frees', 'FreeController:getFrees')->setName('free.list');
//$app->get('/api/frees/{num:[0-9]+}', 'FreeController:getFree');