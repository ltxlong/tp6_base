<?php

namespace app\api\service;

use app\common\base\BaseService;

/**
 * 邀请码服务类
 */
class InviteService extends BaseService
{
    // 以下参数一旦使用后都不能变更
    // 随机字符串 32位 26个大写字母+10个数字，去掉容易混淆的，O、0、I、1等字符就剩下32个
    private $randSourceStr;

    // 32进制 $randSourceStr 的长度
    private $charsLength = 32;

    // 邀请码长度
    // 默认参数下，8位邀请码可以表示11449130896个（一百亿+）用户 ($charsLength^($codeLength - 1) - $slat) / $prime1
    private $codeLength = 8;

    // 随机数据
    // 8位数，位数和邀请码长度对应，为了将id补位
    private $slat = 12345678;

    // $prime1 与 $charsLength 互质，可保证（$id*$prime1）% $charsLength 在 [0,$charsLength)上均匀分布
    private $prime1 = 3;

    // $prime2 与 $codeLength 互质，可保证（$id*$prime2）% $codeLength 在 [0,$codeLength)上均匀分布
    private $prime2 = 11;

    public function __construct()
    {
        $this->randSourceStr = env('invite.invite_source_str', '');
    }

    /**
     * [uidToInvite] 根据用户id生成唯一邀请码
     * @param int $uid 用户id
     * @return string 返回唯一邀请码
     */
    public function uidToInvite(int $uid) : string
    {
        $prime1 = $this->prime1;
        $prime2 = $this->prime2;
        $slat = $this->slat;
        $codeLength = $this->codeLength;
        $charsLength = $this->charsLength;
        $randSourceStr = $this->randSourceStr;

        // 补位
        $uid = $uid * $prime1 + $slat;

        // 将id转换为32位进制
        $bArr = [];
        $bArr[0] = $uid;
        for ($i = 0; $i < $codeLength - 1; $i++) {
            $bArr[$i + 1] = $bArr[$i] / $charsLength;
            // 按位扩散
            $bArr[$i] = ($bArr[$i] + $bArr[0] * $i) % $charsLength;
        }
        // 到这里，按位扩散后的$bArr数组的最后一个元素，只要小于1都可以正确反解析出用户id

        // 校验位
        $expect = 0;
        for ($i = 0; $i < $codeLength - 1; $i++) {
            $expect += $bArr[$i];
        }
        $bArr[$codeLength - 1] = $expect * $prime1 % $codeLength;

        // 混淆
        $inviteCode = '';
        for ($i = 0; $i < $codeLength; $i++) {
            $inviteCode .= $randSourceStr[$bArr[$i * $prime2 % $codeLength]];
        }

        // 返回唯一邀请码
        return  $inviteCode;
    }

    /**
     * [inviteToUid] 根据唯一邀请码解析出用户id
     * @param string $inviteCode 唯一邀请码
     * @return int 用户id
     */
    public function inviteToUid(string $inviteCode) : int
    {
        $prime1 = $this->prime1;
        $prime2 = $this->prime2;
        $slat = $this->slat;
        $codeLength = $this->codeLength;
        $charsLength = $this->charsLength;
        $randSourceStr = $this->randSourceStr;

        if (strlen($inviteCode) != $codeLength) {
            // 字符串异常
            return -1;
        }

        // 反混淆
        $bArr = [];
        for ($i = 0; $i < $codeLength; $i++) {
            $bArr[$i * $prime2 % $codeLength] = $i;
        }

        // 转换回密钥字符串下标
        for ($i = 0; $i < $codeLength; $i++) {
            $j = strpos($randSourceStr, $inviteCode[$bArr[$i]]);
            if ($j == -1) {
                // 字符串异常
                return -1;
            }
            $bArr[$i] = $j;
        }

        // 校验位
        $expect = 0;
        for ($i = 0; $i < $codeLength - 1; $i++) {
            $expect += $bArr[$i];
        }
        $expect = $expect * $prime1 % $codeLength;
        // 校验
        if ($expect != $bArr[$codeLength - 1]) {
            // 字符串异常
            return -1;
        }

        // 反扩散
        for ($i = $codeLength - 2; $i >= 0; $i--) {
            $bArr[$i] = ($bArr[$i] - $i * ($bArr[0] - $charsLength)) % $charsLength;
        }

        $res = 0;
        for ($i = $codeLength - 2; $i > 0; $i--) {
            $res += $bArr[$i];
            $res *= $charsLength;
        }

        // 返回用户id
        $uid = ($res + $bArr[0] - $slat) / $prime1;
        if (is_int($uid)) {
            return $uid;
        } else {
            // 字符串异常，超出id范围
            return -1;
        }
    }

}
