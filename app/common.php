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