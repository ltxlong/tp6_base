<?php
// 应用公共文件

if (!function_exists('retJson')) {
    /**
     * json统一返回格式封装
     * 微信调试成功默认code是0
     *
     * @param string $msg 提示消息
     * @param int $code 错误码
     * @param array $data 附加数据
     * @param int $status http状态码，默认200
     * @return \think\response\Json
     *
     * 一般调用：
     * return retJson();
     *
     * 想要后面的代码继续执行，即异步，那就不能用return：
     * retJson()->send();
     */
    function retJson(string $msg = '', int $code = 0, array $data = [], int $status = 200)
    {
        $returnData = [
            'msg' => $msg,
            'code' => $code,
            'data' => $data,
            'request_id' => \app\MyLog::getRequestId()
        ];

        return json($returnData, $status);
    }
}

if (!function_exists('curl_post')) {
    /**
     * curl post 请求
     * @param string $url            // 请求的curl地址
     * @param $params                // 请求的参数
     * @param array $header          // http头
     * @param int $timeout           // 设置超时时间
     * @param int $log               // 是否启用日志
     * @param int $ssl               // 是否启用ssl
     * @param string $format         // 返回的格式
     * @return bool|mixed|string
     */
    function curl_post(string $url, $params = [], array $header = [], int $timeout = 5, int $log = 1, int $ssl = 0, string $format = 'json')
    {
        $ch = curl_init();
        if (is_array($params)) { // 数组
            $urlParam = http_build_query($params);
        } else { //json字符串
            $urlParam = $params;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // 设置超时时间
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 返回原生的（Raw）输出
        curl_setopt($ch, CURLOPT_POST, 1); // POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $urlParam); // POST参数

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        if ($ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, false); // 将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
        }

        $data = curl_exec($ch);

        if ($format == 'json') {
            $data = json_decode($data, true);
        }

        if (($log && !config('app_debug')) || config('app_debug')) {
            $info = curl_getinfo($ch);
            $_str = "--------------------------------------------------------------------------------------------------------------------\r\n";
            $resultFormat =  $_str . "[" . date('Y-m-d H:i:s') . "] " . request()->ip() ." 耗时:[%s]s 返回状态:[%s] POST\r\n请求网址:\r\n%s\r\n请求参数:\r\n%s\r\n响应结果:\r\n%s\r\n大小:[%s]kb 速度:[%s]kb/s";
            $resultLogMsg = sprintf($resultFormat, $info['total_time'], $info['http_code'], $info['url'], var_export($params,true), var_export($data,true), $info['size_download']/1024, $info['speed_download']/1024);;
            $time = time();
            $logPath = LOG_PATH . 'curl/' . date('Y', $time) . '/post_' . date('Y-m', $time) . '.log';
            checkCurlLogSize($logPath);
            checkCurlLogNum();
            error_log($resultLogMsg . PHP_EOL, 3, $logPath);
        }

        curl_close($ch);

        return $data;
    }
}

