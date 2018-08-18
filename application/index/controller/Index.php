<?php
namespace app\index\controller;

use app\index\model\Goods;
use app\index\model\User;
use app\index\model\UserConfig;
use app\jdunion\utils\JingDongUtil;
use app\unionjd\utils\JingDongBeeApi;
use app\wechat\utils\QRcode;
use app\wechat\utils\SimpleHtmlDom;
use app\wechat\utils\WechatUtil;
use stdClass;
use think\Cache;
use think\Controller;
use think\db;
use app\wechat\utils\Util;
use think\Exception;
use think\Log;
use think\response\Json;
use think\Session;


class Index extends Controller
{
    /**
     * 菜单首页设置
     */
    //snsapi_userinfo snsapi_base
    public function  main(){
        $wechatUtil = new WechatUtil();
        //snsapi_base   snsapi_userinfo
        $wechatUtil->web_auth("snsapi_userinfo",
            "http://wx.microcodor.com/wechat/index/auth_callback","http://wx.microcodor.com/index");
        exit();
    }
    /**
     * 重定向后真实菜单首页
     */
    public function index()
    {
        return view('navdiy');
    }

    /**
     * 服务中心->修改联盟ID
     * http://wx.microcodor.com/index/index/union
     */
    //snsapi_userinfo snsapi_base
    public function  union(){
        $wechatUtil = new WechatUtil();
        //snsapi_base   snsapi_userinfo
        $wechatUtil->web_auth("snsapi_userinfo",
            "http://wx.microcodor.com/wechat/index/auth_callback","http://wx.microcodor.com/index/index/union_main");
        exit();
    }
    /**
     * 重定向后真实修改联盟ID页
     */
    public function union_main()
    {
        //取数据库是否存取了联盟ID

        return view('union');
    }

    /**
     * 服务中心->万能转链
     * http://wx.microcodor.com/index/index/switchlink
     */
    //snsapi_userinfo snsapi_base
    public function  switchlink(){
        $wechatUtil = new WechatUtil();
        //snsapi_base   snsapi_userinfo
        $wechatUtil->web_auth("snsapi_userinfo",
            "http://wx.microcodor.com/wechat/index/auth_callback","http://wx.microcodor.com/index/index/switchlink_main");
        exit();
    }
    /**
     * 重定向后真实转链页
     */
    public function switchlink_main()
    {
        //取数据库是否存取了联盟ID
        return view('switchlink');
    }

    /**
    *   别人的短链转成自己的
     */
    public function switchUrl(){
        if (!isset($_GET['shortUrl'])){
            return null;
        }
        $str = $_GET['shortUrl'];
        //$txt='https://union-click.jd.com/jdc?d=i6hl5M';


        $re1='((?:http|https)(?::\\/{2}[\\w]+)(?:[\\/|\\.]?)(?:[^\\s"]*))';	# HTTP URL 1

        if ($c=preg_match_all ("/".$re1."/is", $str, $matches))
        {
            $shorturl=$matches[1][0];
            //print $shorturl;
            if (isset($shorturl)){
                $jingapi = new JingDongBeeApi();
                $skuId = $jingapi->getskuId($shorturl);
                $res = $jingapi->getOtherUrlToSelfUrl(urlencode($shorturl),UNIONID);
                if (!isset($res)){
                    $res = $jingapi->getOtherCouponUrlToSelfUrl(urlencode($shorturl),UNIONID);
                }
                $realurl = $res->$skuId;
                //var_dump($res->$skuId);

                if (isset($realurl)){
                    $newstr = str_replace($shorturl, $realurl, $str);
                    return $newstr;
                }
            }
        }
        return null;

    }
    /**
    *   京东联盟access_token,定期获取
     */
    public function getToken(){
        $jingdongutil = new JingDongUtil();
        if (!isset($_GET['code'])){
            $jumpurl = $jingdongutil->oauth2_authorize();
            header('Location:'.$jumpurl);
            exit();
        }
    }
    /**
     * 详情页授权入口
     * http://wx.microcodor.com/index/index/authDetail
     */
    //snsapi_userinfo snsapi_base
    public function  authDetail(){
        $wechatUtil = new WechatUtil();
        if (isset($_GET['id'])){
            //snsapi_base   snsapi_userinfo
            $wechatUtil->web_auth("snsapi_userinfo",
                "http://wx.microcodor.com/wechat/index/auth_callback","http://wx.microcodor.com/index/index/detail?id=".$_GET['id']);
        }else{
            $wechatUtil->web_auth("snsapi_userinfo",
                "http://wx.microcodor.com/wechat/index/auth_callback","http://wx.microcodor.com/index");
        }
        exit();
    }
    /**
     * 详情页
     */
    public function detail(){
        //Cache::clear();
        $id = $_GET['id'];
        //echo $id;
        //$goods = Goods::get($id);

        $jingdongutil = new JingDongBeeApi();
        $res = $jingdongutil->get_goods_info($id);
        //$res = json_decode($res);
        //var_dump($res);
        //var_dump($res->msg);
//        $goods = new stdClass();
//        if ($res&&$res->code===1&&sizeof($res->data)>0){
//            //var_dump(sizeof($res->data));
//            $goods = $res->data[0];
//            //var_dump($goods->imgUrl);
//        }
        $this->assign('goods', $res);
        return $this->view->fetch();
    }

