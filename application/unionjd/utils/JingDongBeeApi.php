<?php
/**
 * 京东开源测试API调用
 * User: jinchun
 * Date: 2018/8/15
 * Time: 上午11:54
 * data,[skuId:商品id],[skuName:商品名称],[skuUrl:商品URL地址],[shopName:店铺名称],[shopUrl:店铺地址],
 * [imgUrl:图片地址],[pcPrice:pc价格],[wlPrice:无线价格],[exPrice:专享价格],[couponTab:优惠券标签],
 * [couponNote:优惠券话术],[discountPrice:商品折扣金额],[discountRate:折扣],
 * [startTime:推广开始时间],[endTime:推广结束时间],[isLock:锁定计划标志],[manJianNote:满减话术],[pcCommission: pc佣金金额],
 * [pcCommissionShare: PC佣金比例],[wlCommission: wl佣金金额],[wlCommissionShare:无线佣金比例],[inOrderComm: 排序标示30天引入佣金金额],
 * [inOrderCount: 排序标示30天引入量],[plan: 计划],[isPlan: 定向计划],[prmTab: 促销标签],[realRate: 商品几折],[adowner: 广告主],
 * [vid:商家id],[total:商品数量],[resultCode：接口调用是否成功（200：接口返回成功）]
 */
namespace app\unionjd\utils;

use app\wechat\utils\QRcode;
use app\wechat\utils\Util;

define('APP_KEY','39ADDBACC0D5E0CFD90892D8D45FE3C4');
define('APP_SECRET','d751c9481d524133a07167632b6624e4');
define('REDIRECT_URI', 'http://wx.microcodor.com/unionjd/index/oauth2');
define('JD_TOKEN', '7c54f31a-6f9e-4d12-b3bd-72dd89cd01c6');
define('TID', '1413877440');
define('PID', '2011472183_0_1413877440');
define('UNIONID', '2011472183');
class JingDongBeeApi{
    function __construct(){

    }

