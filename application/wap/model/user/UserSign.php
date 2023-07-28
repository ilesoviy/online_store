<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2018/02/28
 */

namespace app\wap\model\user;


use basic\ModelBasic;
use app\core\util\SystemConfigService;
use think\Model;

class UserSign
{
    public static function checkUserSigned($uid)
    {
        return UserBill::be(['uid'=>$uid,'add_time'=>['>',strtotime('today')],'category'=>'integral','type'=>'sign']);
    }

    public static function userSignedCount($uid)
    {
        return self::userSignBillWhere($uid)->count();
    }

    /**
     * @param $uid
     * @return Model
     */
    public static function userSignBillWhere($uid)
    {
        return UserBill::where(['uid'=>$uid,'category'=>'integral','type'=>'sign']);
    }

    // public static function sign($userInfo)
    // {
    //     $uid = $userInfo['uid'];
    //     $min = SystemConfigService::get('sx_sign_min_int')?:0;
    //     $max = SystemConfigService::get('sx_sign_max_int')?:5;
    //     $integral = rand($min,$max);
    //     ModelBasic::beginTrans();
    //     $res1 = UserBill::income('用户签到',$uid,'integral','sign',$integral,0,bcadd($userInfo['integral'],$integral,2),'签到获得'.floatval($integral).'积分');
    //     $res2 = User::bcInc($uid,'integral',$integral,'uid');
    //     $res = $res1 && $res2;
    //     ModelBasic::checkTrans($res);
    //     if($res)
    //         return $integral;
    //     else
    //         return false;
    // }

     public static function sign($userInfo)
    {
        $uid = $userInfo['uid'];
        // $min = SystemConfigService::get('sx_sign_min_int')?:0;
        // $max = SystemConfigService::get('sx_sign_max_int')?:5;
        // $integral = rand($min,$max);
        $integral=0.88;
        ModelBasic::beginTrans();
        $res1 = UserBill::income('每日簽到獎勵',$uid,'integral','sign',$integral,0,bcadd($userInfo['integral'],$integral,2),'簽到獲得$'.floatval($integral),'Daily sign-in reward','Check in to get'.floatval($integral),0);
        // $res2 = User::bcInc($uid,'integral',$integral,'uid');
        $res2=true;
        $res = $res1 && $res2;
        ModelBasic::checkTrans($res);
        if($res)
            return $integral;
        else
            return false;
    }
}