if (!function_exists('curl_get')) {
    /**
     * curl get 请求
     * @param string $url            // 请求的url地址
     * @param array $params          // 请求的参数（如果请求前不拼接到url）
     * @param array $header          // http头
     * @param int $timeout           // 设置超时时间
     * @param int $log               // 是否启用日志
     * @param string $format         // 返回的格式
     * @return bool|mixed|string
     */
    function curl_get(string $url, array $params = [], array $header = [], int $timeout = 5, int $log = 1, string $format = 'json')
    {
        $ch = curl_init();
        if (!empty($params)) {
            $url = $url . '?' . http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 返回原生的（Raw）输出
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // 设置超时时间

        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $data = curl_exec($ch);

        if ($format == 'json') {
            $data = json_decode($data, true);
        }

        if(($log && !config('app_debug')) || config('app_debug')) {
            $info = curl_getinfo($ch);
            $_str = "--------------------------------------------------------------------------------------------------------------------\r\n";
            $resultFormat =  $_str . "[" . date('Y-m-d H:i:s') . "] " . request()->ip() ." 耗时:[%s]s 返回状态:[%s] GET\r\n请求网址:\r\n%s\r\n请求参数:\r\n%s\r\n响应结果:\r\n%s\r\n大小:[%s]kb 速度:[%s]kb/s";
            $resultLogMsg = sprintf($resultFormat, $info['total_time'], $info['http_code'], $info['url'], var_export($params,true), var_export($data,true), $info['size_download']/1024, $info['speed_download']/1024);
            $time = time();
            $logPath = LOG_PATH . 'curl/' . date('Y', $time) . '/get_' . date('Y-m', $time) . '.log';
            checkCurlLogSize($logPath);
            checkCurlLogNum();
            error_log($resultLogMsg . PHP_EOL, 3, $logPath);
        }

        curl_close($ch);

        return $data;
    }
}

if (!function_exists('checkCurlLogSize')) {
    /**
     * 检查curl日志文件大小并自动生成备份文件
     * @param string $destination 日志路径
     */
    function checkCurlLogSize(string $destination)
    {
        $year = date('Y', time());
        if (!is_dir(LOG_PATH . 'curl')) {
            mkdir(LOG_PATH . 'curl', 0755, true);
        }
        if (!is_dir(LOG_PATH . 'curl/' . $year)) {
            mkdir(LOG_PATH . 'curl/' . $year, 0755, true);
        }

        if (is_file($destination)) {
            $eachLogSize = 2097152; // 每个日志的大小 2M
            if(floor($eachLogSize) <= filesize($destination)) {
                try {
                    rename($destination, dirname($destination) . DIRECTORY_SEPARATOR . time() . '-' .
                                       basename($destination));
                } catch (\Exception $e) {
                }
            }
        } else {
            file_put_contents($destination, "--------------------------------------------------------------------------------------------------------------------\r\n");
        }
    }
}

if (!function_exists('checkCurlLogNum')) {
    /**
     * 限制curl每年的日志数量
     */
    function checkCurlLogNum()
    {
        $yearLogMixNum = 20; // 每个年份文件夹最大日志数量
        $year = date('Y', time());
        $curlLogPath = LOG_PATH . 'curl/' . $year . '/';

        if ($yearLogMixNum) {
            $files = glob($curlLogPath . '*.log');

            try {
                if (count($files) > $yearLogMixNum) {
                    unlink($files[0]);
                }
            } catch (\Exception $e) {
            }
        }
    }
}

if (!function_exists('timeAgo')) {
    /**
     * 修改时间显示
     * @param int $pastTime
     * @return false|string
     */
    function timeAgo(int $pastTime)
    {
        // 当前时间的时间戳
        $nowTime = time();

        // 相差时间戳
        $countTime = $nowTime - $pastTime;

        $postYear = date('Y', $pastTime);
        $currentYear = date('Y', $nowTime);

        // 进行时间转换
        if ($countTime <= 60) { // 时间小于60秒显示时间
            return '刚刚';
        } elseif ($countTime < 3600) {  // 60秒至一个小时间前
            return intval(($countTime / 60)) . '分钟前';
        } elseif ($countTime < 3600 * 24) {  // 一个小时至一天
            return intval(($countTime / 3600)) . '小时前';
        } elseif ($countTime <= 3600 * 24 * 7) { // 一天至一周
            return intval(($countTime / (3600 * 24))) . '天前';
        } elseif ($currentYear === $postYear) {
            return date('n/j H:i', $pastTime);
        } else {
            return date('Y-m-d H:i', $pastTime);
        }
    }
}

if (!function_exists('timeStr2Sec')) {
    /**
     * 时间字符转化到秒
     * @param string $timeStr 时间字符串，如 "22:33"
     * @return float|int
     */
    function timeStr2Sec(string $timeStr)
    {
        if (empty($timeStr)) {
            return 0;
        }

        $timeArr = explode(':', $timeStr);
        $count = count($timeArr);

        switch ($count) {
            case 2:
                $secRes = (int)$timeArr[0] * 60 + (int)$timeArr[1];
                break;
            case 3:
                $secRes = (int)$timeArr[0] * 60 * 60 + (int)$timeArr[1] * 60 + (int)$timeArr[2];
                break;
            default:
                $secRes = 0;
        }

        return $secRes;
    }
}

if (!function_exists('arrKeySort')) {
    /**
     * 二维数组按照某个key排序
     * @param array $arr
     * @param string $key
     * @param string $sort
     * @return array
     */
    function arrKeySort(array $arr = [], string $key = '', string $sort = 'desc')
    {
        if (!empty($arr) || !empty($key)) {
            if ($sort == 'desc') {
                $sort = SORT_DESC;
            } else {
                $sort = SORT_ASC;
            }

            $keyArr = array_column($arr , $key);

            array_multisort($keyArr, $sort, $arr);
        }

        return $arr;
    }
}

if (!function_exists('arrPage')) {
    /**
     * 数组分页
     * @param array $list 数据数组
     * @param int $page 页数
     * @param int $perPage 每页数量
     * @return array
     */
    function arrPage(array $list, int $page = 1, int $perPage = 10)
    {
        if (empty($list)) {
            return [];
        }

        $data = [];
        $data['total'] = count($list);
        $data['per_page'] = $perPage;
        $data['current_page'] = $page;
        $data['last_page'] = (int)ceil($data['total'] / $data['per_page']);
        $data['data'] = array_slice($list,($page - 1) * $perPage, $perPage);

        return $data;
    }
}

if (!function_exists('fff')) {
    /**
     * 浏览器console打印
     * 左手操作
     * @param $logMsg
     */
    function fff($logMsg)
    {
        if (is_array($logMsg) || is_object($logMsg)) {
            echo "<script>console.log('PHP打印：" . json_encode($logMsg) . "')</script>";
        } else {
            echo "<script>console.log('PHP打印：" . $logMsg . "')</script>";
        }
    }
}

if (!function_exists('logError')) {
    /**
     * 打印日志
     * @param $logMsg
     */
    function logError($logMsg)
    {
        \think\facade\Log::record($logMsg, 'error');
    }
}

if (!function_exists('reverseHandleStr')) {
    /**
     * 反向处理还原字符串（如：签名校验的时候要用到）
     * @param array $data
     * @return array
     */
    function reverseHandleStr(array $data)
    {
        $defaultFilter = \think\facade\Request::filter();
        if (!empty($defaultFilter)) {

            foreach ($defaultFilter as $d) {
                if ($d == 'htmlentities') {
                    array_walk($data, function (&$v) {
                        $v = html_entity_decode($v);
                    });
                    unset($v);
                } elseif ($d == 'addcslashes') {
                    array_walk($data, function (&$v) {
                        $v = stripcslashes($v);
                    });
                    unset($v);
                } elseif ($d == 'addslashes') {
                    array_walk($data, function (&$v) {
                        $v = stripslashes($v);
                    });
                    unset($v);
                } elseif ($d == 'htmlspecialchars') {
                    array_walk($data, function (&$v) {
                        $v = htmlspecialchars_decode($v);
                    });
                    unset($v);
                }
            }
        }

        return $data;
    }
}