    /**
    *   推送文案,推送图片
     * 模版
     *  韩国大牌爱敬网红气垫 118.8抢购！全网最低价！  买就送替换装！只为冲量 必须抢～ 收货不满意随便退 我们出运费！放心购！

        【圆通包邮】爱敬 气垫BB霜  正装加替换装（大牌 各大网红最爱）正品保证 假一赔十！
        ————————
        京东价：¥188.8
        内购价：¥118.8
        领券+下单：https://union-click.jd.com/jdc?d=oZB07e
     */
    public function get_push_text_image($skuId, $unionId=UNIONID,$isNeedQrcode=true){
        $text = '';
        $imageUrl = '';
        $res = $this->getCouponSkuInfo($skuId);
        //echo "res:".$res;
        $url = 'https://jd.com';
        $title = '';
        $pcPrice = '';
        $yhPrice = '';
        if ($res){//无优惠券会返回空
            $imageUrl = 'http://img14.360buyimg.com/n1/'.$res->imageurl;
            $url_res = $this->getSkuAndCouponUrl(urlencode('https:'.$res->couponList[0]->link), $skuId, $unionId);
            if ($url_res){
                $url = $url_res->couponCode;
                $title = $res->skuName;
                $pcPrice = $res->pcPrice;
                $yhPrice = $res->wlPrice;
                $text = $res->skuName.'\n'
                    .'\n'
                    .$res->skuName.'\n'
                    .'————————\n'
                    .'京东价：¥'.$res->pcPrice.'\n'
                    .'内购价：¥'.$res->wlPrice.'\n'
                    .'领券+下单：'.$url;
            }

        }else{
            $res = $this->get_goods_info($skuId);
            if ($res){
                $title = $res->goodsName;
                $pcPrice = $res->unitPrice;
                $yhPrice = $res->wlUnitPrice;
                $imageUrl = $res->imgUrl;
                $url_res = $this->getUrlByUnionId($skuId, $unionId);
                if ($url_res){
                    $url = $url_res->$skuId;
                    $text = $res->goodsName.'\n'
                        .'<br />'
                        .$res->goodsName.'\n'
                        .'————————\n'
                        .'京东价：¥'.$res->unitPrice.'\n'
                        .'内购价：¥'.$res->wlUnitPrice.'\n'
                        .'领券+下单：'.$url;
                }
            }else{
                $text = '暂无相关商品信息！';
            }
        }
        $shareImagePath = '';
        //生成需要推送的图片
        if ($isNeedQrcode){//需要二维码
            //下载封面图片
            $coverImage = $this->download($imageUrl);
            //生成短链对应二维码图片
            $qrcodeImage = $this->qrcode($url,$skuId);
            $shareImagePath = $this->createShareImage($skuId,$coverImage,$qrcodeImage,$title, $pcPrice, $yhPrice);
            if (!isset($shareImagePath)){
                $shareImagePath = $coverImage;
            }
            unlink($coverImage);
            unlink($qrcodeImage);
        }else{
            $shareImagePath = $this->download($imageUrl);
        }

        return array('text'=>$text,'image'=>$shareImagePath);
    }
    //下载商品封面图片
    public function download($url)
    {
        $filePath = RUNTIME_PATH.'/download/';
        //$url = 'http://img14.360buyimg.com/n1/jfs/t6112/57/2756828393/393165/70fbe4f/5945f7d0N2f5ce3f7.jpg';
        $pos = strripos($url,'/');
        if ($pos){
            $fileName = substr($url,$pos);
            //echo $fileName;
            $filePath = $filePath.$fileName;
            if (file_exists($filePath)){
                return $filePath;
            }
        }
        Util::downloadImage($url,
            RUNTIME_PATH.'/download/');
        return $filePath;
    }
    //
    public function qrcode($url,$skuId)
    {
        //$url = 'http://img14.360buyimg.com/n1/jfs/t6112/57/2756828393/393165/70fbe4f/5945f7d0N2f5ce3f7.jpg';
        $value = $url;					//二维码内容

        $errorCorrectionLevel = 'L';	//容错级别
        $matrixPointSize = 2;			//生成图片大小

        //生成二维码图片
        $filename = RUNTIME_PATH.'/download/qrcode_'.$skuId.'.png';
        $qrcode = new QRcode();
        $qrcode->png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
        return $filename;

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

    public function createShareImage($skuId,$coverPath, $qrcodePath,$title,$pcPrice,$yhPrice){
        $im = imageCreatetruecolor(350,462);
        $backgroundcolor = imagecolorallocate($im,255,255,255);
        imagefill($im,0,0,$backgroundcolor);
       // echo $im;
        $cover = imagecreatefromjpeg($coverPath);
        $qrcode = imagecreatefrompng($qrcodePath);
        $cover_size = getimagesize($coverPath);
        $qrcode_size = getimagesize($qrcodePath);
        //echo 'cover_size width:'.$cover_size[0].',height:'.$cover_size[1];
        //echo 'qrcode_size width:'.$qrcode_size[0].',height:'.$qrcode_size[1];
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
        //$title = '【京东商城】喜乐猫 实用情侣充电宝 一万毫安 多模板可选';
        //echo 'title-length:'.mb_strlen($title);
        $arr = Util::mb_str_split($title,18);
        if ($arr){
            //echo $arr[0];
            imagefttext($im, $fontSize, $circleSize, $left, $top, $black, $font, $arr[0]);
            if (count($arr)>1&&$arr[1]){
                $top = $top+20;
                imagefttext($im, $fontSize, $circleSize, $left, $top, $black, $font, $arr[1]);
            }
        }

        //添加京东价
        $top = $top+20;
        imagefttext($im, $fontSize, $circleSize, $left, $top, $gray, $font, '京东价：'.$pcPrice);

        $top = $top+20;
        imagefttext($im, $fontSize, $circleSize, $left, $top, $red, $font, '券后价：'.$yhPrice);

        imageline($im, 0, 442, 350, 442, $gray);
        imagefttext($im, $fontSize-2, $circleSize, $left, 442+15, $gray, $font, '长按识别二维码，领取优惠券');

        $sharePath = RUNTIME_PATH.'/download/share_'.$skuId.'.png';
        //header("content-type: image/jpeg");
        imagepng($im,$sharePath);
        imagedestroy($im);
        return $sharePath;
    }
    /**
    *   根据别人的短链获取SKUID
     */
    public function getskuId($shorturl){
        //$shorturl = "https://union-click.jd.com/jdc?d=i6hl5M";
        $html = file_get_contents($shorturl);
        //echo $html;
        preg_match("/hrl='(\S+)'/",$html, $regs);
        if(count($regs)>1){
            $longurl = $regs[1];
            //var_dump($longurl);
            //使用Curl获取长链接302跳转的地址
            $url = Util::curl_get_302($longurl);
           //var_dump($url);
            if($url){
                //从链接中获取sku参数值
                $sku = Util::convertUrlQuery($url)["sku"];
                return $sku;
            }else{
                return false;
            }
        }
    }

    /**
    *   优惠商品列表
     */
    public function get_coupon_goods($from, $pageSize){
        $res = $this->http_get_result('https://jd.open.beeapi.cn/union/queryPreferentialGoods?from='.$from.'&pageSize='.$pageSize);
        $res = json_decode($res);
        if ($res&&$res->code===1&&sizeof($res->data)>0){
            //var_dump(sizeof($res->data));
            return $res->data;
            //var_dump($goods->imgUrl);
        }
        return false;
    }
    /**
     *   单个优惠商品信息
     */
    public function get_goods_info($skuIds){
        $res = $this->http_get_result('https://jd.open.beeapi.cn/union/getGoodsInfo?skuIds='.$skuIds);
        $res = json_decode($res);
        if ($res&&$res->code===1&&sizeof($res->data)>0){
            //var_dump(sizeof($res->data));
            return $res->data[0];
            //var_dump($goods->imgUrl);
        }
        return false;
    }
    /**
     *   联盟微信手q通过unionId获取推广链接
     */
    public function getUrlByUnionId($skuIds,$unionId,$positionId='', $pid=''){
        $res = $this->http_get_result('https://jd.open.beeapi.cn/union/getWXSQCodeByUnionId?materialIds='.$skuIds.'&unionId='.$unionId.'&positionId='.$positionId.'&pid='.$pid);
        $res = json_decode($res);
        if ($res&&$res->code===1&&$res->data){
            //var_dump(sizeof($res->data));
            return $res->data;
            //var_dump($goods->imgUrl);
        }
        return false;
    }
    /**
     *   商品优惠券二合一转链
     */
    public function getSkuAndCouponUrl($couponUrl,$skuIds,$unionId, $positionId='', $pid=''){
        $res = $this->http_get_result('https://jd.open.beeapi.cn/union/getCouponCodeByUnionId?materialIds='.$skuIds.'&unionId='.$unionId.'&positionId='.$positionId.'&pid='.$pid.'&couponUrl='.$couponUrl);
        $res = json_decode($res);
        if ($res&&$res->code===1&&sizeof($res->data)>0){
            //var_dump(sizeof($res->data));
            return $res->data[0];
            //var_dump($goods->imgUrl);
        }
        return false;
    }
    /**
     *   把别人的微信手Q短链转成自己的微信手Q短链
     */
    public function getOtherUrlToSelfUrl($shortUrl,$unionId){
        $res = $this->http_get_result('https://jd.open.beeapi.cn/union/short2WXSQ?shortUrl='.$shortUrl.'&unionId='.$unionId);
        $res = json_decode($res);
        if ($res&&$res->code===1&&$res->data){
            //var_dump(sizeof($res->data));
            return $res->data;
            //var_dump($goods->imgUrl);
        }
        return false;
    }
    /**
     *   别人的二合一短链转成自己的二合一短链
     */
    public function getOtherCouponUrlToSelfUrl($shortUrl,$unionId){
        $res = $this->http_get_result('https://jd.open.beeapi.cn/union/short2CouponCode?shortUrl='.$shortUrl.'&unionId='.$unionId);
        $res = json_decode($res);
        if ($res&&$res->code===1&&sizeof($res->data)>0){
            //var_dump(sizeof($res->data));
            return $res->data[0];
            //var_dump($goods->imgUrl);
        }
        return false;
    }
    /**
     *   优惠券商品查询(单个／集合)
     */
    public function getCouponSkuInfo($skuIdList, $pageIndex='', $pageSize='', $cid3='', $goodsKeyword='', $priceFrom='', $priceTo=''){
        $res = $this->http_get_result('https://jd.open.beeapi.cn/union/queryCouponGoods?skuIdList='.$skuIdList);
        $res = json_decode($res);
        if ($res&&$res->code===1&&$res->data){
            //var_dump(sizeof($res->data));
            return $res->data->goodsList[0];
            //var_dump($goods->imgUrl);
        }
        return false;
    }











    //生成auth的url
    public function oauth2_authorize($state='oauth2_authorize', $scope='read', $view='wap'){
        $url = 'https://oauth.jd.com/oauth/authorize?response_type=code&client_id='.APP_KEY.'&redirect_uri='.REDIRECT_URI.
            '&scope='.$scope.'&state='.$state.'&view='.$view;
        return $url;
    }

    public function  oauth2_access_token($code, $state='oauth2_authorize'){
        $url = 'https://oauth.jd.com/oauth/token?grant_type=authorization_code&client_id='.APP_KEY.'&redirect_uri='.REDIRECT_URI.'&code='.$code.'&state='.$state.'&client_secret='.APP_SECRET;
        $res = $this->http_get_result($url);
        $res = mb_convert_encoding($res, 'UTF-8', 'GBK');
        return $res;

    }

    /**
     * 使用curl实现GET请求
     */
    private function http_get_result($url) {
        $oCurl = curl_init();
        if(stripos($url, "https://")!==FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($oCurl);
        $status = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($status["http_code"])==200)
            return $content;
        else
            return false;
    }
}