<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/23
 */

namespace app\wap\model\store;


use basic\ModelBasic;
use traits\ModelTrait;
use app\wap\model\store\StoreOrder;
use app\wap\model\store\StoreProduct;
use app\wap\model\store\StoreCart;
use app\wap\model\store\StoreOrderStatus;
use app\wap\model\user\User;
use app\wap\model\user\UserBill;
use app\admin\model\system\SystemConfig;
use think\Db;

class Robot extends ModelBasic
{
    use ModelTrait;

    /**
     * 机器人参团
     */
    public static function JoinRobot($order_id=''){
    	$orderinfo=StoreOrder::where('order_id',$order_id)->find();
    	if($orderinfo){
    		$product=StoreProduct::where(['id'=>['in',StoreCart::where(['id'=>['in',$orderinfo['cart_id']]])->column('product_id')]])->find();
    		if($orderinfo['pin_num']<$product['spell_num']){
    			$Robot=self::orderRaw('rand()')->limit(1)->find();
    			StoreOrder::where('order_id', $order_id)->update(['pin_num' => $orderinfo['pin_num']+1]);
    			$data['user_id']=$Robot['id'];
    			$data['order_id']=$order_id;
    			$data['follow_time']=time();
    			$data['status']=1;
    			db('team_follow')->insert($data);
    		}
    		return 1;
    	}
    }
    /**
     * 参团中奖概率计算分配
     */
    public static function Probability($order_id=''){
    	$orderinfo=StoreOrder::where('order_id',$order_id)->find();
    	$userInfo=User::where('uid',$orderinfo['uid'])->find();

    	if($orderinfo){
    		$product=StoreProduct::where(['id'=>['in',StoreCart::where(['id'=>['in',$orderinfo['cart_id']]])->column('product_id')]])->find();
    		if($orderinfo['pin_num']>=$product['spell_num']){
                  
    			//拼团成功概率
    			$spell_probability=(int)SystemConfig::getValue('spell_probability')*10;
    			if(rand(1,100)<=$spell_probability){
                    self::backOrderBrokerage($orderinfo);
    				//成功
    				//退款
    				// User::bcInc($orderinfo['uid'],'now_money',$orderinfo['pay_price'],'uid');
    				//更改订单状态
    				StoreOrder::where('order_id',$order_id)->update(['refund_status'=>2,'refund_reason_time'=>time(),'refund_price'=>$orderinfo['pay_price'],'pin_status'=>2]);
    				db('team_follow')->where('order_id',$order_id)->update(['status'=>3]);
    				//订单日志
    				StoreOrderStatus::status($orderinfo['id'],'admin','拼团成功，系统退款');
                    UserBill::income('拼团成功',$orderinfo['uid'],'now_money','pin_success',$orderinfo['pay_price'],0,bcadd($userInfo['now_money'],$orderinfo['pay_price'],2),'拼團成功，系統退款$'.round($orderinfo['pay_price'],2),'Group success','If the group is successfully assembled, the system will refund'.round($orderinfo['pay_price'],2));
    				//发放佣金
    				$spell_success=(int)SystemConfig::getValue('spell_success')/100*$orderinfo['pay_price'];
    				// User::bcInc($orderinfo['uid'],'integral',$spell_success,'uid');
    				//佣金日志
                    if($spell_success>0){
                        UserBill::income('拼团成功',$orderinfo['uid'],'integral','Collage',$spell_success,0,bcadd($userInfo['integral'],$spell_success,2),'拼团成功得佣金$'.round($spell_success,2),'Group success','Commission for success get'.round($spell_success,2));
                    }
    				
    			}else{
                    self::backOrderBrokerageShi($orderinfo);
    				//失败
    				//退款
    				// User::bcInc($orderinfo['uid'],'now_money',$orderinfo['pay_price'],'uid');
    				//更改订单状态
    				StoreOrder::where('order_id',$order_id)->update(['refund_status'=>2,'refund_reason_time'=>time(),'refund_price'=>$orderinfo['pay_price'],'pin_status'=>3,'status'=>'-1']);
    				db('team_follow')->where('order_id',$order_id)->update(['status'=>3]);
    				//订单日志
    				StoreOrderStatus::status($orderinfo['id'],'admin','拼团失败，系统退款');
                    UserBill::income('拼團失败',$orderinfo['uid'],'now_money','pin_fail',$orderinfo['pay_price'],0,bcadd($userInfo['now_money'],$orderinfo['pay_price'],2),'拼團失败，系統退款$'.round($orderinfo['pay_price'],2),'Group success','Group collage failed, system refund'.round($orderinfo['pay_price'],2));
    				//发放佣金
    				$spell_failure=(int)SystemConfig::getValue('spell_failure')/100*$orderinfo['pay_price'];
    				// User::bcInc($orderinfo['uid'],'integral',$spell_failure,'uid');
    				//佣金日志
                    if($spell_failure>0){
                        UserBill::income('拼团失败',$orderinfo['uid'],'integral','Collage',$spell_failure,0,bcadd($userInfo['integral'],$spell_failure,2),'拼团失败得佣金$'.round($spell_failure,2),'Group failure','Commission for failure get'.round($spell_failure,2));
                    }
    				
    				$team_follow=db('team_follow')->where('order_id',$order_id)->orderRaw('rand()')->limit(1)->find();
    				db('team_follow')->where('id',$team_follow['id'])->update(['status'=>2]);
    			}
    		}else{
    			self::JoinRobot($order_id);
    		}
    		return 1;
    	}
    }
    /**
     * 获取拼团的团长
     */
    public static function getHead($product_id=0){
    		$head_uid=StoreCart::where(['product_id'=>$product_id,'pin_type'=>1,'is_pay'=>1])->column('uid');
    		$user=User::where(['uid'=>['in',$head_uid]])->field('uid,nickname,avatar')->select();

    		return $user;


    }

