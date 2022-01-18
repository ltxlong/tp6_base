<?php

namespace app;

use think\App;
use think\contract\LogHandlerInterface;

/**
 * 自定义日志驱动
 * 为每个请求添加唯一request_id
 */
class MyLog implements LogHandlerInterface
{
    private static $requestId = null;

    protected $config = [
        'time_format' => ' c ',
        'single'      => false,
        'file_size'   => 2097152,
        'path'        => LOG_PATH,
        'apart_level' => [],
        'max_files'   => 0,
        'json'        => false,
    ];

    /**
     * 实例化并传入参数
     * LuckyLog constructor.
     * @param App $app
     * @param array $config
     */
    public function __construct(App $app, $config = [])
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        if (self::$requestId === null) {
            self::$requestId = self::generate();

            if (PHP_SAPI == 'cli') {
                self::$requestId .= '.cli';
            }
        }
    }

    /**
     * 设置当前请求id
     * @param $requestId
     */
    public static function setRequestId($requestId)
    {
        self::$requestId = $requestId;
    }

    /**
     * 获取当前请求id
     */
    public static function getRequestId()
    {
        if (self::$requestId === null) {
            self::$requestId = self::generate();

            if (PHP_SAPI == 'cli') {
                self::$requestId .= '.cli';
            }
        }

        return self::$requestId;
    }

    /**
     * 生成当前请求id
     * @return string
     */
    private static function generate()
    {
        // 使用session_create_id()方法创建前缀
        $prefix = session_create_id(date('YmdHis'));

        // 使用uniqid()方法创建唯一id
        $request_id = strtoupper(md5(uniqid($prefix, true)));

        // 格式化请求id
        return self::format($request_id);
    }

    /**
     * 格式化请求id
     * @param $requestId
     * @param string $format // 8,4,4,4,12 是标准的uuid格式
     * @return string
     */
    private static function format($requestId, $format = '8,4,4,4,12')
    {
        $tmp = array();
        $offset = 0;

        $cut = explode(',', $format);

        // 根据设定格式化
        if ($cut) {
            foreach ($cut as $v) {
                $tmp[] = substr($requestId, $offset, $v);
                $offset += (int)$v;
            }
        }

        // 加入剩余部分
        if ($offset < strlen($requestId)) {
            $tmp[] = substr($requestId, $offset);
        }

        return implode('-', $tmp);
    }

    /**
     * 日志写入接口
     * @access public
     * @param array $log 日志信息
     * @return bool
     */
    public function save(array $log): bool
    {
        $destination = $this->getMasterLogFile();

        $path = dirname($destination);
        !is_dir($path) && mkdir($path, 0755, true);

        $info = [];

        // 日志信息封装
        $time = \DateTime::createFromFormat('0.u00 U', microtime())
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            ->format($this->config['time_format']);

        foreach ($log as $type => $val) {
            $message = [];
            foreach ($val as $msg) {
                if (!is_string($msg)) {
                    $msg = var_export($msg, true);
                }

                $message[] = $this->config['json'] ?
                    json_encode(['time' => $time, 'type' => $type, 'msg' => $msg], $this->config['json_options']) :
                    sprintf($this->config['format'], $time, $type, $msg);
            }

            if (true === $this->config['apart_level'] || in_array($type, $this->config['apart_level'])) {
                // 独立记录的日志级别
                $filename = $this->getApartLevelFile($path, $type);
                $this->write($message, $filename);
                continue;
            }

            $info[$type] = $message;
        }

        if ($info) {
            return $this->write($info, $destination);
        }

        return true;
    }

    /**
     * 获取主日志文件名
     * @access public
     * @return string
     */
    protected function getMasterLogFile()
    {
        if ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';

            $destination = $this->config['path'] . $name . '.log';
        } else {
            $cli = PHP_SAPI == 'cli' ? '_cli' : '';

            if ($this->config['max_files']) {
                $filename = date('Ymd') . $cli . '.log';
                $files    = glob($this->config['path'] . '*.log');

                try {
                    if (count($files) > $this->config['max_files']) {
                        unlink($files[0]);
                    }
                } catch (\Exception $e) {
                }
            } else {
                $filename = date('Ym') . DIRECTORY_SEPARATOR . date('d') . $cli . '.log';
            }

            $destination = $this->config['path'] . $filename;
        }

        return $destination;
    }

    /**
     * 获取独立日志文件名
     * @access public
     * @param  string $path 日志目录
     * @param  string $type 日志类型
     * @return string
     */
    protected function getApartLevelFile($path, $type)
    {
        $cli = PHP_SAPI == 'cli' ? '_cli' : '';

        if ($this->config['single']) {
            $name = is_string($this->config['single']) ? $this->config['single'] : 'single';
            $name .= '_' . $type;
        } elseif ($this->config['max_files']) {
            $name = date('Ymd') . '_' . $type . $cli;
        } else {
            $name = date('d') . '_' . $type . $cli;
        }

        return $path . DIRECTORY_SEPARATOR . $name . '.log';
    }

    /**
     * 日志写入
     * @access protected
     * @param  array     $message 日志信息
     * @param  string    $destination 日志文件
     * @param  bool      $apart 是否独立文件写入
     * @param  bool      $append 是否追加请求信息
     * @return bool
     */
    protected function write($message, $destination, $apart = false, $append = false)
    {
        // 检测日志文件大小，超过配置大小则备份日志文件重新生成
        $this->checkLogSize($destination);

        // 日志信息封装
        $info['timestamp'] = date($this->config['time_format']);

        foreach ($message as $type => $msg) {
            $info[$type] = is_array($msg) ? implode("\r\n", $msg) : $msg;
        }

        if (PHP_SAPI == 'cli') {
            $message = $this->parseCliLog($info);
        } else {
            // 添加调试日志
            $this->getDebugLog($info, $append, $apart);

            $message = $this->parseLog($info);
        }

        return error_log($message, 3, $destination);
    }

    /**
     * 检查日志文件大小并自动生成备份文件
     * @access protected
     * @param  string    $destination 日志文件
     * @return void
     */
    protected function checkLogSize($destination)
    {
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            try {
                rename($destination, dirname($destination) . DIRECTORY_SEPARATOR . time() . '-' .
                                   basename($destination));
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * CLI日志解析
     * @access protected
     * @param  array     $info 日志信息
     * @return string
     */
    protected function parseCliLog($info)
    {
        if ($this->config['json']) {
            $message = json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\r\n";
        } else {
            $now = $info['timestamp'];
            unset($info['timestamp']);

            $message = implode("\r\n", $info);

            $message = "[{$now}]" . $message . "\r\n";
        }

        return $message;
    }

    /**
     * 解析日志
     * @access protected
     * @param  array     $info 日志信息
     * @return string
     */
    protected function parseLog($info)
    {
        $request     = \think\facade\Request::instance();
        $requestInfo = [
            'ip'     => $request->ip(),
            'method' => $request->method(),
            'host'   => $request->host(),
            'uri'    => $request->url(),
        ];

        if ($this->config['json']) {
            $info = $requestInfo + $info;
            return json_encode($info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\r\n";
        }

        array_unshift($info, "---------------------------------------------------------------\r\n[{$info['timestamp']}] [" . self::$requestId . "] {$requestInfo['ip']} {$requestInfo['method']} {$requestInfo['host']}{$requestInfo['uri']}");
        unset($info['timestamp']);

        return implode("\r\n", $info) . "\r\n";
    }

    /**
     * getDebugLog
     * @param $info
     * @param $append
     * @param $apart
     */
    protected function getDebugLog(&$info, $append, $apart)
    {
        if (app()->isDebug() && $append) {

            if ($this->config['json']) {
                // 获取基本信息
                $runtime = round(microtime(true) - THINK_START_TIME, 10);
                $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';

                $memory_use = number_format((memory_get_usage() - THINK_START_MEM) / 1024, 2);

                $info = [
                        'runtime' => number_format($runtime, 6) . 's',
                        'reqs'    => $reqs . 'req/s',
                        'memory'  => $memory_use . 'kb',
                        'file'    => count(get_included_files()),
                    ] + $info;

            } elseif (!$apart) {
                // 增加额外的调试信息
                $runtime = round(microtime(true) - THINK_START_TIME, 10);
                $reqs    = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';

                $memory_use = number_format((memory_get_usage() - THINK_START_MEM) / 1024, 2);

                $time_str   = '[运行时间：' . number_format($runtime, 6) . 's] [吞吐率：' . $reqs . 'req/s]';
                $memory_str = ' [内存消耗：' . $memory_use . 'kb]';
                $file_load  = ' [文件加载：' . count(get_included_files()) . ']';

                array_unshift($info, $time_str . $memory_str . $file_load);
            }
        }
    }
}