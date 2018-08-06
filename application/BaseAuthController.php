<?php
/**
 * Created by PhpStorm.
 * User: jinchun
 * Date: 2018/8/5
 * Time: 下午9:06
 */
namespace app;
use think\Controller;

class BaseAuthController extends Controller{
    //使用tp框架提供的构造函数验证session中是否有用户的信息的唯一标识user_id
    public function _initialize(){
        $user_id = session('user_id');
        if(!$user_id){
            //获取当前网页，授权后跳回
            $path =  $_SERVER['REQUEST_URI'];
            //跳转到微信授权
            header('Location:/auth/index?path='.$path);
        }
    }
}