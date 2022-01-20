<?php

namespace app\common\exception;

use app\common\base\BaseException;

/**
 * 自定义Api通用异常类
 */
class ApiException extends BaseException
{
    // 状态码
    public $code;
    // 错误信息
    public $message;
    // 错误码
    public $errorCode;
    // 附加信息
    public $data;

    /**
     * 构造函数
     * @param $apiErrConst - 错误参数，字符串或数组，为空时是[400, 'error']
     * string $msg
     * or
     * array [$code, $msg]
     * @param int $statusCode - http状态码，默认400
     * @param array $data - 附加信息
     */
    public function __construct($apiErrConst, $statusCode = 400, $data = [])
    {
        if (empty($apiErrConst)) {
            $errorCode = 400;
            $message = 'error';
        } else {
            if (is_array($apiErrConst)) {
                $errorCode = $apiErrConst[0];
                $message = $apiErrConst[1] ?? 'error';
            } else {
                $errorCode = 400;
                $message = $apiErrConst;
            }
        }

        $this->code = $statusCode;
        $this->message = $message;
        $this->errorCode = $errorCode;
        $this->data = $data;
    }
}