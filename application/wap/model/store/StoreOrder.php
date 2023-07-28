<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/20
 */

namespace app\wap\model\store;


use app\wap\model\store\StoreCombination;
use app\wap\model\store\StoreOrderCartInfo;
use app\wap\model\store\StoreOrderStatus;
use app\wap\model\store\StoreProductReply;
use app\wap\model\user\User;
use app\wap\model\user\UserNum;
use app\wap\model\user\UserAddress;
use app\wap\model\user\UserBill;
use app\wap\model\user\WechatUser;
use basic\ModelBasic;
use behavior\wap\StoreProductBehavior;
use behavior\wechat\PaymentBehavior;
use service\HookService;
use service\JsonService;
use app\core\util\SystemConfigService;
use service\UtilService;
use app\core\util\WechatService;
use app\core\util\WechatTemplateService;
use think\Cache;
use think\Db;
use think\Request;
use think\Url;
use traits\ModelTrait;
use app\admin\model\system\SystemConfig;

class StoreOrder extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];
    protected static $payType = ['weixin'=>'微信支付','yue'=>'餘額支付','offline'=>'线下支付'];
    protected static $payTypeen = ['weixin'=>'微信支付','yue'=>'Balance payment','offline'=>'线下支付'];

    protected static $deliveryType = ['send'=>'商家配送','express'=>'快递配送'];

    protected function setAddTimeAttr()
    {   
        return time();
    }

    protected function setCartIdAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    protected function getCartIdAttr($value)
    {
        return json_decode($value,true);
    }

    public static function getOrderPriceGroup($cartInfo)
    {

        $storePostage = floatval(SystemConfigService::get('store_postage'))?:0;
        $storeFreePostage =  floatval(SystemConfigService::get('store_free_postage'))?:0;
        $totalPrice = self::getOrderTotalPrice($cartInfo);
        $costPrice = self::getOrderCostPrice($cartInfo);
        if(!$storeFreePostage) {
            $storePostage = 0;
        }else{
            foreach ($cartInfo as $cart){
                if(!$cart['productInfo']['is_postage']){$storePostage = bcadd($storePostage,$cart['productInfo']['postage'],2);}

            }
            if($storeFreePostage <= $totalPrice) $storePostage = 0;
        }
        return compact('storePostage','storeFreePostage','totalPrice','costPrice');
    }

    public static function getOrderTotalPrice($cartInfo)
    {
        $totalPrice = 0;
        foreach ($cartInfo as $cart){
            $totalPrice = bcadd($totalPrice,bcmul($cart['cart_num'],$cart['truePrice'],2),2);
        }
        return $totalPrice;
    }
    public static function getOrderCostPrice($cartInfo)
    {
        $costPrice=0;
        foreach ($cartInfo as $cart){
            $costPrice = bcadd($costPrice,bcmul($cart['cart_num'],$cart['costPrice'],2),2);
        }
        return $costPrice;
    }



    /**
     * 拼团
     * @param $cartInfo
     * @return array
     */
    public static function getCombinationOrderPriceGroup($cartInfo)
    {
        $storePostage = floatval(SystemConfigService::get('store_postage'))?:0;
        $storeFreePostage =  floatval(SystemConfigService::get('store_free_postage'))?:0;
        $totalPrice = self::getCombinationOrderTotalPrice($cartInfo);
        $costPrice = self::getCombinationOrderCostPrice($cartInfo);
        if(!$storeFreePostage) {
            $storePostage = 0;
        }else{
            foreach ($cartInfo as $cart){
                if(!StoreCombination::where('id',$cart['combination_id'])->value('is_postage'))
                    $storePostage = bcadd($storePostage,StoreCombination::where('id',$cart['combination_id'])->value('postage'),2);
            }
            if($storeFreePostage <= $totalPrice) $storePostage = 0;
        }
        return compact('storePostage','storeFreePostage','totalPrice','costPrice');
    }


    /**
     * 拼团价格
     * @param $cartInfo
     * @return float
     */
    public static function getCombinationOrderTotalPrice($cartInfo)
    {
        $totalPrice = 0;
        foreach ($cartInfo as $cart){
            if($cart['combination_id']){
                 $totalPrice = bcadd($totalPrice,bcmul($cart['cart_num'],StoreCombination::where('id',$cart['combination_id'])->value('price'),2),2);
            }
        }
        return (float)$totalPrice;
    }
    public static function getCombinationOrderCostPrice($cartInfo)
    {
        $costPrice = 0;
        foreach ($cartInfo as $cart){
            if($cart['combination_id']){
                $totalPrice = bcadd($costPrice,bcmul($cart['cart_num'],StoreCombination::where('id',$cart['combination_id'])->value('price'),2),2);
            }
        }
        return (float)$costPrice;
    }


    public static function cacheOrderInfo($uid,$cartInfo,$priceGroup,$other = [],$cacheTime = 600)
    {
        $key = md5(time());
        Cache::set('user_order_'.$uid.$key,compact('cartInfo','priceGroup','other'),$cacheTime);
        return $key;
    }

    public static function getCacheOrderInfo($uid,$key)
    {
        $cacheName = 'user_order_'.$uid.$key;
        if(!Cache::has($cacheName)) return null;
        return Cache::get($cacheName);
    }

    public static function clearCacheOrderInfo($uid,$key)
    {
        Cache::clear('user_order_'.$uid.$key);
    }

    public static function cacheKeyCreateOrder($uid,$key,$addressId,$payType,$useIntegral = false,$couponId = 0,$mark = '',$combinationId = 0,$pinkId = 0,$seckill_id=0,$bargainId = 0,$pin_type=0)
    {   

        if(!array_key_exists($payType,self::$payType)) return self::setErrorInfo('选择支付方式有误!');
        if(self::be(['unique'=>$key,'uid'=>$uid])) return self::setErrorInfo('请勿重复提交订单');
        $userInfo = User::getUserInfo($uid);
        if(!$userInfo) return  self::setErrorInfo('用户不存在!');
        $cartGroup = self::getCacheOrderInfo($uid,$key);
        if(!$cartGroup) return self::setErrorInfo('订单已过期,请刷新当前页面!');
        $cartInfo = $cartGroup['cartInfo'];
        $priceGroup = $cartGroup['priceGroup'];
        $other = $cartGroup['other'];
        $payPrice = $priceGroup['totalPrice'];
        $payPostage = $priceGroup['storePostage'];
        if(!$addressId) return self::setErrorInfo('请选择收货地址!');
        if(!UserAddress::be(['uid'=>$uid,'id'=>$addressId,'is_del'=>0]) || !($addressInfo = UserAddress::find($addressId)))
            return self::setErrorInfo('地址选择有误!');

        //使用优惠劵
        $res1 = true;
        if($couponId){
            $couponInfo = StoreCouponUser::validAddressWhere()->where('id',$couponId)->where('uid',$uid)->find();
            if(!$couponInfo) return self::setErrorInfo('选择的优惠劵无效!');
            if($couponInfo['use_min_price'] > $payPrice)
                return self::setErrorInfo('不满足优惠劵的使用条件!');
            $payPrice = bcsub($payPrice,$couponInfo['coupon_price'],2);
            $res1 = StoreCouponUser::useCoupon($couponId);
            $couponPrice = $couponInfo['coupon_price'];
        }else{
            $couponId = 0;
            $couponPrice = 0;
        }
        if(!$res1) return self::setErrorInfo('使用优惠劵失败!');
        //是否包邮
        if((isset($other['offlinePostage'])  && $other['offlinePostage'] && $payType == 'offline')) $payPostage = 0;
        $payPrice = bcadd($payPrice,$payPostage,2);
        //积分抵扣
        $res2 = true;
        if($useIntegral && $userInfo['integral'] > 0){
            $deductionPrice = bcmul($userInfo['integral'],$other['integralRatio'],2);
            if($deductionPrice < $payPrice){
                $payPrice = bcsub($payPrice,$deductionPrice,2);
                $usedIntegral = $userInfo['integral'];
                $res2 = false !== User::edit(['integral'=>0],$userInfo['uid'],'uid');
            }else{
                $deductionPrice = $payPrice;
                $usedIntegral = bcdiv($payPrice,$other['integralRatio'],2);
                $res2 = false !== User::bcDec($userInfo['uid'],'integral',$usedIntegral,'uid');
                $payPrice = 0;
            }
            $res2 = $res2 && false != UserBill::expend('积分抵扣',$uid,'integral','deduction',$usedIntegral,$key,bcsub($userInfo['integral'],$usedIntegral,2),'购买商品使用'.floatval($usedIntegral).'积分抵扣'.floatval($deductionPrice).'元');
        }else{
            $deductionPrice = 0;
            $usedIntegral = 0;
        }
        if(!$res2) return self::setErrorInfo('使用积分抵扣失败!');

        $cartIds = [];
        $totalNum = 0;
        $gainIntegral = 0;
        foreach ($cartInfo as $cart){
                $cartIds[] = $cart['id'];
                $totalNum += $cart['cart_num'];
                $gainIntegral = bcadd($gainIntegral,$cart['productInfo']['give_integral'],2);
        }
        $orderInfo = [
            'uid'=>$uid,
            'order_id'=>self::getNewOrderId(),
            'real_name'=>$addressInfo['real_name'],
            'user_phone'=>$addressInfo['phone'],
            'user_address'=>$addressInfo['province'].' '.$addressInfo['city'].' '.$addressInfo['district'].' '.$addressInfo['detail'],
            'cart_id'=>$cartIds,
            'total_num'=>$totalNum,
            'total_price'=>$priceGroup['totalPrice'],
            'total_postage'=>$priceGroup['storePostage'],
            'coupon_id'=>$couponId,
            'coupon_price'=>$couponPrice,
            'pay_price'=>$payPrice,
            'pay_postage'=>$payPostage,
            'deduction_price'=>$deductionPrice,
            'paid'=>0,
            'pay_type'=>$payType,
            'use_integral'=>$usedIntegral,
            'gain_integral'=>$gainIntegral,
            'mark'=>htmlspecialchars($mark),
            'combination_id'=>$combinationId,
            'pink_id'=>$pinkId,
            'seckill_id'=>$seckill_id,
            'bargain_id'=>$bargainId,
            'pin_type'=>$pin_type*1,
            'cost'=>$priceGroup['costPrice'],
            'unique'=>$key
        ];
        // dump($orderInfo);die;
        $order = self::set($orderInfo);
        if(!$order)return self::setErrorInfo('订单生成失败!');
        $res5 = true;
        foreach ($cartInfo as $cart){
            //减库存加销量
            if($combinationId) $res5 = $res5 && StoreCombination::decCombinationStock($cart['cart_num'],$combinationId);
            else if($seckill_id) $res5 = $res5 && StoreSeckill::decSeckillStock($cart['cart_num'],$seckill_id);
            else if($bargainId) $res5 = $res5 && StoreBargain::decBargainStock($cart['cart_num'],$bargainId);
            else $res5 = $res5 && StoreProduct::decProductStock($cart['cart_num'],$cart['productInfo']['id'],isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique']:'');

         }
        //保存购物车商品信息
        $res4 = false !== StoreOrderCartInfo::setCartInfo($order['id'],$cartInfo);
        //购物车状态修改
        $res6 = false !== StoreCart::where('id','IN',$cartIds)->update(['is_pay'=>1]);
        if(!$res4 || !$res5 || !$res6) return self::setErrorInfo('订单生成失败!');
        try{
            HookService::listen('store_product_order_create',$order,compact('cartInfo','addressId'),false,StoreProductBehavior::class);
        }catch (\Exception $e){
            return self::setErrorInfo($e->getMessage());
        }
        self::clearCacheOrderInfo($uid,$key);
        self::commitTrans();
        StoreOrderStatus::status($order['id'],'cache_key_create_order','订单生成');
        return $order;
    }

    public static function getNewOrderId()
    {
        $count = (int) self::where('add_time',['>=',strtotime(date("Y-m-d"))],['<',strtotime(date("Y-m-d",strtotime('+1 day')))])->count();
        return 'wx'.date('YmdHis',time()).(10000+$count+1);
    }

    public static function changeOrderId($orderId)
    {
        $ymd = substr($orderId,2,8);
        $key = substr($orderId,16);
        return 'wx'.$ymd.date('His').$key;
    }

    public static function jsPay($orderId,$field = 'order_id')
    {
        if(is_string($orderId))
            $orderInfo = self::where($field,$orderId)->find();
        else
            $orderInfo = $orderId;
        if(!$orderInfo || !isset($orderInfo['paid'])) exception('支付订单不存在!');
        if($orderInfo['paid']) exception('支付已支付!');
        if($orderInfo['pay_price'] <= 0) exception('该支付无需支付!');
        // $openid = WechatUser::uidToOpenid($orderInfo['uid']);
        // H5支付
        $params = [
            'body' => SystemConfigService::get('site_name'),
            'out_trade_no' => $orderInfo['order_id'],
            'total_fee' => $orderInfo['pay_price']*100,
        ];

        $result = \wxpay\WapPay::getPayUrl($params);
        // halt($result);
        $url=urlencode("http://h5app.xinyuad.net/wap/my/query/uni/".$orderInfo['order_id']);
        return $result.'&redirect_url='.$url;
        // return WechatService::jsPay($openid,$orderInfo['order_id'],$orderInfo['pay_price'],'product',SystemConfigService::get('site_name'));
    }


    
    public static function yuePay($order_id,$uid)
    {

        $orderInfo = self::where('uid',$uid)->where('order_id',$order_id)->where('is_del',0)->find();
        if(!$orderInfo) return self::setErrorInfo('订单不存在!');
        if($orderInfo['paid']) return self::setErrorInfo('该订单已支付!');
        if($orderInfo['pay_type'] != 'yue') return self::setErrorInfo('该订单不能使用余额支付!');
        $userInfo = User::getUserInfo($uid);
        if($userInfo['now_money'] < $orderInfo['pay_price'])
            return self::setErrorInfo('余额不足'.floatval($orderInfo['pay_price']));
        self::beginTrans();
        $res1 = false !== User::bcDec($uid,'now_money',$orderInfo['pay_price'],'uid');

        $res2 = UserBill::expend('購買商品',$uid,'now_money','pay_product',$orderInfo['pay_price'],$orderInfo['id'],bcsub($userInfo['now_money'],$orderInfo['pay_price'],2),'餘額支付 $'.floatval($orderInfo['pay_price']).'購買商品','Purchase goods','Balance payment $'.floatval($orderInfo['pay_price']).'buy goods',1);
        $res3 = self::paySuccess($order_id);
        try{
            HookService::listen('yue_pay_product',$userInfo,$orderInfo,false,PaymentBehavior::class);
        }catch (\Exception $e){
            self::rollbackTrans();
            return self::setErrorInfo($e->getMessage());
        }
        $res = $res1 && $res2 && $res3;
        self::checkTrans($res);

        return $res;
    }

    /**
     * 微信支付 为 0元时
     * @param $order_id
     * @param $uid
     * @return bool
     */
    public static function jsPayPrice($order_id,$uid){
        $orderInfo = self::where('uid',$uid)->where('order_id',$order_id)->where('is_del',0)->find();
        if(!$orderInfo) return self::setErrorInfo('订单不存在!');
        if($orderInfo['paid']) return self::setErrorInfo('该订单已支付!');
        $userInfo = User::getUserInfo($uid);
        self::beginTrans();
        $res1 = UserBill::expend('购买商品',$uid,'now_money','pay_product',$orderInfo['pay_price'],$orderInfo['id'],$userInfo['now_money'],'微信支付'.floatval($orderInfo['pay_price']).'元购买商品');
        $res2 = self::paySuccess($order_id);
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
    }

    public static function yueRefundAfter($order)
    {

    }

    /**
     * 用户申请退款
     * @param $uni
     * @param $uid
     * @param string $refundReasonWap
     * @return bool
     */
    public static function orderApplyRefund($uni, $uid,$refundReasonWap = '')
    {
        $order = self::getUserOrderDetail($uid,$uni);
        if(!$order) return self::setErrorInfo('支付订单不存在!');
        if($order['refund_status'] == 2) return self::setErrorInfo('订单已退款!');
        if($order['refund_status'] == 1) return self::setErrorInfo('正在申请退款中!');
        if($order['status'] == 1) return self::setErrorInfo('订单当前无法退款!');
        self::beginTrans();
        $res1 = false !== StoreOrderStatus::status($order['id'],'apply_refund','用户申请退款，原因：'.$refundReasonWap);
        $res2 = false !== self::edit(['refund_status'=>1,'refund_reason_wap'=>$refundReasonWap],$order['id'],'id');
        $res = $res1 && $res2;
        self::checkTrans($res);
        if(!$res)
            return self::setErrorInfo('申请退款失败!');
        else{
            try{
                HookService::afterListen('store_product_order_apply_refund',$order['id'],$uid,false,StoreProductBehavior::class);
            }catch (\Exception $e){}
            return true;
        }
    }

    /**
     * //TODO 支付成功后
     * @param $orderId
     * @param $notify
     * @return bool
     */
    public static function paySuccess($orderId)
    {   
        $order = self::where('order_id',$orderId)->find();
        $resPink = true;
        User::bcInc($order['uid'],'pay_count',1,'uid');
        if($order['pin_type']==1){
            $res1 = self::where('order_id',$orderId)->update(['paid'=>1,'pay_time'=>time(),'pin_status'=>1]);
        }else{
            $res1 = self::where('order_id',$orderId)->update(['paid'=>1,'pay_time'=>time(),'pin_status'=>0]);
        }
        
        if($order->combination_id && $res1 && !$order->refund_status) $resPink = StorePink::createPink($order);//创建拼团
        $oid = self::where('order_id',$orderId)->value('id');
        StoreOrderStatus::status($oid,'pay_success','用户付款成功');
        // WechatTemplateService::sendTemplate(WechatUser::uidToOpenid($order['uid']),WechatTemplateService::ORDER_PAY_SUCCESS, [
        //     'first'=>'亲，您购买的商品已支付成功',
        //     'keyword1'=>$orderId,
        //     'keyword2'=>$order['pay_price'],
        //     'remark'=>'点击查看订单详情'
        // ],Url::build('wap/My/order',['uni'=>$orderId],true,true));

        // WechatTemplateService::sendAdminNoticeTemplate([
        //     'first'=>"亲,您有一个新订单 \n订单号:{$order['order_id']}",
        //     'keyword1'=>'新订单',
        //     'keyword2'=>'已支付',
        //     'keyword3'=>date('Y/m/d H:i',time()),
        //     'remark'=>'请及时处理'
        // ]);
        
        //用户次数处理
        if($order['pin_type']==1){
            $user_num=UserNum::where('user_id',$order['uid'])->whereTime('add_time', 'today')->find();
        if($user_num){
            UserNum::where('user_id',$order['uid'])->whereTime('add_time', 'today')->update(['user_num' => $user_num['user_num']-1]);
        }else{
            $spread_uid=User::where('spread_uid',$order['uid'])->count();
            $data=[
                'user_id'=>$order['uid'],
                'user_num'=>19+$spread_uid*2,
                'add_time'=>time()
            ];
            UserNum::insert($data);
            UserNum::income('初始化',$order['uid'],20+$spread_uid*2,20+$spread_uid*2,'用户初始化次数');
        }
         UserNum::expend('拼团购物',$order['uid'],1,$user_num['user_num']-1,'用户拼团购物');
        }
        
        $res = $res1 && $resPink;
        return false !== $res;
    }

    public static function createOrderTemplate($order)
    {
        $goodsName = StoreOrderCartInfo::getProductNameList($order['id']);
        WechatTemplateService::sendTemplate(WechatUser::uidToOpenid($order['uid']),WechatTemplateService::ORDER_CREATE, [
            'first'=>'亲，您购买的商品已支付成功',
            'keyword1'=>date('Y/m/d H:i',$order['add_time']),
            'keyword2'=>implode(',',$goodsName),
            'keyword3'=>$order['order_id'],
            'remark'=>'点击查看订单详情'
        ],Url::build('/wap/My/order',['uni'=>$order['order_id']],true,true));
        WechatTemplateService::sendAdminNoticeTemplate([
            'first'=>"亲,您有一个新订单 \n订单号:{$order['order_id']}",
            'keyword1'=>'新订单',
            'keyword2'=>'线下支付',
            'keyword3'=>date('Y/m/d H:i',time()),
            'remark'=>'请及时处理'
        ]);
    }

    public static function getUserOrderDetail($uid,$key)
    {
        return self::where('order_id|unique',$key)->where('uid',$uid)->where('is_del',0)->find();
    }


    /**
     * TODO 订单发货
     * @param array $postageData 发货信息
     * @param string $oid orderID
     */
    public static function orderPostageAfter($postageData, $oid)
    {
        $order = self::where('id',$oid)->find();
        $openid = WechatUser::uidToOpenid($order['uid']);
        $url = Url::build('wap/My/order',['uni'=>$order['order_id']],true,true);
        $group = [
            'first'=>'亲,您的订单已发货,请注意查收',
            'remark'=>'点击查看订单详情'
        ];
        if($postageData['delivery_type'] == 'send'){//送货
            $goodsName = StoreOrderCartInfo::getProductNameList($order['id']);
            $group = array_merge($group,[
                'keyword1'=>$goodsName,
                'keyword2'=>$order['pay_type'] == 'offline' ? '线下支付' : date('Y/m/d H:i',$order['pay_time']),
                'keyword3'=>$order['user_address'],
                'keyword4'=>$postageData['delivery_name'],
                'keyword5'=>$postageData['delivery_id']
            ]);
            WechatTemplateService::sendTemplate($openid,WechatTemplateService::ORDER_DELIVER_SUCCESS,$group,$url);

        }else if($postageData['delivery_type'] == 'express'){//发货
            $group = array_merge($group,[
                'keyword1'=>$order['order_id'],
                'keyword2'=>$postageData['delivery_name'],
                'keyword3'=>$postageData['delivery_id']
            ]);
            WechatTemplateService::sendTemplate($openid,WechatTemplateService::ORDER_POSTAGE_SUCCESS,$group,$url);
        }
    }

    public static function orderTakeAfter($order)
    {
        $openid = WechatUser::uidToOpenid($order['uid']);
        WechatTemplateService::sendTemplate($openid,WechatTemplateService::ORDER_TAKE_SUCCESS,[
            'first'=>'亲，您的订单已成功签收，快去评价一下吧',
            'keyword1'=>$order['order_id'],
            'keyword2'=>'已收货',
            'keyword3'=>date('Y/m/d H:i',time()),
            'keyword4'=>implode(',',StoreOrderCartInfo::getProductNameList($order['id'])),
            'remark'=>'点击查看订单详情'
        ],Url::build('My/order',['uni'=>$order['order_id']],true,true));
    }

    /**
     * 删除订单
     * @param $uni
     * @param $uid
     * @return bool
     */
    public static function removeOrder($uni, $uid)
    {
        $order = self::getUserOrderDetail($uid,$uni);
        if(!$order) return self::setErrorInfo('订单不存在!');
        $order = self::tidyOrder($order);
        if($order['_status']['_type'] != 0 && $order['_status']['_type']!= -2 && $order['_status']['_type'] != 4)
            return self::setErrorInfo('该订单无法删除!');
        if(false !== self::edit(['is_del'=>1],$order['id'],'id') &&
            false !==StoreOrderStatus::status($order['id'],'remove_order','删除订单'))
            return true;
        else
            return self::setErrorInfo('订单删除失败!');
    }


    /**
     * //TODO 用户确认收货
     * @param $uni
     * @param $uid
     */
    public static function takeOrder($uni, $uid)
    {
        $order = self::getUserOrderDetail($uid,$uni);
        if(!$order) return self::setErrorInfo('订单不存在!');
        $order = self::tidyOrder($order);
        if($order['_status']['_type'] != 2)  return self::setErrorInfo('订单状态错误!');
        self::beginTrans();
        if(false !== self::edit(['status'=>2],$order['id'],'id') &&
            false !== StoreOrderStatus::status($order['id'],'user_take_delivery','用户已收货')){
            try{
                HookService::listen('store_product_order_user_take_delivery',$order,$uid,false,StoreProductBehavior::class);
            }catch (\Exception $e){
                return self::setErrorInfo($e->getMessage());
            }
            self::commitTrans();

            return true;
        }else{
            self::rollbackTrans();
            return false;
        }
    }

    public static function tidyOrder($order,$detail = false)
    {
        if($detail == true && isset($order['id'])){
            $cartInfo = self::getDb('StoreOrderCartInfo')->where('oid',$order['id'])->column('cart_info','unique')?:[];
            foreach ($cartInfo as $k=>$cart){
                $cartInfo[$k] = json_decode($cart, true);
                $cartInfo[$k]['unique'] = $k;
            }
            // dump($cartInfo);die;
            $order['cartInfo'] = $cartInfo;
        }
        $order_cancel_time=SystemConfig::getValue('order_cancel_time')*60*60;

        $status = [];
        if(!$order['paid'] && $order['pay_type'] == 'offline' && !$order['status'] >= 2){
            $status['_type'] = 9;
            $status['_title'] = L('線下付款','Offline payment');
            $status['_msg'] = L('等待商家處理，請耐心等待','Please wait patiently while waiting for the merchant to handle');
            $status['_class'] = 'nobuy';
        }else if(!$order['paid'] && $order['add_time']+$order_cancel_time<time()){
            $status['_type'] = -2;
            $status['_title'] = L('未支付(已失效)','Unpaid (expired)');
            $status['_msg'] = L('該訂單已失效','The order has expired');
            $status['_class'] = 'nobuy';
        }else if(!$order['paid']){
            $status['_type'] = 0;
            $status['_title'] = L('未支付','Unpaid');
            $status['_msg'] = L('立即支付訂單吧','Pay the order now');
            $status['_class'] = 'nobuy';
        }else if($order['refund_status'] == 1){
            $status['_type'] = -1;
            $status['_title'] = L('申請退款中','Applying for refund');
            $status['_msg'] = L('商家稽核中，請耐心等待','Please wait patiently while the merchant reviews');
            $status['_class'] = 'state-sqtk';
        }else if(!$order['status']){
            if(isset($order['pink_id'])){
                if(StorePink::where('id',$order['pink_id'])->where('status',1)->count()){
                    $status['_type'] = 1;
                    $status['_title'] = L('拼團中','In the puzzle');
                    $status['_msg'] = L('等待其他人參加拼團','Waiting for others to join the group');
                    $status['_class'] = 'state-nfh';
                }else{
                    $status['_type'] = 1;
                    $status['_title'] = L('未發貨','Not shipped');
                $status['_msg'] = L('商家未發貨，請耐心等待','The merchant has not delivered the goods, please wait patiently');
                    $status['_class'] = 'state-nfh';
                }
            }else{
                $status['_type'] = 1;
                $status['_title'] = L('未發貨','Not shipped');
                $status['_msg'] = L('商家未發貨，請耐心等待','The merchant has not delivered the goods, please wait patiently');
                $status['_class'] = 'state-nfh';
            }
        }else if($order['status'] == 1){
            if($order['delivery_type'] == 'send'){//TODO 送货
                $status['_type'] = 2;
                $status['_title'] = L('待收貨','Goods to be received');
                $status['_msg'] = date('m-d H:i',StoreOrderStatus::getTime($order['id'],'delivery')).L('服務商已發貨','Service provider shipped');
                $status['_class'] = 'state-ysh';
            }else{//TODO  发货
                $status['_type'] = 2;
                $status['_title'] = L('待收貨','Goods to be received');
                $status['_msg'] = date('m-d H:i',StoreOrderStatus::getTime($order['id'],'delivery_goods')).L('服務商已發貨','Service provider shipped');
                $status['_class'] = 'state-ysh';
            }
        }else if($order['status'] == 2){
            $status['_type'] = 3;
            // $status['_title'] = L('待評估','To be evaluated');
            // $status['_msg'] = L('已收貨，快去評估一下吧','The goods have been received. Go and evaluate it quickly');
              $status['_title'] = L('交易完成','Transaction completion');
            $status['_msg'] = L('交易完成，感謝您的支持','The transaction is completed. Thank you for your support');
            $status['_class'] = 'state-ypj';
        }else if($order['status'] == 3){
            $status['_type'] = 4;
            $status['_title'] = L('交易完成','Transaction completion');
            $status['_msg'] = L('交易完成，感謝您的支持','The transaction is completed. Thank you for your support');
            $status['_class'] = 'state-ytk';
        }else if($order['refund_status'] == 2){
            $status['_type'] = -2;
            $status['_title'] = L('已退款','Refunded');
            $status['_msg'] = L('已為您退款，感謝您的支持','You have been refunded. Thank you for your support');
            $status['_class'] = 'state-sqtk';
        }
        if($order['pin_type']==1){

            if($order['status']==0 && $order['paid']==1 && $order['pin_status']==1 && $order['refund_status'] == 0){
                    $status['_type'] = 10;
                    $status['_title'] = L('拼團中','In the puzzle');
                    $status['_msg'] = L('等待其他人參加拼團','Waiting for others to join the group');
                    $status['_class'] = 'state-nfh';
            }else if($order['status']==0 && $order['paid']==1 && $order['pin_status']==2 && $order['refund_status'] == 2){
                    $status['_type'] = 4;
                    $status['_title'] = L('拼團成功','Group success');
                    $status['_msg'] = L('拼團成功，本金已退，等待發貨','The group has been successfully put together. The principal has been refunded and waiting for delivery');
                    $status['_class'] = 'state-ytk';
            }else if($order['status']=='-1' && $order['paid']==1 && $order['pin_status']==3 && $order['refund_status'] == 2){
                    $status['_type'] = 11;
                    $status['_title'] = L('拼團失敗','Collage failure');
                    $status['_msg'] = L('拼團失敗，本金已退','The group has failed. The principal has been refunded');
                    $status['_class'] = 'state-ysh';
            }
        }
        if(isset($order['pay_type']))
            $status['_payType'] = isset(self::$payType[$order['pay_type']]) ? L(self::$payType[$order['pay_type']],self::$payTypeen[$order['pay_type']]) : L('其他方式','Other ways');
        if(isset($order['delivery_type']))
            $status['_deliveryType'] = isset(self::$deliveryType[$order['delivery_type']]) ? self::$deliveryType[$order['delivery_type']] : L('其他方式','Other ways');
        $order['_status'] = $status;
        return $order;
    }

    public static function statusByWhere($status,$uid,$model = null)
    {
        $orderId = StorePink::where('uid',User::getActiveUid())->where('status',1)->column('order_id','id');//获取正在拼团的订单编号
        if($model == null) $model = new self;
        if('' === $status)
            return $model;
        else if($status == 0)
            return $model->where('paid',0)->where('status',0)->where('refund_status',0);
        else if($status == 1){
                   //待发货
                   
                        return $model->where(function ($query) {
                                $query->where('paid',1)->where('status',0)->where('pin_status !=1');
                            })->where(function ($query) {
                                $query->where('refund_status=0 or pin_status=2');
                            });
                    // return $model->where('paid',1)->where('order_id','NOT IN',implode(',',$orderId))->where('status',0)->where('refund_status',0);
                    }
        else if($status == 2){
            return $model->where(function ($query) {
                                $query->where('paid',1)->where('status',1)->where('pin_status !=1');
                            })->where(function ($query) {
                                $query->where('refund_status=0 or pin_status=2');
                            });
            // return $model->where('paid',1)->where('status',1)->where('refund_status',0);
        }
        else if($status == 3){
            return $model->where(function ($query) {
                                $query->where('paid',1)->where('status',2)->where('pin_status !=1');
                            })->where(function ($query) {
                                $query->where('refund_status=0 or pin_status=2');
                            });
            // return $model->where('paid',1)->where('status',2)->where('refund_status',0);
        }
        else if($status == 4){
            return $model->where(function ($query) {
                                $query->where('paid',1)->where('status',3)->where('pin_status !=1');
                            })->where(function ($query) {
                                $query->where('refund_status=0 or pin_status=2');
                            });
            // return $model->where('paid',1)->where('status',3)->where('refund_status',0);
        }
        else if($status == -1)
            return $model->where('paid',1)->where('refund_status',1);
        else if($status == -2)
            return $model->where('paid',1)->where('refund_status',2);
        else if($status == 11){
            return $model->where('order_id','IN',implode(',',$orderId));
        }
        else
            return $model;
    }

    public static function statusByWherePin($status,$model = null)
    {
        $orderId = StorePink::where('uid',User::getActiveUid())->where('status',1)->column('order_id','id');//获取正在拼团的订单编号
        if($model == null) $model = new self;
        if('' === $status)
            return $model;
        else if($status == 0)
            return $model->where('paid',1)->where('status',0)->where('refund_status',0)->where('pin_status',1);
        else if($status == 1)
            return $model->where('paid',1)->where('order_id','NOT IN',implode(',',$orderId))->where('status',0)->where('refund_status',0);
        else if($status == 2)//拼团成功
            return $model->where('paid',1)->where('status',0)->where('refund_status',2)->where('pin_status',2);
        else if($status == 3)//拼团失败
            return $model->where('paid',1)->where('status','-1')->where('refund_status',2)->where('pin_status',3);
        else if($status == 4)
            return $model->where('paid',1)->where('status',3)->where('refund_status',0);
        else if($status == -1)
            return $model->where('paid',1)->where('refund_status',1);
        else if($status == -2)
            return $model->where('paid',1)->where('refund_status',2);
        else if($status == 11){
            return $model->where('order_id','IN',implode(',',$orderId));
        }
        else
            return $model;
    }

    public static function getUserOrderList($uid,$status = '',$first = 0,$limit = 8,$pin_type)
    {       
        if($pin_type==1){
            $statuswhere=self::statusByWherePin($status);
        }else{
           $statuswhere=self::statusByWhere($status,$uid);
        }
        $list = $statuswhere->where('is_del',0)->where('uid',$uid)
            ->field('combination_id,id,order_id,pay_price,total_num,total_price,pay_postage,total_postage,paid,status,refund_status,pay_type,coupon_price,deduction_price,pink_id,delivery_type,pin_type,pin_status,add_time')
            ->order('add_time DESC')->limit($first,$limit)->select()->toArray();

        foreach ($list as $k=>$order){
            $list[$k] = self::tidyOrder($order,true);
        }
        return $list;
    }

    public static function searchUserOrder($uid,$order_id,$pin_type)
    {
        $order = self::where('uid',$uid)->where('order_id',$order_id)->where('pin_type',$pin_type)->where('is_del',0)->field('combination_id,id,order_id,pay_price,total_num,total_price,pay_postage,total_postage,paid,status,refund_status,add_time,pay_type,coupon_price,deduction_price,delivery_type,pin_type,pin_status')
            ->order('add_time DESC')->find();
        if(!$order)
            return false;
        else
            return self::tidyOrder($order->toArray(),true);

    }

    public static function orderOver($oid)
    {
        $res = self::edit(['status'=>'3'],$oid,'id');
        if(!$res) exception('评价后置操作失败!');
        StoreOrderStatus::status($oid,'check_order_over','用户评价');
    }

    public static function checkOrderOver($oid)
    {
        $uniqueList = StoreOrderCartInfo::where('oid',$oid)->column('unique');
        if(StoreProductReply::where('unique','IN',$uniqueList)->where('oid',$oid)->count() == count($uniqueList)){
            HookService::listen('store_product_order_over',$oid,null,false,StoreProductBehavior::class);
            self::orderOver($oid);
        }
    }


    public static function getOrderStatusNum($uid)
    {
        $noBuy = self::where('uid',$uid)->where('paid',0)->where('is_del',0)->where('pay_type','<>','offline')->where('refund_status',0)->count();
        $noPostageNoPink = self::where('o.uid',$uid)->alias('o')->where('o.paid',1)->where('o.pink_id',0)->where('o.is_del',0)->where('o.status',0)->where('o.pay_type','<>','offline')->where('o.refund_status',0)->where('o.pin_status !=1')->count();
        $noPostageYesPink = self::where('o.uid',$uid)->alias('o')->join('StorePink p','o.pink_id = p.id')->where('p.status',2)->where('o.paid',1)->where('o.is_del',0)->where('o.status',0)->where('o.refund_status',0)->where('o.pay_type','<>','offline')->count();

        $noPostage = bcadd($noPostageNoPink,$noPostageYesPink,0);
        //拼团成功
        $success =StoreOrder::where(['uid'=>$uid])->where('paid',1)->where('pin_type',1)->where('status',0)->where('refund_status',2)->where('pin_status',2)->count();
        $noPostage = bcadd($noPostage,$success,0);
        $noTake = self::where('uid',$uid)->where('paid',1)->where('is_del',0)->where('status',1)->where('pay_type','<>','offline')->where('refund_status',0)->count();
        $noTake_pin=StoreOrder::where(['uid'=>$uid])->where('paid',1)->where('pin_type',1)->where('status',1)->where('refund_status',2)->where('pin_status',2)->count();
         $noTake = bcadd($noTake,$noTake_pin,0);
        $noReply = self::where('uid',$uid)->where('paid',1)->where('is_del',0)->where('status',2)->where('refund_status',0)->count();
        $noReply_pin=StoreOrder::where(['uid'=>$uid])->where('paid',1)->where('pin_type',1)->where('status',2)->where('refund_status',2)->where('pin_status',2)->count();
         $noReply = bcadd($noReply,$noReply_pin,0);
        $noPink = self::where('o.uid',$uid)->alias('o')->join('StorePink p','o.pink_id = p.id')->where('p.status',1)->where('o.paid',1)->where('o.is_del',0)->where('o.status',0)->where('o.pay_type','<>','offline')->where('o.refund_status',0)->count();
        return compact('noBuy','noPostage','noTake','noReply','noPink');
    }

    public static function gainUserIntegral($order)
    {
        if($order['gain_integral'] > 0){
            $userInfo = User::getUserInfo($order['uid']);
            ModelBasic::beginTrans();
            $res1 = false != User::where('uid',$userInfo['uid'])->update(['integral'=>bcadd($userInfo['integral'],$order['gain_integral'],2)]);
            $res2 = false != UserBill::income('购买商品赠送积分',$order['uid'],'integral','gain',$order['gain_integral'],$order['id'],bcadd($userInfo['integral'],$order['gain_integral'],2),'购买商品赠送'.floatval($order['gain_integral']).'积分');
            $res = $res1 && $res2;
            ModelBasic::checkTrans($res);
            return $res;
        }
        return true;
    }

    /**
     * 获取当前订单中有没有拼团存在
     * @param $pid
     * @return int|string
     */
    public static function getIsOrderPink($pid){
        $uid = User::getActiveUid();
        return self::where('uid',$uid)->where('pink_id',$pid)->where('refund_status',0)->where('is_del',0)->count();
    }

    /**
     * 获取order_id
     * @param $pid
     * @return mixed
     */
    public static function getStoreIdPink($pid){
        $uid = User::getActiveUid();
        return self::where('uid',$uid)->where('pink_id',$pid)->where('is_del',0)->value('order_id');
    }

    /**
     * 删除当前用户拼团未支付的订单
     */
    public static function delCombination(){
        self::where('combination','GT',0)->where('paid',0)->where('uid',User::getActiveUid())->delete();
    }
}