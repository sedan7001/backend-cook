<?php
/**
 * Created by PhpStorm.
 * User: bahara
 * Date: 2018. 7. 11.
 * Time: AM 3:02
 */

namespace APP\Exceptions;


//php의 기본 exception은 namespace를 설정할 경우는 이렇게 호출하면 됨...
Class AdminException extends \Exception {

    //http 상태 코드, 오류 메시지
    //protected
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}