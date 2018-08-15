<?php
/**
 * Created by PhpStorm.
 * User: jinchun
 * Date: 2018/8/15
 * Time: 上午11:54
 */
namespace app\unionjd\utils;

define('APP_KEY','39ADDBACC0D5E0CFD90892D8D45FE3C4');
define('APP_SECRET','d751c9481d524133a07167632b6624e4');
define('REDIRECT_URI', 'http://wx.microcodor.com/unionjd/index/oauth2');
class JingDongUtil{

    //生成auth的url
    public function oauth2_authorize($state='oauth2_authorize', $scope='read', $view='wap'){
        $url = 'https://oauth.jd.com/oauth/authorize?response_type=code&client_id='.APP_KEY.'&redirect_uri='.REDIRECT_URI.
            '&scope='.$scope.'&state='.$state.'&view='.$view;
        return $url;
    }

    public function  oauth2_access_token($code, $state='oauth2_authorize'){
        $url = 'https://oauth.jd.com/oauth/token?grant_type=authorization_code&client_id='.APP_KEY.'&
                redirect_uri='.REDIRECT_URI.'&code='.$code.'&state='.$state.'&client_secret='.APP_SECRET;
        $res = $this->http_get_result($url);

        return $res;

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
}