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
require __DIR__.'/../app/Controllers/MemoController.php';


//echo dirname(__DIR__.'/../app/Controllers/MemoController.php');
$app->group('/api/memos/{num:[0-9]+}', function() {
    $this->delete('', 'MemoController:deleteMemo');
    $this->post('/ripples', 'MemoController:registerMemoRipple');
    $this->delete('/ripples/{r_num:[0-9]+}', 'MemoController:deleteMemoRipple');
})->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));
$app->post('/api/memos', 'MemoController:registerMemo')->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));

$app->get('/api/memos', 'MemoController:getMemos')->setName('memo.list');
$app->get('/api/memos/{num:[0-9]+}/ripples', 'MemoController:getMemoRipples')->setName('memo.listRipples');