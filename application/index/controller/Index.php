<?php
namespace app\index\controller;

use app\index\model\Goods;
use app\index\model\User;
use app\wechat\utils\SimpleHtmlDom;
use app\wechat\utils\WechatUtil;
use think\Controller;
use think\db;
use app\wechat\utils\Util;
use think\Log;


class Index extends Controller
{
    public function index()
    {

        return view('index');
    }
    //snsapi_userinfo snsapi_base
    public function  main(){
        $wechatUtil = new WechatUtil();
        $wechatUtil->web_auth("snsapi_userinfo",
            "http://wx.microcodor.com/wechat/index/auth_callback","http://wx.microcodor.com/index");
    }
    public function nav(){
        return view('navdiy');
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
        $data = ['openId' => '323221123', 'nickName' => 'jiwei','headImgUrl' => 'htttp://wx.microcodor.com'];
        $user = new User($data);
        //$user->save();
        $nickName = User::get(323221123)['nickName'];
        echo $nickName;
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
