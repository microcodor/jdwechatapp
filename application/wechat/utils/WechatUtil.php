<?php
/**
 * Created by PhpStorm.
 * User: jinchun
 * Date: 2018/8/6
 * Time: 下午11:46
 */

namespace app\wechat\utils;

use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Wechat;
use think\Cache;
use think\Log;


class WechatUtil{
    public $wechat;
    public $api;
    function __construct(){
        //echo '__construct';
        if (!$this->wechat){
            // wechat模块 - 处理用户发送的消息和回复消息
            $this->wechat = new Wechat(array(
                'appId' => config('appID'),
                'token' => 	config('token'),
                'encodingAESKey' =>	config('encodingAESKey') //可选
            ));
        }
        if (!$this->api){
            // api模块 - 包含各种系统主动发起的功能
            // api模块
            $this->api = new Api(
                array(
                    'appId' => config('appID'),
                    'appSecret'	=> config('appSecret'),
                    'get_access_token' => function(){
                        // 用户需要自己实现access_token的返回
                        //$wechatUtil = new WechatUtil();
                        $access_token = $this->get_access_token();
                        //echo 'get_access_token1:'.$access_token;
                        return $access_token;
                    },
                    'save_access_token' => function($token) {
                        // 用户需要自己实现access_token的保存
                        //echo 'get_access_token2:'.$token;
                        Cache::set("access_data",$token,7000);
                    },
                    'get_jsapi_ticket' => function(){
                        // 用户需要自己实现access_token的返回
                        //$wechatUtil = new WechatUtil();
                        $ticket = $this->get_jsapi_ticket();
                        return $ticket;
                    },
                    'save_jsapi_ticket' => function($ticket) {
                        // 用户需要自己实现access_token的保存
                        Cache::set("jsapi_ticket",$ticket,7000);
                    },
                )
            );
        }
    }
    /**
     *  获取token，使用文件缓存机制
     *  首先检查文件是否存在，存在则检查token是否过期
     *  若过期或文件不存在，则向服务器请求，然后存入文件
     *
     */
    public function get_access_token() {
        //$path = WEIXIN_ROBOT_PLUGIN_DIR."/access_token.json";
        $json = Cache::get('access_data');
        //echo "get_access_token3:".$json;

        // 检查文件并查看token是否过期
        //if(file_exists($path)) {
        if ($json){
            // $json = file_get_contents($path);
            $array = json_decode($json, true);
            //echo "get_access_token4 :".$array['access_token'];
            if(isset($array['access_token']) && isset($array['expires_in'])){
                $expires_time = intval($array["expires_in"]) - 100;
                $now = time();
                //echo "get_access_token5 :".($now < $expires_time);
                if($now < $expires_time)
                    return $json;
            }
        }
        // 如果文件不存在或者token已经过期则向服务器请求
        $result = $this->http_get_result("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".config("appID")."&secret=".config("appSecret"));
        //echo "result:".$result;
        if($result) {
            $json = json_decode($result, true);
            //echo "expires_in上还是：".$json["expires_in"];
            if(!$json || isset($json['errcode']))
                return false;
            //echo "access_token的数据:".$json["access_token"];
            Cache::set("access_data",json_encode($json),7000);
            return json_encode($json);
        }
        return false;
    }
    /**
     * JS-SDK 获取jsapi_ticket
     *
     * @return string $ticket
     */
    public function get_jsapi_ticket()
    {
        $ticket = Cache::get('jsapi_ticket');
        Log::write('get_jsapi_ticket1:'.$ticket,'log');
        if ($ticket){
            // $json = file_get_contents($path);
            if(!isset($ticket['ticket']) || !isset($ticket['time']) || !isset($ticket['expires_in']))
                return false;
            $array = json_decode($ticket, true);
            $expires_time = intval($array["time"]) + intval($array["expires_in"]) - 100;
            $now = time();
            if($now < $expires_time)
                return $ticket;
        }
        $result = $this->http_get_result("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=" . $this->get_access_token() . "&type=jsapi");
        Log::write('get_jsapi_ticket2:'.$ticket,'log');
        if($result) {
            $json = json_decode($result, true);
            //echo "expires_in上还是：".$json["expires_in"];
            if(!$json || isset($json['errcode']))
                return false;
            $json["time"] = time();
            //$json = json_encode($json);
            // 写入文件
            //$file = fopen($path, "wb");
            //Cache::set("access_data",json_encode($json),7000);
            //            if($file!==false) {
            //                fwrite($file, $json);
            //                fclose($file);
            //            }
            //echo "access_token的数据:".$json["access_token"];
            return json_encode($json);
        }
        return false;
    }

    /**
    *  微信网页授权
     */
    public function web_auth($auth_type,$callback_url,$main_url){
        $path = $this->api->get_authorize_url($auth_type, $callback_url,$main_url);
        //echo $path;
        header('Location:'.$path);
    }

    /**
     * 使用curl实现GET请求
     */
    public function http_get_result($url) {
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
