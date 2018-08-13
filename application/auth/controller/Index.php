<?php
/**
 * Created by PhpStorm.
 * User: jinchun
 * Date: 2018/8/5
 * Time: 下午8:59
 */
namespace app\auth\controller;
use app\index\model\User;
use app\wechat\utils\WechatUtil;
use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Utils\FileCache;
use think\Cache;
use think\Controller;
use think\Exception;

/**
* 原生测试页
 */
class Index extends Controller
{
    //jssdk授权前信息获取
    public function index()
    {

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
        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $jsapi_config =  $api->get_jsapi_config($url);
        return json($jsapi_config);
    }
    // 暂不使用
    public function auth(){
        //授权完跳转的网址
        $path = $_REQUEST['path'];
        //echo $path;
        //用户同意授权后回调的网址.必须使用url对回调网址进行编码，我们也将授权完跳转对网址,
        //http://wx.microcodor.com/auth/index/callBack
            //$redirect_uri = urlencode('http://'.$_SERVER['HTTP_HOST'].'/auth/index/callBack');
        $redirect_uri = urlencode('http://wx.microcodor.com/auth/index/callBack');
        //echo $redirect_uri;
        header('Location:https://open.weixin.qq.com/connect/oauth2/authorize?appid='
            .config('appID').'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_userinfo&state='.$path.
            '#wechat_redirect');
    }
    //获取code后的回调函数
    public function callBack(){
        //获取到的code
        $code = $_REQUEST['code'];

        //授权结束后的回调网址
        $path = $_REQUEST['state'];

        //获取access_token
        $curl = curl_init();

        curl_setopt($curl,CURLOPT_URL,'https://api.weixin.qq.com/sns/oauth2/access_token?appid='
            .config('appID').'&secret='.config('appSecret').'&code='.$code.'&grant_type=authorization_code ');

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

        //获取access_token和openid,转换为数组
        $data = json_decode(curl_exec($curl),true);

        //如果获取成功，根据access_token和openid获取用户的基本信息
        if($data != null && $data['access_token']){

            //获取用户的基本信息，并将用户的唯一标识保存在session中
            curl_setopt($curl,CURLOPT_URL,'https://api.weixin.qq.com/sns/userinfo?access_token='
                .$data['access_token'].'&openid='.$data['openid'].'&lang=zh_CN');

            $user_data = json_decode(curl_exec($curl),true);

            if($user_data != null && $user_data['openid']){

                curl_close($curl);
                //将用户信息存在数据库中,同时将用户在数据库中唯一的标识保存在session中
                $array = [];

                $array['openId'] = $user_data['openid'];
                $array['nickName'] = $user_data['nickname'];
                $array['headImgUrl'] = $user_data['headimgurl'];

                //我这里只存储了用户的openid,nickname,headimgurl
               // $model = Model('user');

                //$model->save();
                $user = User::get($array['openId']);

                //先判断用户数据是不是已经存储了，如果存储了获取用户在数据库中的唯一标识
                //$user_id = $model->where(['openid'=>$array['openid']])->getField('user_id');
                if($user){
                    session('openId',$user['openId']);
                }else{
                    throw  new Exception("null");
                    $model = new User($array);
                    $model ->save();
                    //将用户在数据库中的唯一表示保存在session中
                    session('openId',$model['openId']);
                }
                //跳转网页
                header('Location:'.$path);
            }else{

                curl_close($curl);

                exit('获取用户信息失败！');

            }
        }else{

            curl_close($curl);

            exit('微信授权失败');
        }
    }

}