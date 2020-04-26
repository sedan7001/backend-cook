<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 10.
 * Time: AM 8:47
 */

namespace App\Controllers;


class HomeController
{
    public function home($request, $response, $args) {
        return 'Welcome to Cook!!';
    }
}