<?php
/**
 * Created by PhpStorm.
 * User: jinchun
 * Date: 2018/8/11
 * Time: 上午9:42
 */
namespace app\wechat\utils;
class Util {
    /**
     * 递归检测url重定向地址, 直到重定向到rule所指地址
     * 返回该地址
     *
     * @param string $url 待检测的地址
     * @param string $rule 匹配的地址
     * @return mixed
     */
    public static function get_redirect_url($url)
    {
        $headers = get_headers($url, TRUE);
        print_r($headers);

//输出跳转到的网址
        echo $headers['Location'];

    }

}