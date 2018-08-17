<?php
/**
 * 使用Gaoming13-sdk握手
 * User: jinchun
 * Date: 2018/8/4
 * Time: 下午2:59
 */
namespace app\wechat\controller;

use app\index\model\User;
use app\wechat\utils\Util;
use app\wechat\utils\WechatUtil;
use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Utils\FileCache;
use Gaoming13\WechatPhpSdk\Wechat;
use think\Cache;
use think\Controller;
use think\Exception;
use think\Log;
use think\Session;


class Index extends Controller {

    public function index(){
        // 这是使用了Memcached来保存access_token
        array(
            'type'=>'memcached',
            'host'=>'localhost',
            'port'=>'11211',
            'prefix'=>'think',
            'expire'=>0
        );
        $wechatUtil = new WechatUtil();
        // wechat模块 - 处理用户发送的消息和回复消息
//        $wechat = new Wechat(array(
//            'appId' => config('appID'),
//            'token' => 	config('token'),
//            'encodingAESKey' =>	config('encodingAESKey') //可选
//        ));
//        // api模块 - 包含各种系统主动发起的功能
//        // api模块
//        $api = new Api(
//            array(
//                'appId' => config('appID'),
//                'appSecret'	=> config('appSecret'),
//                'get_access_token' => function(){
//                    // 用户需要自己实现access_token的返回
//                    $wechatUtil = new WechatUtil();
//                    $access_token = $wechatUtil->get_access_token();
//                    return $access_token;
//                },
//                'save_access_token' => function($token) {
//                    // 用户需要自己实现access_token的保存
//                    Cache::set("access_data",$token,7000);
//                }
//            )
//        );

        // 获取微信消息
        $msg = $wechatUtil->wechat->serve();

        // 默认消息
        $default_msg = "/微笑  欢迎关注本测试号:\n 回复1: 回复文本消息\n 回复2: 回复图片消息\n 回复3: 回复语音消息\n 回复4: 回复视频消息\n 回复5: 回复音乐消息\n 回复6: 回复图文消息";
        Log::write('MsgType:'.$msg->MsgType,'log');
        Log::write('Content:'.$msg->Content,'log');
        // 用户关注微信号后 - 回复用户普通文本消息
        if ($msg->MsgType == 'event' && $msg->Event == 'subscribe') {

            $wechatUtil->wechat->reply($default_msg);
            exit();
        }

        // 用户回复1 - 回复文本消息
        if ($msg->MsgType == 'text' && $msg->Content == '1') {
            Log::write($msg->Content,'notice');
            $wechatUtil->wechat->reply("hello world!");
            /* 也可使用这种数组方式回复
            $wechat->reply(array(
                'type' => 'text',
                'content' => 'hello world!'
            ));
            */
            exit();
        }

        // 用户回复2 - 回复图片消息
        if ($msg->MsgType == 'text' && $msg->Content == '2') {
            $wechatUtil->wechat->reply(array(
                'type' => 'image',
                // 通过素材管理接口上传多媒体文件，得到的id
                'media_id' => 'Uq7OczuEGEyUu--dYjg7seTm-EJTa0Zj7UDP9zUGNkVpjcEHhl7tU2Mv8mFRiLKC'
            ));
            exit();
        }

        // 用户回复3 - 回复语音消息
        if ($msg->MsgType == 'text' && $msg->Content == '3') {

            $wechatUtil->wechat->reply(array(
                'type' => 'voice',
                // 通过素材管理接口上传多媒体文件，得到的id
                'media_id' => 'rVT43tfDwjh4p1BV2gJ5D7Zl2BswChO5L_llmlphLaTPytcGcguBAEJ1qK4cg4r_'
            ));
            exit();
        }

        // 用户回复4 - 回复视频消息
        if ($msg->MsgType == 'text' && $msg->Content == '4') {

            $wechatUtil->wechat->reply(array(
                'type' => 'video',
                // 通过素材管理接口上传多媒体文件，得到的id
                'media_id' => 'yV0l71NL0wtpRA8OMX0-dBRQsMVyt3fspPUzurIS3psi6eWOrb_WlEeO39jasoZ8',
                'title' => '视频消息的标题',			//可选
                'description' => '视频消息的描述'		//可选
            ));
            exit();
        }

        // 用户回复5 - 回复音乐消息
        if ($msg->MsgType == 'text' && $msg->Content == '5') {

            $wechatUtil->wechat->reply(array(
                'type' => 'music',
                'title' => '音乐标题',						//可选
                'description' => '音乐描述',				//可选
                'music_url' => 'http://me.diary8.com/data/music/2.mp3',		//可选
                'hqmusic_url' => 'http://me.diary8.com/data/music/2.mp3',	//可选
                'thumb_media_id' => 'O39wW0ZsXCb5VhFoCgibQs5PupFb6VZ2jH5A8gHUJCJz2Qmkrb7objoTue7bGTGQ',
            ));
            exit();
        }

        // 用户回复6 - 回复图文消息
        if ($msg->MsgType == 'text' && $msg->Content == '6') {

            $wechatUtil->wechat->reply(array(
                'type' => 'news',
                'articles' => array(
                    array(
                        'title' => '图文消息标题1',								//可选
                        'description' => '图文消息描述1',						//可选
                        'picurl' => 'http://me.diary8.com/data/img/demo1.jpg',	//可选
                        'url' => 'http://www.example.com/'						//可选
                    ),
                    array(
                        'title' => '图文消息标题2',
                        'description' => '图文消息描述2',
                        'picurl' => 'http://me.diary8.com/data/img/demo2.jpg',
                        'url' => 'http://www.example.com/'
                    ),
                    array(
                        'title' => '图文消息标题3',
                        'description' => '图文消息描述3',
                        'picurl' => 'http://me.diary8.com/data/img/demo3.jpg',
                        'url' => 'http://www.example.com/'
                    )
                )
            ));
            exit();
        }
        if ($msg->MsgType == 'text' && strpos($msg->Content,'http') !== false){
            $resMsg = Util::save_data($msg->Content);
            Log::write($resMsg,'notice');
            $wechatUtil->wechat->reply($resMsg);
            exit();
        }

        // 默认回复默认信息
        $wechatUtil->wechat->reply($default_msg);


    }
    public function createMenu(){
        // api模块 - 包含各种系统主动发起的功能
//        $api = new Api(
//            array(
//                'appId' => config('appID'),
//                'appSecret'	=> config('appSecret'),
//                'get_access_token' => function(){
//                    // 用户需要自己实现access_token的返回
//                    $wechatUtil = new WechatUtil();
//                    $access_token = $wechatUtil->get_access_token();
//                    return $access_token;
//                },
//                'save_access_token' => function($token) {
//                    // 用户需要自己实现access_token的保存
//                    Cache::set("access_data",$token,7000);
//                }
//            )
//        );
        $wechatUtil = new WechatUtil();
        $menu_json = '{
	    "button":[
	    {
				"name":"京仓京配",
				"type":"view",
				"url":"http://wx.microcodor.com/index/index/main"
	    },
	    {
	      "type":"view",
	      "name":"爆品高佣",
	      "url":"http://wx.microcodor.com/index/index/main"
	    },
	    {
	      "name":"服务中心",
	      "sub_button":[
					{
						"type":"view",
						"name":"修改联盟ID",
						"url":"http://wx.microcodor.com/index/index/union"
					},
					{
						"type":"view",
						"name":"万能转链",
						"url":"http://wx.microcodor.com/index/index/switchlink"
					}
				]
	    }]
  	}';
        list($err, $res)  = $wechatUtil->api->create_menu($menu_json);
        if ($res!==null){
            return $res;
        }
        return json($err);
    }

    public function getMenu(){
        $wechatUtil = new WechatUtil();
        list($err, $menu) = $wechatUtil->api->get_menu();
        //var_dump($menu);
        if ($menu!==null){
            return $menu;
        }
        return json($err);
    }

    /**
    *   网页授权回调地址：http://wx.microcodor.com/wechat/index/auth_callback
     */
    public function auth_callback(){
        // api模块
        $api = new Api(
            array(
                'appId' => config('appID'),
                'appSecret'	=> config('appSecret'),
                'get_access_token' => function(){
                    // 用户需要自己实现access_token的返回
                    $wechatUtil = new WechatUtil();
                    $access_token = $wechatUtil->get_access_token();
                    return $access_token;
                },
                'save_access_token' => function($token) {
                    // 用户需要自己实现access_token的保存
                    Cache::set("access_data",$token,7000);
                }
            )
        );
        //snsapi_userinfo  snsapi_base
        list($err, $user_info) = $api->get_userinfo_by_authorize('snsapi_userinfo');
        if ($user_info !== null) {
            //将用户信息存在数据库中,同时将用户在数据库中唯一的标识保存在session中
            $array = [];

            $array['openId'] = $user_info->openid;
            $array['nickName'] = $user_info->nickname;
            $array['headImgUrl'] = $user_info->headimgurl;

            //我这里只存储了用户的openid,nickname,headimgurl
            // $model = Model('user');

            //$model->save();
            $user = User::get($array['openId']);

            //先判断用户数据是不是已经存储了，如果存储了获取用户在数据库中的唯一标识
            //$user_id = $model->where(['openid'=>$array['openid']])->getField('user_id');
            if($user){
                Session::set('openId',$user['openId']);
            }else{
                //throw  new Exception("null");
                $model = new User($array);
                $model ->save();
                //将用户在数据库中的唯一表示保存在session中
                Session::set('openId',$model['openId']);
            }

            //var_dump($user_info);
            //Log::write('MsgType:'.$user_info,'log');.'?openid='.$user_info->openid
            header('Location:'.$_REQUEST['state']);
            exit();
        } else {
            echo '授权失败！';
        }
    }

    public function getOpenId(){
        // api模块 - 包含各种系统主动发起的功能
        $cache =  new FileCache;
        // api模块
        $api = new Api(
            array(
                'appId' => config('appID'),
                'appSecret'	=> config('appSecret'),
                'get_access_token' => function(){
                    // 用户需要自己实现access_token的返回
                    $wechatUtil = new WechatUtil();
                    $access_token = $wechatUtil->get_access_token();
                    return $access_token;
                },
                'save_access_token' => function($token) {
                    // 用户需要自己实现access_token的保存
                    Cache::set("access_data",$token,7000);
                }
            )
        );

    }
}