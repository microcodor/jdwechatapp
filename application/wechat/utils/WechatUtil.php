<?php
/**
 * Created by PhpStorm.
 * User: jinchun
 * Date: 2018/8/6
 * Time: 下午11:46
 */

namespace app\wechat\utils;

use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Utils\FileCache;
use think\Cache;

class WechatUtil{
    /**
     *  获取token，使用文件缓存机制
     *  首先检查文件是否存在，存在则检查token是否过期
     *  若过期或文件不存在，则向服务器请求，然后存入文件
     *
     */
    public function get_access_token() {
        //$path = WEIXIN_ROBOT_PLUGIN_DIR."/access_token.json";
        $json = Cache::get('access_data');
        echo "缓存access_token的数据:".$json;

        // 检查文件并查看token是否过期
        //if(file_exists($path)) {
        if ($json){
            // $json = file_get_contents($path);
            if(!isset($json['access_token']) || !isset($json['time']) || !isset($json['expires_in']))
                return false;
            $array = json_decode($json, true);
            $expires_time = intval($array["time"]) + intval($array["expires_in"]) - 100;
            $now = time();
            if($now < $expires_time)
                return $array["access_token"];
        }
        // 如果文件不存在或者token已经过期则向服务器请求
        $result = $this->http_get_result("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".config("appID")."&secret=".config("appSecret"));
        if($result) {
            $json = json_decode($result, true);
            //echo "expires_in上还是：".$json["expires_in"];
            if(!$json || isset($json['errcode']))
                return false;
            $json["time"] = time();
            //$json = json_encode($json);
            // 写入文件
            //$file = fopen($path, "wb");
            Cache::set("access_data",json_encode($json),7000);
            //            if($file!==false) {
            //                fwrite($file, $json);
            //                fclose($file);
            //            }
            //echo "access_token的数据:".$json["access_token"];
            return $json["access_token"];
        }
        return false;
    }

    /**
    *  微信网页授权
     */
    public function web_auth($auth_type,$callback_url,$main_url){
        $cache =  new FileCache;

        // api模块
        $api = new Api(
            array(
                'appId' => config("appID"),
                'appSecret' => config('appSecret'),
                'get_access_token' => function() use ($cache) {
                    // echo "\nget_access_token:".json_decode($cache->get('access_token'))->access_token;
                    return json_decode($cache->get('access_token'))->access_token;
                },
                'save_access_token' => function($token) use ($cache) {
                    //echo "\nsave_access_token:".$token;
                    // 用户需要自己实现access_token的保存
                    $cache->set('access_token', $token, 3600);
                }
            )
        );
        $path = $api->get_authorize_url($auth_type, $callback_url,$main_url);
        header('Location:'.$path);
    }

    /**
     * 使用curl实现GET请求
     */
    private function http_get_result($url) {
        $oCurl = curl_init();
        if(stripos($url, "https://")!==FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($oCurl);
        $status = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($status["http_code"])==200)
            return $content;
        else
            return false;
    }
    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @return string $result
     */
    private function http_post_result($url, $param) {
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (is_string($param)) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach($param as $key=>$val) {
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
        $result = curl_exec($oCurl);
        $status = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($status["http_code"])==200)
            return $result;
        return $status["http_code"];
    }
}