    public function userconfig(){
        $openId = Session::get('openId');
        $openId = 'okYer5iBmUNBECeXX1uJk-3jGLrA';
        if ($openId){
            $userconfig = UserConfig::get($openId);
            if (isset($_POST['unionId'])&&isset($_POST['smallLink'])){
                if (!isset($userconfig)){
                    $data = ['openId' => $openId, 'smallLink' => $_POST['smallLink'], 'showQrcode' => $_POST['showQrcode'],'unionId' => $_POST['unionId']];
                    $userconfig = new UserConfig($data);
                    $userconfig->save();
                }else{
                    $userconfig->unionId = $_POST['unionId'];
                    $userconfig->smallLink = $_POST['smallLink'];
                    $userconfig->showQrcode = $_POST['showQrcode'];
                    $userconfig->save();
                }

            }else{//get
                if (!isset($userconfig)){
                    $data = ['openId' => $openId, 'smallLink' => 0, 'showQrcode' => 0,'unionId' => ''];
                    $userconfig = new UserConfig($data);
                    $userconfig->save();
                }
            }
            return json($userconfig);
        }
        return null;
    }
    /**
     * 主动推送消息
     */
    public function push(){
        $id = $_GET['id'];

        $jdutil = new JingDongBeeApi();
        $arr = $jdutil->get_push_text_image($id);
        //var_dump($arr);

        $openId = Session::get('openId');
        //$openId = 'okYer5iBmUNBECeXX1uJk-3jGLrA';
        //var_dump($openId);
        Log::write("openId:".$openId,'notice');
        $wechatUtil = new WechatUtil();
        if ($openId&&$arr){
            // 主动发送
            $res = $wechatUtil->api->send($openId, $arr['text']);
            if ($arr['image']){
                list($err, $mediaInfo) = $wechatUtil->api->add_material('image', $arr['image']);
                if ($mediaInfo!==null){
                    $arr = array('type'=>'image','media_id'=>$mediaInfo->media_id);
                    $wechatUtil->api->send($openId, $arr);
                }
            }
            if ($res){
                return json(array('result'=>true));
            }
        }
        return json(array('result'=>false));
    }

