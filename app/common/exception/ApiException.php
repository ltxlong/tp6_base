<?php

namespace app\common\exception;

use app\common\base\BaseException;

/**
 * 自定义Api通用异常类
 */
class ApiException extends BaseException
{
    // 状态码
    public $status;
    // 错误信息
    public $msg;
    // 错误码
    public $code;
    // 附加信息
    public $data;

    /**
     * 构造函数
     * @param $apiErrConst - 错误参数，字符串或数组，为空时是[400, 'error']
     * string $msg
     * or
     * array [$code, $msg]
     * @param int $status - http状态码，默认400
     * @param array $data - 附加信息
     */
    public function __construct($apiErrConst, $status = 400, $data = [])
    {
        if (empty($apiErrConst)) {
            $code = 400;
            $msg = 'error';
        } else {
            if (is_array($apiErrConst)) {
                $code = $apiErrConst[0];
                $msg = $apiErrConst[1] ?? 'error';
            } else {
                $code = 400;
                $msg = $apiErrConst;
            }
        }

        $this->status = $status;
        $this->msg = $msg;
        $this->code = $code;
        $this->data = $data;
    }
}