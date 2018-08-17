<?php
/**
 * Created by PhpStorm.
 * User: jinchun
 * Date: 2018/8/11
 * Time: 上午9:42
 */
namespace app\wechat\utils;
use app\index\model\Goods;
use think\Log;

class Util {
    /**
     * 递归检测url重定向地址, 直到重定向到rule所指地址
     * 返回该地址
     *
     * @param string $url 待检测的地址
     * @param string $rule 匹配的地址
     * @return mixed
     */
    public static function get_redirect_url($url)
    {
        $headers = get_headers($url, TRUE);
        print_r($headers);

//输出跳转到的网址
        echo $headers['Location'];

    }

    public static function save_data($str){
//        $str = '【京东】【领券直降】赛丹狐（SAIDANHU） 户外运动T恤 情侣透气吸湿快干圆领短袖休闲健身T恤速干恤 彩兰【男】 XL
//——————————————
//京东价: ￥69.00。
//券后价: ￥39.00。
//领券抢购: https://union-click.jd.com/jdc?d=oRMFwF';
        $flag = strstr($str, 'jd.com');
        //echo "flag:".$flag."<br>";
        Log::write("flag:".$flag,'save_data');
        if ($flag){
            $hello = explode("\n", $str);
            //echo "count:".count($hello)."<br>";
            Log::write("count:".count($hello),'save_data');
            $goods = new Goods();
            $goods->sku_id = 0;
            for($index=0;$index<count($hello);$index++)
            {

                if(strpos($hello[$index],'【京东商城】') !== false||strpos($hello[$index],'【京东】') !== false){
                    //echo "sku_name:".$hello[$index];echo "</br>";
                    Log::write("sku_name:".$hello[$index],'save_data');

                    if ($hello[$index]){
                        $tmp_goods = $goods->where('sku_name', $hello[$index])->select();
                        if ($tmp_goods){
                            Log::write('数据已存在','save_data');
                            return '数据已存在';
                        }
                        $goods->sku_name = $hello[$index];
                    }else{
                        $goods->sku_name = "【京东商城】";
                    }

                }else if(strpos($hello[$index],'京东价') !== false){
                    $price = substr($hello[$index],strpos($hello[$index],'￥')+3);
                    //echo "price:".$price;echo "</br>";
                    Log::write("price:".$price,'save_data');
                    $goods->price = $price;
                }else if(strpos($hello[$index],'内购价') !== false||strpos($hello[$index],'券后价') !== false){
                    $sell_price = substr($hello[$index],strpos($hello[$index],'￥')+3);
                    //echo "sell_price:".$sell_price;echo "</br>";
                    Log::write("sell_price:".$sell_price,'save_data');
                    $goods->sell_price = $sell_price;
                }else if(strpos($hello[$index],'微信抢购') !== false){
                    $str = substr($hello[$index],strpos($hello[$index],'：')+3);

                    $sku_id = mb_substr($str,0,strpos($str,' '));
                    if (!$sku_id){
                        $sku_id = 0;
                    }else{
                        $tmp_goods = $goods->where('sku_id', $sku_id)->select();
                        if ($tmp_goods){
                            Log::write('数据已存在','save_data');
                            return '数据已存在';
                        }
                    }
                    //echo "sku_id:".$sku_id;echo "</br>";
                    Log::write("sku_id:".$sku_id,'save_data');
                    $goods->sku_id = $sku_id;
                    $commission = substr($str,strpos($str,' ')+1);
                    $goods->commission = $commission;
                    Log::write("commission:".$commission,'save_data');
                    //echo "commission:".$commission;echo "</br>";
                }else if(strpos($hello[$index],'http') !== false){
                    $coupon_url = substr($hello[$index],strpos($hello[$index],'http'));
                    //echo "coupon_url:".$coupon_url;echo "</br>";
                    Log::write("coupon_url:".$coupon_url,'save_data');
                    $goods->coupon_url = $coupon_url;
                }


            }
            if ($goods->save()){
                Log::write('数据存储成功','save_data');
                return '数据存储成功';
            }else{
                Log::write('数据存储异常','save_data');
            }

        }
        //Log::write("111111111111",'notice');

    }

    public static function downloadImage($url, $path = 'images/'){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);
        $filename = pathinfo($url, PATHINFO_BASENAME);
        $resource = fopen($path . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);
     }

    /**
     * Convert a string to an array
     * @param string $str
     * @param number $split_length
     * @return multitype:string
     */
    public static function mb_str_split($str,$split_length=1,$charset="UTF-8"){
        if(func_num_args()==1){
            return preg_split('/(?<!^)(?!$)/u', $str);
        }
        if($split_length<1)return false;
        $len = mb_strlen($str, $charset);
        $arr = array();
        for($i=0;$i<$len;$i+=$split_length){
            $s = mb_substr($str, $i, $split_length, $charset);
            $arr[] = $s;
        }
        return $arr;
    }
    public static function curl_get_302($url) {
        $ch = curl_init();
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL,  $url);
        curl_setopt($ch,  CURLOPT_FOLLOWLOCATION, 1); // 302 redirect
        $data = curl_exec($ch);
        $Headers =  curl_getinfo($ch);
        curl_close($ch);
        if ($data != $Headers)
            return  $Headers["url"];
        else
            return false;
    }
    public static function convertUrlQuery($query){
        $query = str_replace("?", "&", $query);
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[@$item[0]] = @$item[1];
        }
        return $params;
    }

}