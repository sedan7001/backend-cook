<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 10.
 * Time: PM 3:34
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//반드시 class file을 불러와야하는 듯..
require __DIR__.'/../app/Controllers/MemberController.php';
require __DIR__.'/../app/Models/MemberModel.php';
require __DIR__.'/../app/Exceptions/MemberException.php';

//echo dirname(__DIR__.'/../app/Controllers/MemberController.php');
$app->group('/api/members/{num:[0-9]+}', function() {
    $this->put('', 'MemberController:modifyMember');
    $this->get('', 'MemberController:getMember');
})->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));
$app->post('/api/members', 'MemberController:registerMember');
$app->get('/api/members/check-id', 'MemberController:checkMemberById');
$app->get('/api/members/check-nick', 'MemberController:checkMemberByNick');