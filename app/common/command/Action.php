<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

/**
 * 自定义指令执行控制器中的方法
 * 使用方法：
 * 切换到项目根目录
 * php think action api/v1/test/hello -o name=world,age=18
 * 请求api/v1/test控制器的hello方法，参数为name=world age=18，参数用英文逗号隔开
 */
class Action extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('action')
            ->addArgument('route', Argument::REQUIRED, '控制器方法路径')
            ->addOption('options', '-o', Option::VALUE_OPTIONAL, '参数') // option的第一个字母
            ->setDescription('the action command');
    }

    protected function execute(Input $input, Output $output)
    {
        $arguments = $input->getArguments();
        $params = [];
        if ($input->hasOption('options')) {
            $params = $this->handleOptions($input->getOption('options'));
        }
        $action = $this->handleRoute($arguments['route'])['action'];
        $result = app($this->getClassPath($arguments['route']))->$action($params);
        $result = $result ?: '';

        $output->writeln(is_array($result) ? json_encode($result) : is_object($result) ? $result->getContent() : $result);
    }

    protected function handleOptions($options)
    {
        $optionsArr = explode(',', $options); // 注意，这里不能用&号，会报错，所以用逗号
        $params = [];
        foreach ($optionsArr as $v) {
            $temp = explode('=', $v);
            $params[$temp[0]] = $temp[1];
        }

        return $params;
    }

    protected function handleRoute($route)
    {
        $routeArr = explode('/', $route);
        $data = [];
        if (count($routeArr) === 4) {
            list($data['module'], $data['version'], $data['controller'], $data['action']) = $routeArr;
        }
        if (count($routeArr) === 3) {
            list($data['module'], $data['controller'], $data['action']) = $routeArr;
        } else if (count($routeArr) === 2) {
            list($data['controller'], $data['action']) = $routeArr;
        }

        return $data;
    }

    protected function getClassPath($route)
    {
        $routeArr = $this->handleRoute($route);
        $data[] = 'app';
        if (isset($routeArr['module'])) {
            $data[] = $routeArr['module'];
        }
        $data[] = 'controller';
        if (isset($routeArr['version'])) {
            $data[] = $routeArr['version'];
        }
        $data[] = $routeArr['controller'];

        return implode('\\', $data);
    }

}