<?php

namespace app\common\error;

/**
 * Api通用错误码
 */
class ApiErrorDesc
{
    /**
     * error_code < 1000
     */
    const SUCCESS = [0, 'Success'];
    const FAILURE = [1, 'Failure'];

    const ERR_TOKEN_EXPIRED = [401, '已过期，请重新登录'];
    const ERR_REFRESH_TOKEN_EXPIRED = [402, '已过期，请重新登录'];

    const ERR_PARAMS = [422, '参数错误'];

    /**
     * error_code 1001 < 1100
     */

    const ERR_PHONE_HAS_BIND = [1011, '该手机号已存在微信绑定'];
    const ERR_WX_HAS_BIND_PHONE = [1012, '该微信已存在手机绑定'];
}