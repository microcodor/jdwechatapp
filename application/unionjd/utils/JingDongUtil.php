<?php
/**
 * 京东官方API调用
 * User: jinchun
 * Date: 2018/8/15
 * Time: 上午11:54
 */
namespace app\unionjd\utils;

use JD\JdClient;
use JD\request\UnionThemeGoodsServiceQueryCouponGoodsRequest;

//define('APP_KEY','39ADDBACC0D5E0CFD90892D8D45FE3C4');
//define('APP_SECRET','d751c9481d524133a07167632b6624e4');
///define('REDIRECT_URI', 'http://wx.microcodor.com/unionjd/index/oauth2');
///define('JD_TOKEN', '7c54f31a-6f9e-4d12-b3bd-72dd89cd01c6');
define('APP_KEY','9AB172BE4A1690C0F94ED75291C696EB');
define('APP_SECRET','01f4f550efbb45099315604453f9ef82');
define('REDIRECT_URI', 'http://wx.microcodor.com/unionjd/index/oauth2');
define('JD_TOKEN', '7c54f31a-6f9e-4d12-b3bd-72dd89cd01c6');
class JingDongUtil{
    public $c;
    function __construct(){
        //测试demo
        $this->c = new JdClient();
        $this->c->appKey = APP_KEY;
        $this->c->appSecret = APP_SECRET;
        $this->c->accessToken = JD_TOKEN;
        $this->c->serverUrl = "https://api.jd.com/routerjson";
    }
    /**
    *   优惠商品信息
     */
    public function get_goods(){
        $req = new UnionThemeGoodsServiceQueryCouponGoodsRequest();

        $req->setFrom( 0 ); $req->setPageSize( 10 ); $req->setCid3( -1 );

        $resp = $this->c->execute($req, $this->c->accessToken);
        return $resp;
    }

    //生成auth的url
    public function oauth2_authorize($state='oauth2_authorize', $scope='read', $view='wap'){
        $url = 'https://oauth.jd.com/oauth/authorize?response_type=code&client_id='.APP_KEY.'&redirect_uri='.REDIRECT_URI.
            '&scope='.$scope.'&state='.$state.'&view='.$view;
        return $url;
    }

    public function  oauth2_access_token($code, $state='oauth2_authorize'){
        $url = 'https://oauth.jd.com/oauth/token?grant_type=authorization_code&client_id='.APP_KEY.'&redirect_uri='.REDIRECT_URI.'&code='.$code.'&state='.$state.'&client_secret='.APP_SECRET;
        $res = $this->http_get_result($url);
        $res = mb_convert_encoding($res, 'UTF-8', 'GBK');
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