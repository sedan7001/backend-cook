<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 14.
 * Time: PM 10:07
 */

namespace Lib;

use \Psr\Http\Message\ServerRequestInterface as Request;


class Pagination
{
    private $request;
    private $perPage;
    private $total;
    private $current;


    public function __construct(Request $request, $total, $page=1, $perPage=10)
    {
        $this->current = $page;
        $this->perPage = $perPage;
        $this->total = $total;
        $this->request = $request;
    }
    
    public function getPagination() {
        //var_dump($this->getPrevious());
        try {
            $pagination = ['total' => $this->total
                , 'per_page' => $this->perPage
                , 'page' => $this->current
                , 'first' => $this->getFirst()
                , 'last' => $this->getLast()
                , 'from' => $this->getFirst()
                , 'to' => $this->getLast()
                , 'links' => ['prev' => $this->getPrevious()
                    , 'next' => $this->getNext()
                    , 'page_list' => $this->getList()
                ]
            ];
        } catch (\Exception $e) {
            throw $e;
        }
        return $pagination;
    }


    public function getFirst() {
        //var_dump($this->perPage);
        return floor($this->current / $this->perPage) * $this->perPage + 1 ;
    }
    private function getLast() {
        return ((ceil($this->total / $this->perPage) < ($this->getFirst() + $this->perPage) - 1)
            ? ceil($this->total / $this->perPage)
            : ($this->getFirst() + $this->perPage) - 1);
    }
    private function getList() {
        $pageList = array();
        for ($i = $this->getFirst(); $i <= $this->getLast() ; $i++) {
            $page['link'] = ($i == $this->current) ? "#"
                : $this->getPageLink($i);
            $page['rel'] = $i;
            array_push($pageList, $page);
            //echo $i.PHP_EOL;
        }
        return $pageList;
    }

    private function getPrevious() {
        $previous = array();
        $previous['link'] = ($this->getFirst() == 1) ? "#"
            : $this->getPageLink(($this->getFirst() - $this->perPage));
        $previous['rel'] = 'prev';
        return $previous;
    }

    private function getNext() {
        $next = array();
        $next['link'] = ($this->getLast() >= ceil($this->total / $this->perPage)) ? "#"
            : $this->getPageLink(($this->getFirst() + $this->perPage));
        $next['rel'] = 'next';
        return $next;

    }

    private function getPageLink($num) {
        $uri = $this->request->getUri();
        $pageUrl = $uri->getScheme() . "://".$uri->getHost();
        $pageUrl .= ($uri->getPort() == '80' ? "" : $uri->getPort());
        $pageUrl .= $uri->getBasePath() ."/". $uri->getPath();
        //$pageUrl .= "?". $uri->getQuery();
        $params = "?page=" . $num;
        foreach($this->request->getQueryParams() as $key=>$value) {
            if ($key == 'page') {
                continue;
            }
            $params .="&".$key."=".$value;
        }
        return $pageUrl .= $params;
    }

}