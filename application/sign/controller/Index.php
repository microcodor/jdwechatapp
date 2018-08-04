<?php
/**
 * Created by PhpStorm.
 * User: jinchun
 * Date: 2018/8/4
 * Time: 下午3:44
 */
namespace app\sign\controller;

//这两个控制器是我本来就有的这里就不做演示  可以不继承 这个控制器用到的东西 不依靠外面的东西
use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Wechat;

/**
 * 与微信公众平台握手
 */
define("TOKEN", "jdwechatapp");//定义你公众号自己设置的token
define("APPID", "wxd92439ed67f18c2a");//填写你微信公众号的appid 千万要一致啊
define("APPSECRET", "711da457037ce5073af0689dd3ba8dbd");//填写你微信公众号的appsecret  千万要记得保存 以后要看的话就只有还原了  保存起来 有益无害
class Index
{
    //判断是介入还是用户  只有第一次介入的时候才会返回echostr
    function index()
    {
        //这个echostr呢  只有说验证的时候才会echo  如果是验证过之后这个echostr是不存在的字段了
        //$echoStr = $_GET["echostr"];
        if (!isset($_GET['echostr'])) { //echostr不存在的话去执行wechatObj类中的responseMsg函数
            $this->responseMsg();
        }else{
            $echoStr = $_GET["echostr"];
            if ($this->checkSignature()) {
                echo $echoStr;
                //如果你不知道是否验证成功  你可以先echo echostr 然后再写一个东西
                exit;
            }
        }

    }//index end
    //验证微信开发者模式接入是否成功
    private function checkSignature()
    {
        //signature 是微信传过来的 类似于签名的东西
        $signature = $_GET["signature"];
        //微信发过来的东西
        $timestamp = $_GET["timestamp"];
        //微信传过来的值  什么用我不知道...
        $nonce     = $_GET["nonce"];
        //定义你在微信公众号开发者模式里面定义的token
        $token  = TOKEN;
        //三个变量 按照字典排序 形成一个数组
        $tmpArr = array(
            $token,
            $timestamp,
            $nonce
        );
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        //哈希加密  在laravel里面是Hash::
        $tmpStr = sha1($tmpStr);
        //按照微信的套路 给你一个signature没用是不可能的 这里就用得上了
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }// checkSignature end

    private function responseMsg(){
        // 这是使用了Memcached来保存access_token
        array(
            'type'=>'memcached',
            'host'=>'localhost',
            'port'=>'11211',
            'prefix'=>'think',
            'expire'=>0
        );

        // 开发者中心-配置项-AppID(应用ID)
        $appId = APPID;
        // 开发者中心-配置项-AppSecret(应用密钥)
        $appSecret = APPSECRET;
        // 开发者中心-配置项-服务器配置-Token(令牌)
        $token = TOKEN;
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

        // 回复文本消息
        if ($msg->MsgType == 'text' && $msg->Content == '你好') {
            $wechat->reply("你也好！ - 这是我回复的额！");
        } else {
            $wechat->reply("听不懂！ - 这是我回复的额！");
        }

        // 主动发送
        $api->send($msg->FromUserName, '这是我主动发送的一条消息');
    }

    //构建一个发送请求的curl方法  微信的东西都是用这个 直接百度
    function https_request($url, $data = null)
    {
        //这个方法我不知道是怎么个意思  我看都是这个方法 就copy过来了
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }//https_request end
} //classend
