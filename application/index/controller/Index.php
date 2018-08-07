<?php
namespace app\index\controller;

use app\index\model\User;
use think\Controller;
use think\db;

class Index extends Controller
{
    public function index()
    {

        return view('index');
    }
    public function  main(){
//        $user = Db::table('user')->where('openId',323221122)->find();
//        if (!$user){
//            $data = ['openId' => '323221122', 'nickName' => 'jinchun','headImgUrl' => 'htttp://wx.microcodor.com'];
//            Db::table('user')->insert($data);
//        }

        //$user = User::get(323221122);
        $data = ['openId' => '323221123', 'nickName' => 'jiwei','headImgUrl' => 'htttp://wx.microcodor.com'];
        $user = new User($data);
        //$user->save();
        $nickName = User::get(323221123)['nickName'];
        echo $nickName;
        return "this is main page：".$nickName;
    }
    public function test(){
        return dirname($_SERVER['SCRIPT_NAME']);
    }
}
