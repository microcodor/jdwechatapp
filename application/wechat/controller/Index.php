<?php
/**
 * 使用Gaoming13-sdk握手
 * User: jinchun
 * Date: 2018/8/4
 * Time: 下午2:59
 */
namespace app\wechat\controller;

use app\wechat\utils\WechatUtil;
use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Wechat;
use think\Controller;
use think\Loader;



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

        // 开发者中心-配置项-AppID(应用ID)
        $appId = 'wxd92439ed67f18c2a';
        // 开发者中心-配置项-AppSecret(应用密钥)
        $appSecret = '711da457037ce5073af0689dd3ba8dbd';
        // 开发者中心-配置项-服务器配置-Token(令牌)
        $token = 'jdwechatapp';
        // 开发者中心-配置项-服务器配置-EncodingAESKey(消息加解密密钥)
        $encodingAESKey = '072vHYArTp33eFwznlSvTRvuyOTe5YME1vxSoyZbzaV';

        // wechat模块 - 处理用户发送的消息和回复消息

        $wechat = new Wechat(array(
            'appId' => $appId,
            'token' => 	$token,
            'encodingAESKey' =>	$encodingAESKey //可选
        ));
        // api模块 - 包含各种系统主动发起的功能
        $api = new Api(
            array(
                'appId' => $appId,
                'appSecret'	=> $appSecret,
                'get_access_token' => function(){
                    // 用户需要自己实现access_token的返回
                    return 'wechat_token';
                },
                'save_access_token' => function($token) {
                    // 用户需要自己实现access_token的保存
                    echo 'wechat_token', $token;
                }
            )
        );

        // 获取微信消息
        $msg = $wechat->serve();

        // 默认消息
        $default_msg = "/微笑  欢迎关注本测试号:\n 回复1: 回复文本消息\n 回复2: 回复图片消息\n 回复3: 回复语音消息\n 回复4: 回复视频消息\n 回复5: 回复音乐消息\n 回复6: 回复图文消息";

        // 用户关注微信号后 - 回复用户普通文本消息
        if ($msg->MsgType == 'event' && $msg->Event == 'subscribe') {

            $wechat->reply($default_msg);
            exit();
        }

        // 用户回复1 - 回复文本消息
        if ($msg->MsgType == 'text' && $msg->Content == '1') {

            $wechat->reply("hello world!");
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

            $wechat->reply(array(
                'type' => 'image',
                // 通过素材管理接口上传多媒体文件，得到的id
                'media_id' => 'Uq7OczuEGEyUu--dYjg7seTm-EJTa0Zj7UDP9zUGNkVpjcEHhl7tU2Mv8mFRiLKC'
            ));
            exit();
        }

        // 用户回复3 - 回复语音消息
        if ($msg->MsgType == 'text' && $msg->Content == '3') {

            $wechat->reply(array(
                'type' => 'voice',
                // 通过素材管理接口上传多媒体文件，得到的id
                'media_id' => 'rVT43tfDwjh4p1BV2gJ5D7Zl2BswChO5L_llmlphLaTPytcGcguBAEJ1qK4cg4r_'
            ));
            exit();
        }

        // 用户回复4 - 回复视频消息
        if ($msg->MsgType == 'text' && $msg->Content == '4') {

            $wechat->reply(array(
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

            $wechat->reply(array(
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

            $wechat->reply(array(
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

        // 默认回复默认信息
        $wechat->reply($default_msg);

        // 主动发送
        $api->send($msg->FromUserName, '这是我主动发送的一条消息');
    }
    public function createMenu(){
        // api模块 - 包含各种系统主动发起的功能
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
                    echo 'wechat_token', $token;
                }
            )
        );
        $menu_json = '{
	    "button":[
	    {
				"name":"京仓京配",
				"type":"view",
				"url":"http://microcodor.com"
	    },
	    {
	      "type":"view",
	      "name":"爆品高佣",
	      "url":"http://microcodor.com"
	    },
	    {
	      "name":"服务中心",
	      "sub_button":[
					{
						"type":"click",
						"name":"今日必推",
						"key":"MENU_RECENT_POSTS"
					},
					{
						"type":"click",
						"name":"推广榜单",
						"key":"MENU_RANDOM_POSTS"
					},
					{
						"type":"click",
						"name":"万能转链",
						"key":"MENU_HOTEST_POSTS"
					}
				]
	    }]
  	}';
        $api->create_menu($menu_json);
    }
}