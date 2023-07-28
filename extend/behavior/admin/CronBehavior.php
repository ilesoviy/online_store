<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/18
 */

namespace behavior\admin;

use app\admin\model\user\User;
use app\admin\model\user\UserAddress;
use app\admin\model\user\UserBill;
use app\admin\model\wechat\WechatUser;
use basic\ModelBasic;
use app\admin\model\order\StoreOrder;
use app\core\util\WechatTemplateService;
use service\UtilService as Utils;

class CronBehavior
{



    /**
     * 取消订单
     */
    public static function cancle()
    {
        Utils::log('自动取消订单任务开始时间：' . date('Y-m-d H:i:s') . PHP_EOL);
//        $setting = getSetting('order_cancel_time');
//        $order   = model('common/Order');
//        $order->autoCancel($setting);
        Utils::log('自动取消订单任务结束时间：' . date('Y-m-d H:i:s') . PHP_EOL);
    }

    /**
     * 订单自动完成
     */
    public static function complete()
    {
        Utils::log('自动完成订单任务开始时间：'.date('Y-m-d H:i:s').PHP_EOL);
//        $setting = getSetting('order_complete_time');
//        model('common/Order')->autoComplete($setting);
        Utils::log('自动完成订单任务结束时间：'.date('Y-m-d H:i:s').PHP_EOL);
    }

    /**
     * 自动评价订单
     */
    public static function evaluate()
    {
        Utils::log('自动评价订单任务开始时间：'.date('Y-m-d H:i:s').PHP_EOL);

//        $setting = getSetting('order_autoEval_time');
//        model('common/Order')->autoEvaluate($setting);
        Utils::log('自动评价订单任务结束时间：'.date('Y-m-d H:i:s').PHP_EOL);
    }

    /**
     * 订单自动签收
     */
    public static function sign()
    {
        Utils::log('自动签收订单任务开始时间：'.date('Y-m-d H:i:s').PHP_EOL);
//        $setting = getSetting('order_autoSign_time');
//        model('common/Order')->autoSign($setting);

        Utils::log('自动签收订单任务结束时间：'.date('Y-m-d H:i:s').PHP_EOL);
    }

    /**
     * 催付款
     */
    public  static function remind(){
        Utils::log('催付款开始时间：'.date('Y-m-d H:i:s').PHP_EOL);
//        $setting = getSetting('order_cancel_time');
//        $order = new \app\common\model\Order();
//        $order->remind_order_pay($setting);
        Utils::log('催付款任务结束时间：'.date('Y-m-d H:i:s').PHP_EOL);
    }


    /**
     * 修改状态为  已退款
     * @param $data
     *  $data array type 1 直接退款  2 退款后返回原状态  refund_price  退款金额
     * @param $oid
     * $oid  string store_order表中的id
     */
    public static function storeProductOrderRefundYAfter($data,$oid){
       StoreOrder::refundTemplate($data,$oid);
    }

    /**
     * 修改状态为  不退款
     * @param $data
     *  $data string  退款原因
     * @param $oid
     * $oid  string store_order表中的id
     */
    public static function storeProductOrderRefundNAfter($data,$oid){

    }
    /**
     * 线下付款
     * @param $id
     * $id 订单id
     */
    public static function storeProductOrderOffline($id){

    }

    /**
     * 修改订单状态
     * @param $data
     *  data  total_price 商品总价   pay_price 实际支付
     * @param $oid
     * oid 订单id
     */
    public static function storeProductOrderEditAfter($data,$oid){

    }
    /**
     * 修改送货信息
     * @param $data
     *  $data array  送货人姓名/快递公司   送货人电话/快递单号
     * @param $oid
     * $oid  string store_order表中的id
     */
    public static function storeProductOrderDistributionAfter($data,$oid){

    }

    /**
     * 用户申请退款
     * @param $oid
     * @param $uid
     */
    public static function storeProductOrderApplyRefundAfter($oid, $uid)
    {
        $order = StoreOrder::where('id',$oid)->find();
        WechatTemplateService::sendAdminNoticeTemplate([
            'first'=>"亲,您有一个订单申请退款 \n订单号:{$order['order_id']}",
            'keyword1'=>'申请退款',
            'keyword2'=>'待处理',
            'keyword3'=>date('Y/m/d H:i',time()),
            'remark'=>'请及时处理'
        ]);
    }


    /**
     * 评价产品
     * @param $replyInfo
     * @param $cartInfo
     */
    public static function storeProductOrderReply($replyInfo, $cartInfo)
    {
        //StoreOrder::checkOrderOver($cartInfo['oid']);
    }


    /**
     * 退积分
     * @param $product
     * $product 商品信息
     * @param $back_integral
     * $back_integral 退多少积分
     */
    public static function storeOrderIntegralBack($product,$back_integral){

    }




}