    /**
     * 判断能否成团
     */
    public static function isAgglomerate($uid=0){
    	 $res=StoreOrder::where(['uid'=>$uid,'pin_status'=>1])->find();
    	 if($res){
    	 	return false;
    	 }else{
    	 	return true;
    	 }
    }



    /**
     * 获取拼团的信息
     */
    public static function getCollageInfo($order_id=0){
    		$head_uid=StoreOrder::where(['order_id'=>$order_id,'pin_type'=>1])->value('uid');
    		$user=User::where(['uid'=>$head_uid])->field('uid,nickname,avatar')->select()->toArray();
    		$follow=db('team_follow')->where(['order_id'=>$order_id])->column('user_id');
    		$follow_user=self::where(['id'=>['in',$follow]])->field('id,nickname,avatar')->select()->toArray();
    		if(count($follow_user)>0){
    			$res=array_merge($user,$follow_user);
    		}else{
    			$res=$user;
    		}
    		$orderinfo=StoreOrder::where('order_id',$order_id)->find();
    		$product=StoreProduct::where(['id'=>['in',StoreCart::where(['id'=>['in',$orderinfo['cart_id']]])->column('product_id')]])->find();
    		if($orderinfo['pin_num']<$product['spell_num']){
    			$num=$product['spell_num']-$orderinfo['pin_num'];
    			for($i=0;$i<$num;$i++){
    				$res=array_merge($res,[[]]);
    			}
    		}

    		return [$res,$product['spell_num']-$orderinfo['pin_num']];


    }

