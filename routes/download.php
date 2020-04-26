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
require __DIR__.'/../app/Controllers/DownloadController.php';



//echo dirname(__DIR__.'/../app/Controllers/DownloadController.php');
$app->group('/api/downloads/{num:[0-9]+}', function() {
    $this->put('', 'DownloadController:modifyDownload');
    $this->delete('', 'DownloadController:deleteDownload');
})->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));
$app->post('/api/downloads', 'DownloadController:registerDownload')
    ->add($container->get('jwtAuth'))->add($container->get('refreshAuth'));
$app->get('/api/downloads', 'DownloadController:getDownloads')->setName('download.list');
$app->get('/api/downloads/{num:[0-9]+}', 'DownloadController:getDownload');