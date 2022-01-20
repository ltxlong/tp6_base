<?php

namespace app\common\base;

use think\Exception;

/**
 * 自定义异常基类
 */
class BaseException extends Exception
{
    // 状态码
    public $code = 400;
    // 错误信息
    public $message = 'error';
    // 错误码
    public $errorCode = 400;
    // 附加信息
    public $data = [];

    public function __construct($params = [])
    {
        if (array_key_exists('code', $params)) {
            $this->code = $params['code'];
        }
        if (array_key_exists('msg', $params)) {
            $this->message = $params['msg'];
        }
        if (array_key_exists('errorCode', $params)) {
            $this->errorCode = $params['errorCode'];
        }
        if (array_key_exists('data', $params)) {
            $this->data = $params['data'];
        }
    }
}