    /**
     * TODO 一级返佣(成功)
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerage($orderInfo)
    {
        //TODO 如果不是拼团产品不返佣金
        if($orderInfo['pin_type']==0) return true;

        //TODO 支付金额减掉邮费
        $orderInfo['pay_price'] = bcsub($orderInfo['pay_price'],$orderInfo['pay_postage'],2);
        //TODO 获取购买商品的用户
        $userInfo = User::getUserInfo($orderInfo['uid']);
        //TODO 当前用户不存在 或者 没有上级 直接返回
        if(!$userInfo || !$userInfo['spread_uid']) return true;
        //TODO 获取后台分销类型  1 指定分销 2 人人分销
        $storeBrokerageStatus = SystemConfig::getValue('store_brokerage_statu');

        $storeBrokerageStatus = $storeBrokerageStatus ? $storeBrokerageStatus : 1;
        //TODO 指定分销 判断 上级是否时推广员  如果不是推广员直接跳转二级返佣
        if($storeBrokerageStatus == 1){
            if(!User::be(['uid'=>$userInfo['spread_uid'],'is_promoter'=>1])) return self::backOrderBrokerageTwo($orderInfo);
        }
        //TODO 获取后台一级返佣比例
        $storeBrokerageRatio = SystemConfig::getValue('store_brokerage_ratio');
        //TODO 一级返佣比例 小于等于零时直接返回 不返佣
        if($storeBrokerageRatio <= 0) return true;
        //TODO 计算获取一级返佣比例
        $brokerageRatio = bcdiv($storeBrokerageRatio,100,2);
        // //TODO 成本价
        $cost = isset($orderInfo['cost']) ? $orderInfo['cost'] : 0;
        // //TODO 成本价大于等于支付价格时直接返回
        // if($cost >= $orderInfo['pay_price']) return true;
        //TODO 获取订单毛利
        // $payPrice = bcsub($orderInfo['pay_price'],$cost,2);
        $payPrice = $orderInfo['pay_price'];
        //TODO 返佣金额 = 毛利 / 一级返佣比例
        $brokeragePrice = bcmul($payPrice,$brokerageRatio,2);
        // return $brokeragePrice;
        //TODO 返佣金额小于等于0 直接返回不返佣金
        if($brokeragePrice <= 0) return true;
        //TODO 获取上级推广员信息
        $spreadUserInfo = User::getUserInfo($userInfo['spread_uid']);
        //TODO 上级推广员返佣之后的金额
        $balance = bcadd($spreadUserInfo['integral'],$brokeragePrice,2);
        $mark = $userInfo['nickname'].'拼團成功消費 $'.floatval($orderInfo['pay_price']).',獎勵推廣傭金'.floatval($brokeragePrice);
        $mark_en = $userInfo['nickname'].'Group success consumption $'.floatval($orderInfo['pay_price']).',Incentive Promotion Commission'.floatval($brokeragePrice);
        self::beginTrans();
        //TODO 添加推广记录
        $res1 = UserBill::income('獲得推廣傭金',$userInfo['spread_uid'],'integral','brokerage',$brokeragePrice,$orderInfo['id'],$balance,$mark,'Get promotion commission',$mark_en);
        //TODO 添加用户余额
        // $res2 = User::bcInc($userInfo['spread_uid'],'integral',$brokeragePrice,'uid');
        $res2 = true;
        $res = $res1 && $res2;
        self::checkTrans($res);
        //TODO 一级返佣成功 跳转二级返佣
        if($res) return self::backOrderBrokerageTwo($orderInfo);
        return $res;
    }

    /**
     * TODO 二级推广成功
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerageTwo($orderInfo){
        //TODO 获取购买商品的用户
        $userInfo = User::getUserInfo($orderInfo['uid']);
        //TODO 获取上推广人
        $userInfoTwo = User::getUserInfo($userInfo['spread_uid']);
        //TODO 上推广人不存在 或者 上推广人没有上级 直接返回
        if(!$userInfoTwo || !$userInfoTwo['spread_uid']) return true;
        //TODO 获取后台分销类型  1 指定分销 2 人人分销
        $storeBrokerageStatus = SystemConfig::getValue('store_brokerage_statu');
        $storeBrokerageStatus = $storeBrokerageStatus ? $storeBrokerageStatus : 1;
        //TODO 指定分销 判断 上上级是否时推广员  如果不是推广员直接返回
        if($storeBrokerageStatus == 1){
            if(!User::be(['uid'=>$userInfoTwo['spread_uid'],'is_promoter'=>1]))  return true;
        }
        //TODO 获取二级返佣比例
        $storeBrokerageTwo = SystemConfig::getValue('store_brokerage_two');
        //TODO 二级返佣比例小于等于0 直接返回
        if($storeBrokerageTwo <= 0) return true;
        //TODO 计算获取二级返佣比例
        $brokerageRatio = bcdiv($storeBrokerageTwo,100,2);
        //TODO 获取成本价
        $cost = isset($orderInfo['cost']) ? $orderInfo['cost'] : 0;
        //TODO 成本价大于等于支付价格时直接返回
        // if($cost >= $orderInfo['pay_price']) return true;
        //TODO 获取订单毛利
        // $payPrice = bcsub($orderInfo['pay_price'],$cost,2);
        $payPrice = $orderInfo['pay_price'];
        //TODO 返佣金额 = 毛利 / 二级返佣比例
        $brokeragePrice = bcmul($payPrice,$brokerageRatio,2);
        //TODO 返佣金额小于等于0 直接返回不返佣金
        if($brokeragePrice <= 0) return true;
        //TODO 获取上上级推广员信息
        $spreadUserInfoTwo = User::getUserInfo($userInfoTwo['spread_uid']);
        //TODO 获取上上级推广员返佣之后余额
        $balance = bcadd($spreadUserInfoTwo['integral'],$brokeragePrice,2);
        $mark = '二級推廣人'.$userInfo['nickname'].'拼團成功消費 $'.floatval($orderInfo['pay_price']).',獎勵推廣傭金'.floatval($brokeragePrice);
        $mark_en = 'Secondary promoter'.$userInfo['nickname'].'Group success consumption $'.floatval($orderInfo['pay_price']).',Incentive Promotion Commission'.floatval($brokeragePrice);
        self::beginTrans();
        //TODO 添加返佣记录
        $res1 = UserBill::income('获得推广佣金',$userInfoTwo['spread_uid'],'integral','brokerage',$brokeragePrice,$orderInfo['id'],$balance,$mark,'Get promotion commission',$mark_en);
        //TODO 添加用户余额
        // $res2 = User::bcInc($userInfoTwo['spread_uid'],'integral',$brokeragePrice,'uid');
        $res2 = true;
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
    }
    /**
     * TODO 一级返佣(失败)
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerageShi($orderInfo)
    {
        //TODO 如果不是拼团产品不返佣金
        if($orderInfo['pin_type']==0) return true;

        //TODO 支付金额减掉邮费
        $orderInfo['pay_price'] = bcsub($orderInfo['pay_price'],$orderInfo['pay_postage'],2);
        //TODO 获取购买商品的用户
        $userInfo = User::getUserInfo($orderInfo['uid']);
        //TODO 当前用户不存在 或者 没有上级 直接返回
        if(!$userInfo || !$userInfo['spread_uid']) return true;
        //TODO 获取后台分销类型  1 指定分销 2 人人分销
        $storeBrokerageStatus = SystemConfig::getValue('store_brokerage_statu');

        $storeBrokerageStatus = $storeBrokerageStatus ? $storeBrokerageStatus : 1;
        //TODO 指定分销 判断 上级是否时推广员  如果不是推广员直接跳转二级返佣
        if($storeBrokerageStatus == 1){
            if(!User::be(['uid'=>$userInfo['spread_uid'],'is_promoter'=>1])) return self::backOrderBrokerageTwoShi($orderInfo);
        }
        //TODO 获取后台一级返佣比例
        $storeBrokerageRatio = SystemConfig::getValue('store_brokerage_ratio_shi');
        //TODO 一级返佣比例 小于等于零时直接返回 不返佣
        if($storeBrokerageRatio <= 0) return true;
        //TODO 计算获取一级返佣比例
        $brokerageRatio = bcdiv($storeBrokerageRatio,100,2);
        // //TODO 成本价
        $cost = isset($orderInfo['cost']) ? $orderInfo['cost'] : 0;
        // //TODO 成本价大于等于支付价格时直接返回
        // if($cost >= $orderInfo['pay_price']) return true;
        //TODO 获取订单毛利
        // $payPrice = bcsub($orderInfo['pay_price'],$cost,2);
        $payPrice = $orderInfo['pay_price'];
        //TODO 返佣金额 = 毛利 / 一级返佣比例
        $brokeragePrice = bcmul($payPrice,$brokerageRatio,2);
        // return $brokeragePrice;
        //TODO 返佣金额小于等于0 直接返回不返佣金
        if($brokeragePrice <= 0) return true;
        //TODO 获取上级推广员信息
        $spreadUserInfo = User::getUserInfo($userInfo['spread_uid']);
        //TODO 上级推广员返佣之后的金额
        $balance = bcadd($spreadUserInfo['integral'],$brokeragePrice,2);
        $mark = $userInfo['nickname'].'拼團成功消費 $'.floatval($orderInfo['pay_price']).',獎勵推廣傭金'.floatval($brokeragePrice);
        $mark_en = $userInfo['nickname'].'Group success consumption $'.floatval($orderInfo['pay_price']).',Incentive Promotion Commission'.floatval($brokeragePrice);
        self::beginTrans();
        //TODO 添加推广记录
        $res1 = UserBill::income('獲得推廣傭金',$userInfo['spread_uid'],'integral','brokerage',$brokeragePrice,$orderInfo['id'],$balance,$mark,'Get promotion commission',$mark_en);
        //TODO 添加用户余额
        // $res2 = User::bcInc($userInfo['spread_uid'],'integral',$brokeragePrice,'uid');
        $res2 = true;
        $res = $res1 && $res2;
        self::checkTrans($res);
        //TODO 一级返佣成功 跳转二级返佣
        if($res) return self::backOrderBrokerageTwoShi($orderInfo);
        return $res;
    }

    /**
     * TODO 二级推广失败
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerageTwoShi($orderInfo){
        //TODO 获取购买商品的用户
        $userInfo = User::getUserInfo($orderInfo['uid']);
        //TODO 获取上推广人
        $userInfoTwo = User::getUserInfo($userInfo['spread_uid']);
        //TODO 上推广人不存在 或者 上推广人没有上级 直接返回
        if(!$userInfoTwo || !$userInfoTwo['spread_uid']) return true;
        //TODO 获取后台分销类型  1 指定分销 2 人人分销
        $storeBrokerageStatus = SystemConfig::getValue('store_brokerage_statu');
        $storeBrokerageStatus = $storeBrokerageStatus ? $storeBrokerageStatus : 1;
        //TODO 指定分销 判断 上上级是否时推广员  如果不是推广员直接返回
        if($storeBrokerageStatus == 1){
            if(!User::be(['uid'=>$userInfoTwo['spread_uid'],'is_promoter'=>1]))  return true;
        }
        //TODO 获取二级返佣比例
        $storeBrokerageTwo = SystemConfig::getValue('store_brokerage_two_shi');
        //TODO 二级返佣比例小于等于0 直接返回
        if($storeBrokerageTwo <= 0) return true;
        //TODO 计算获取二级返佣比例
        $brokerageRatio = bcdiv($storeBrokerageTwo,100,2);
        //TODO 获取成本价
        $cost = isset($orderInfo['cost']) ? $orderInfo['cost'] : 0;
        //TODO 成本价大于等于支付价格时直接返回
        // if($cost >= $orderInfo['pay_price']) return true;
        //TODO 获取订单毛利
        // $payPrice = bcsub($orderInfo['pay_price'],$cost,2);
        $payPrice = $orderInfo['pay_price'];
        //TODO 返佣金额 = 毛利 / 二级返佣比例
        $brokeragePrice = bcmul($payPrice,$brokerageRatio,2);
        //TODO 返佣金额小于等于0 直接返回不返佣金
        if($brokeragePrice <= 0) return true;
        //TODO 获取上上级推广员信息
        $spreadUserInfoTwo = User::getUserInfo($userInfoTwo['spread_uid']);
        //TODO 获取上上级推广员返佣之后余额
        $balance = bcadd($spreadUserInfoTwo['integral'],$brokeragePrice,2);
        $mark = '二級推廣人'.$userInfo['nickname'].'拼團成功消費 $'.floatval($orderInfo['pay_price']).',獎勵推廣傭金'.floatval($brokeragePrice);
        $mark_en = 'Secondary promoter'.$userInfo['nickname'].'Group success consumption $'.floatval($orderInfo['pay_price']).',Incentive Promotion Commission'.floatval($brokeragePrice);
        self::beginTrans();
        //TODO 添加返佣记录
        $res1 = UserBill::income('获得推广佣金',$userInfoTwo['spread_uid'],'integral','brokerage',$brokeragePrice,$orderInfo['id'],$balance,$mark,'Get promotion commission',$mark_en);
        //TODO 添加用户余额
        // $res2 = User::bcInc($userInfoTwo['spread_uid'],'integral',$brokeragePrice,'uid');
        $res2 = true;
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
    }
}