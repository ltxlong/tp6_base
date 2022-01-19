<?php
// 应用公共文件

if (!function_exists('retJson')) {
    /**
     * json统一返回格式封装
     * 微信调试成功默认code是0
     *
     * @param string $msg
     * @param int $code
     * @param array $data
     * @return \think\response\Json
     *
     * 一般调用：
     * return retJson();
     *
     * 想要后面的代码继续执行，即异步，那就不能用return：
     * retJson()->send();
     */
    function retJson(string $msg = '', int $code = 0, array $data = [])
    {
        $returnData = [
            'msg' => $msg,
            'code' => $code,
            'data' => $data,
            'request_id' => \app\MyLog::getRequestId()
        ];

        return json($returnData);
    }
}