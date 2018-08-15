<?php
/**
 * Created by PhpStorm.
 * User: jinchun
 * Date: 2018/8/5
 * Time: 下午8:59
 */
namespace app\unionjd\controller;

use app\unionjd\utils\JingDongUtil;
use think\Cache;
use think\Controller;
use think\Log;

//define('APP_KEY','39ADDBACC0D5E0CFD90892D8D45FE3C4');
//define('APP_SECRET','d751c9481d524133a07167632b6624e4');
//define('REDIRECT_URI', 'http://wx.microcodor.com/jdunion/index/oauth2');
/**
* 原生测试页
 */
class Index extends Controller
{
    //jssdk授权前信息获取
    public function index()
    {
        echo 'aaaaa';
    }
    public function getToken(){
        $oauth2_info =  Cache::get('oauth2_access_token');
        var_dump($oauth2_info);
    }

    public function oauth2(){
        $jingdongutil = new JingDongUtil();
        if (!isset($_GET['code'])){
            $jumpurl = $jingdongutil->oauth2_authorize();
            header('Location:'.$jumpurl);
            exit();
        }else{
            $oauth2_info = $jingdongutil->oauth2_access_token($_GET['code']);
            if ($oauth2_info){
                Log::write('oauth2_access_token:'.$oauth2_info->access_token,'log');
                Cache::set('oauth2_access_token',$oauth2_info);
                return json_encode($oauth2_info);
            }else{
                return 'oauth2_info is null';
            }
        }
        return false;
    }
}