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
require __DIR__.'/../app/Controllers/QnAController.php';


//echo dirname(__DIR__.'/../app/Controllers/QnAController.php');
$app->group('/api/qnas/{num:[0-9]+}', function() {
    $this->put('', 'QnAController:modifyQnA');
    $this->post('/answer', 'QnAController:registerAnswerQnA');
    $this->delete('', 'QnAController:deleteQnA');
})->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));
$app->post('/api/qnas', 'QnAController:registerQnA')->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));

$app->get('/api/qnas', 'QnAController:getQnAs')->setName('qna.list');
$app->get('/api/qnas/{num:[0-9]+}', 'QnAController:getQnA');