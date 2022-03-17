<?php

declare(strict_types=1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Demo extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('demo')
            ->setDescription('the demo command');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('exec demo ...');
    }
}