    public function download()
    {
        $filePath = RUNTIME_PATH.'/download/';
        $url = 'http://img14.360buyimg.com/n1/jfs/t6112/57/2756828393/393165/70fbe4f/5945f7d0N2f5ce3f7.jpg';
        $pos = strripos($url,'/');
        if ($pos){
            $fileName = substr($url,$pos);
            echo $fileName;
            $filePath = $filePath.$fileName;
            if (file_exists($filePath)){
                return $filePath;
            }
        }
        Util::downloadImage($url,
            RUNTIME_PATH.'/download/');
        return $filePath;
    }
    public function qrcode()
    {
        $url = 'http://img14.360buyimg.com/n1/jfs/t6112/57/2756828393/393165/70fbe4f/5945f7d0N2f5ce3f7.jpg';
        $value = $url;					//二维码内容

        $errorCorrectionLevel = 'L';	//容错级别
        $matrixPointSize = 2;			//生成图片大小

        //生成二维码图片
        $filename = RUNTIME_PATH.'/download/1534391113.png';
        $qrcode = new QRcode();
        $qrcode->png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);

//        $QR = $filename;				//已经生成的原始二维码图片文件
//
//
//        $QR = imagecreatefromstring(file_get_contents($QR));
//
//        //输出图片
//        imagepng($QR, $filename);
//        imagedestroy($QR);
//        return '<img src="qrcode.png" alt="使用微信扫描支付">';

    }

    public function createimage(){
        $im = imageCreatetruecolor(350,462);
        $backgroundcolor = imagecolorallocate($im,255,255,255);
        imagefill($im,0,0,$backgroundcolor);
        echo $im;
        $cover = imagecreatefromjpeg(RUNTIME_PATH.'/download/5945f7d0N2f5ce3f7.jpg');
        $qrcode = imagecreatefrompng(RUNTIME_PATH.'/download/1534391113.png');
        $cover_size = getimagesize(RUNTIME_PATH.'/download/5945f7d0N2f5ce3f7.jpg');
        $qrcode_size = getimagesize(RUNTIME_PATH.'/download/1534391113.png');
        echo 'cover_size width:'.$cover_size[0].',height:'.$cover_size[1];
        echo 'qrcode_size width:'.$qrcode_size[0].',height:'.$qrcode_size[1];
        imagecopy($im, $cover, 0, 0, 0, 0, $cover_size[0], $cover_size[1]);

        imagecopy($im, $qrcode, $cover_size[0]-$qrcode_size[0]-10, $cover_size[1]+10,0,0,$qrcode_size[0],$qrcode_size[1]);

        //添加文字 标题
        $font = PUBLIC_PATH.'/msyh.ttf';//字体文件
        $black = imagecolorallocate($im,0, 0, 0);//字体颜色 RGB
        $red = imagecolorallocate($im,255, 0, 0);//字体颜色 RGB
        $gray = imagecolorallocate($im,169, 169, 169);//字体颜色 RGB
        $fontSize = 10;   //字体大小
        $circleSize = 0; //旋转角度
        $left = 5;      //左边距
        $top = $cover_size[1]+20;       //顶边距
        $title = '【京东商城】喜乐猫 实用情侣充电宝 一万毫安 多模板可选';
        echo 'title:'.mb_strlen($title);
        $arr = Util::mb_str_split($title,18);
        echo $arr[0];
        imagefttext($im, $fontSize, $circleSize, $left, $top, $black, $font, $arr[0]);
        if ($arr[1]){
            $top = $top+20;
            imagefttext($im, $fontSize, $circleSize, $left, $top, $black, $font, $arr[1]);
        }
        //添加京东价
        $top = $top+20;
        imagefttext($im, $fontSize, $circleSize, $left, $top, $gray, $font, '京东价：59');

        $top = $top+20;
        imagefttext($im, $fontSize, $circleSize, $left, $top, $red, $font, '券后价：39');

        imageline($im, 0, 442, 350, 442, $gray);
        imagefttext($im, $fontSize-2, $circleSize, $left, 442+15, $gray, $font, '长按识别二维码，领取优惠券');

        //header("content-type: image/jpeg");
        imagepng($im,RUNTIME_PATH.'/download/tmp.png');
        imagedestroy($im);
    }

    public function nav(){
        return view('main');
        //return $text;
    }
    public function testcache(){
        $str = Session::get('abdcddd');

        if (!$str){
            Session::set('abdcddd','111111111111111');
        }else{
            echo 'print:'.$str;
        }
        Session::clear();
        echo '22';

    }
    public function redir(){
        header("Location:http://baidu.com");
        exit();
    }

    /**
    * 获取首页数据
     */
    public function getTabData(){
        $tabIndex = $_GET['tabIndex'];
        $sortIndex = $_GET['sortIndex'];
        $page = $_GET['curPage'];
        $size= $_GET['pageSize'];
        //dump("page:".$page);
        //dump("size:".$size);
        $goods = new Goods();
        $defaultOrder = 'publish_date';
        if ($tabIndex==0){
            $defaultOrder = 'publish_date';
        }else if ($tabIndex==1){
            $defaultOrder = 'commission';
        }else {
            $defaultOrder = 'sell_price';
        }

        $list = $goods->where('end_date','GT',date('Y-m-d H:i:s', time()))->order($defaultOrder,$sortIndex)->limit($page*$size,$size)->select();
        return json($list);
    }

    public function savedata(){
        $str = '🎁七夕好礼【照片定制充电宝】足一万毫安 大容量 不虚标💪创意模板 秀出个性 超薄小巧⚠ 新品冲量 

【京东商城】喜乐猫 实用情侣充电宝 一万毫安 多模板可选 
———————— 
京东价：￥99 
内购价：￥59 

👉领券+下单：https://union-click.jd.com/jdc?d=WWJ9yu';
        $flag = strstr($str, 'jd.com');
        echo "flag:".$flag."<br>";
        if ($flag){
            $hello = explode("\n", $str);
            echo "count:".count($hello)."<br>";
            $goods = new Goods();
            $goods->sku_id = 0;
            for($index=0;$index<count($hello);$index++)
            {

                if(strpos($hello[$index],'【京东商城】') !== false||strpos($hello[$index],'【京东】') !== false){
                    echo "sku_name:".$hello[$index];echo "</br>";
                    if ($hello[$index]){
                        $tmp_goods = $goods->where('sku_name', $hello[$index])->select();
                        //echo $tmp_goods->sku_name;
                        if ($tmp_goods){

                            return '数据已存在';
                        }
                        $goods->sku_name = $hello[$index];
                    }else{
                        $goods->sku_name = "【京东商城】";
                    }

                }else if(strpos($hello[$index],'京东价') !== false){
                    $price = substr($hello[$index],strpos($hello[$index],'￥')+3);
                    echo "price:".$price;echo "</br>";
                    $goods->price = $price;
                }else if(strpos($hello[$index],'内购价') !== false||strpos($hello[$index],'券后价') !== false){
                    $sell_price = substr($hello[$index],strpos($hello[$index],'￥')+3);
                    echo "sell_price:".$sell_price;echo "</br>";
                    $goods->sell_price = $sell_price;
                }else if(strpos($hello[$index],'微信抢购') !== false){
                    $str = substr($hello[$index],strpos($hello[$index],'：')+3);

                    $sku_id = mb_substr($str,0,strpos($str,' '));
                    if (!$sku_id){
                        $sku_id = 0;
                    }else{
                        $tmp_goods = $goods->where('sku_id', $sku_id)->select();
                        if ($tmp_goods){
                            return '数据已存在';
                        }
                    }
                    echo "sku_id:".$sku_id;echo "</br>";
                    $goods->sku_id = $sku_id;
                    $commission = substr($str,strpos($str,' ')+1);
                    $goods->commission = $commission;
                    echo "commission:".$commission;echo "</br>";
                }else if(strpos($hello[$index],'http') !== false){
                    $coupon_url = substr($hello[$index],strpos($hello[$index],'http'));
                    echo "coupon_url:".$coupon_url;echo "</br>";
                    $goods->coupon_url = $coupon_url;
                }


            }
            $goods->save();
        }
        //Log::write("111111111111",'notice');

    }

    public function test_redirect(){
        echo "test_redirect";
        $this->redirect("index/index/nav");
    }

    public function get_url(){
        //echo Util::get_redirect_url('https://union-click.jd.com/jdc?d=EMznpe');
        $url ="https://union-click.jd.com/jdc?d=EMznpe";
        //$url = 'http://localhost/jdwechatapp/public/index/index/test_redirect';
        //$url = "http://www.amazon.com/Bengoo-Portable-Desktop-Electric-Rechargeable/dp/tech-data/B01F70ZGYW%3FSubscriptionId%3DAKIAJWXT2MCY6ZQDW7VQ%26tag%3DASSOCIATETAG%26linkCode%3Dsp1%26camp%3D2025%26creative%3D386001%26creativeASIN%3DB01F70ZGYW";
        $curl = $this->get_all_redirects($url);
        //echo "runing curl...:".$rs;
        dump($curl);
    }
    private function get_redirect_url($url){
        $redirect_url = null;

        $url_parts = @parse_url($url);
        if (!$url_parts) return false;
        if (!isset($url_parts['host'])) return false; //can't process relative URLs
        if (!isset($url_parts['path'])) $url_parts['path'] = '/';

        $sock = fsockopen($url_parts['host'], (isset($url_parts['port']) ? (int)$url_parts['port'] : 80), $errno, $errstr, 30);
        if (!$sock) return false;

        $request = "HEAD " . $url_parts['path'] . (isset($url_parts['query']) ? '?'.$url_parts['query'] : '') . " HTTP/1.1\r\n";
        $request .= 'Host: ' . $url_parts['host'] . "\r\n";
        $request .= "Connection: Close\r\n\r\n";
        fwrite($sock, $request);
        $response = '';
        while(!feof($sock)) $response .= fread($sock, 8192);
        fclose($sock);

        if (preg_match('/^Location: (.+?)$/m', $response, $matches)){
            return trim($matches[1]);
        } else {
            return false;
        }
    }
    private function get_all_redirects($url){
        $redirects = array();
        while ($newurl = $this->get_redirect_url($url)){
            if (in_array($newurl, $redirects)){
                break;
            }
            $redirects[] = $newurl;
            $url = $newurl;
        }
        return $redirects;
    }
    private function get_final_url(){
        $redirects = $this->get_all_redirects($this->url);

        if (count($redirects)>0){
            return array_pop($redirects);
        } else {
            return $this->url;
        }
    }
    public function test_user(){
        //        $user = Db::table('user')->where('openId',323221122)->find();
//        if (!$user){
//            $data = ['openId' => '323221122', 'nickName' => 'jinchun','headImgUrl' => 'htttp://wx.microcodor.com'];
//            Db::table('user')->insert($data);
//        }

        //$user = User::get(323221122);
        //$data = ['openId' => '323221123', 'nickName' => 'jiwei','headImgUrl' => 'htttp://wx.microcodor.com'];
        $user = new User();
        //$user->save();
        $nickName = Goods::get(3)['sku_name'];
        //echo $nickName;
        return "this is main page：".$nickName;
    }
    public function test_goods(){
        //        $user = Db::table('user')->where('openId',323221122)->find();
//        if (!$user){
//            $data = ['openId' => '323221122', 'nickName' => 'jinchun','headImgUrl' => 'htttp://wx.microcodor.com'];
//            Db::table('user')->insert($data);
//        }

        //$user = User::get(323221122);
        $data = ['sku_id' => 31240578838, 'sku_name' => '好遇 榛子夹心巧克力 12粒 四色可选',
            'image' => 'http://static.dakabl.com/upload/2018-08-08/b70ffa48-6791-4897-9bf2-0d8ab228f38b_s.jpg',
            'commission' => '20', 'coupon_price' => '15','coupon_url' => 'http://t.cn/RDfZh0V','price' => '36.8','sell_price' => '21.8',
            'start_date' => date('Y-m-d H:i:s', time()),
            'end_date' => date('Y-m-d H:i:s', strtotime('+1 day'))];
        $goods = new Goods($data);
        //$goods->save();
        $nickName = Goods::get(31240578838)['sku_name'];
        echo "nickName:".$nickName;
        return "this is main page：".$nickName;
    }

    public function get_daka_data(){
        // 新建一个Dom实例
        $dom = new SimpleHtmlDom();
        $html = $dom->file_get_html('http://www.dakajd.com/');
        foreach($html->find('div.media-text-area') as $e){
            //echo $e->outertext . '<br>';
            $goods = new Goods();

            $hello = explode('<br/>',$e->outertext);
            for($index=0;$index<count($hello);$index++)
            {
                if(strpos($hello[$index],'【京东商城】') !== false){
                    echo "sku_name:".$hello[$index];echo "</br>";
                    if ($hello[$index]){
                        $goods->sku_name = $hello[$index];
                    }else{
                        $goods->sku_name = "【京东商城】";
                    }

                }else if(strpos($hello[$index],'京东价：') !== false){
                    $price = substr($hello[$index],strpos($hello[$index],'：')+3);
                    echo "price:".$price;echo "</br>";
                    $goods->price = $price;
                }else if(strpos($hello[$index],'内购价：') !== false){
                    $sell_price = substr($hello[$index],strpos($hello[$index],'：')+3);
                    echo "sell_price:".$sell_price;echo "</br>";
                    $goods->sell_price = $sell_price;
                }else if(strpos($hello[$index],'微信抢购：') !== false){
                    $str = substr($hello[$index],strpos($hello[$index],'：')+3);

                    $sku_id = mb_substr($str,0,strpos($str,' '));
                    echo "sku_id:".$sku_id;echo "</br>";
                    $goods->sku_id = $sku_id;
                    $commission = substr($str,strpos($str,' ')+1);
                    $goods->commission = $commission;
                    echo "commission:".$commission;echo "</br>";
                }else if(strpos($hello[$index],'领券：') !== false){
                    $coupon_url = substr($hello[$index],strpos($hello[$index],'：')+3);
                    echo "coupon_url:".$coupon_url;echo "</br>";
                    $goods->coupon_url = $coupon_url;
                }


            }
            $goods->save();
            echo "___________________</br>";

        }



// 从url中加载

    }

    public function daka_data(){
        $wechatUtil = new WechatUtil();

        $url = "http://www.dakajd.com/api/goods?page=1&pageSize=50&appId=".""."&nonceStr=1531470218&sign=4cc90677c17e7ad48a68b6dff1ba7728";
        $result = $wechatUtil->http_get_result("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".config("appID")."&secret=".config("appSecret"));

    }
}
