<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 10.
 * Time: PM 3:34
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//설문상세, 설문하기, 설문등록, 수정, 삭제
require __DIR__.'/../app/Controllers/SurveyController.php';


//echo dirname(__DIR__.'/../app/Controllers/SurveyController.php');
//$app->group('/admin/api/survey', function() {
//    $this->put('', 'SurveyController:modifySurvey');
//    $this->post('', 'SurveyController:registerSurvey');
//    $this->delete('', 'SurveyController:deleteSurvey');
//})->add($container->get('checkAdmin'))->add($container->get('refreshAuth'))->add($container->get('jwtAuth'));jwtAuth

$app->get('/api/survey', 'SurveyController:getSurvey')->setName('survey.view');
$app->put('/api/survey', 'SurveyController:updateAnswer');