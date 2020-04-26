<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 10.
 * Time: PM 2:40
 */

namespace APP\Controllers;


class Controller
{
    protected $container;
    protected $db;

    public function __construct($container)
    {
        $this->container = $container;
        //var_dump($container);
        //print_r($this->container->db);
        $this->db = $container['db'];
    }

    public function __get($property) {
        if ($this->container->{$property}) {
            return $this->container->{$property};
        }
    }

//    public function __destruct()
//    {
//        $this->db = null;
//    }
}