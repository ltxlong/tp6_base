<?php

namespace app;

use app\common\base\BaseException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\db\exception\PDOException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Log;
use think\facade\Request;
use think\Response;
use Throwable;

/**
 * 自定义异常处理类
 */
class MyExceptionHandle extends Handle
{
    // 状态码
    public $status;
    // 错误信息
    public $msg;
    // 错误码
    public $code;
    // 附加信息
    public $data = [];

    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制
        if ($e instanceof BaseException) {
            $this->status = $e->status;
            $this->msg = $e->msg;
            $this->code = $e->code;
            $this->data = $e->data;
        } else {
            // 如果是服务器未处理的异常，将http状态码设置为500
            $this->status = 500;
            $this->msg = 'sorry, we make a mistake';
            $this->code = 999;
        }

        $this->recordErrorLog($e);

        $result = [
            'msg' => $this->msg,
            'code' => $this->code,
            'data' => $this->data,
            'request_id' => MyLog::getRequestId()
        ];

        if (app()->isDebug()) {
            // 调试状态下需要显示TP默认的异常页面，因为TP的默认页面
            // 很容易看出问题
            //return parent::render($request, $e);

            $debugData = [
                'name' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $this->getCode($e),
                'message' => $this->getMessage($e),
                'trace' => $e->getTrace(),
                'source' => $this->getSourceCode($e),
            ];

            return json(array_merge($result, ['debug' => $debugData]));
        } else {
            return json($request, $this->status);
        }
    }

    /**
     * 将异常写入日志
     * @param Throwable $e
     */
    private function recordErrorLog(Throwable $e)
    {
        // 错误信息
        $data = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $this->getMessage($e),
            'status' => $this->getCode($e),
        ];

        // 日志内容
        $log = '';
        $log .= $this->getVisitMsg();
        $log .= "\r\n" . "[ message ] [{$data['status']}] {$data['message']}";
        $log .= "\r\n" . "[ file ] {$data['file']}:{$data['line']}";
        $log .= "\r\n" . '[ header ] ' . print_r(Request::header(), true);
        $log .= "\r\n" . '[ param ] ' . print_r(Request::param(), true);

        // 如果是数据库报错, 则记录sql语句
        if ($e instanceof PDOException) {
            $log .= "[ Error SQL ] " . $e->getData()['Database Status']['Error SQL'];
            $log .= "\r\n";
        }
        $log .= "\r\n" . $e->getTraceAsString();
        $log .= "\r\n" . '--------------------------------------------------------------------------------------------';

        Log::record($log, 'error');
    }

    /**
     * 获取请求路径信息
     * @return string
     */
    private function getVisitMsg()
    {
        $data = [Request::ip(), Request::method(), Request::url(true)];

        return implode(' ', $data);
    }
}