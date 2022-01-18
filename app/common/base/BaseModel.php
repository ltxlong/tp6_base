<?php

namespace app\common\base;

use think\Model;

class BaseModel extends Model
{
    /**
     * 私有化构造方法，防止通过new来实例化
     */
    private function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取实例化对象
     * @return static
     */
    public static function obj()
    {
        return new static();
    }
}