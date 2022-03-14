<?php

namespace app\common\base;

class BaseDao
{
    /**
     * 私有化构造方法，防止通过new来实例化
     */
    private function __construct()
    {
    }

    /**
     * 获取实例化对象
     * @return static
     */
    public static function instance()
    {
        return new static();
    }
}