<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/21
 */

namespace app\wap\controller;


use Api\Express;
use app\admin\model\system\SystemConfig;
use app\wap\model\store\StoreBargainUser;
use app\wap\model\store\StoreBargainUserHelp;
use app\wap\model\store\StoreCombination;
use app\wap\model\store\StoreOrderCartInfo;
use app\wap\model\store\StorePink;
use app\wap\model\store\StoreProduct;
use app\wap\model\store\StoreProductRelation;
use app\wap\model\store\StoreProductReply;
use app\wap\model\store\StoreCouponUser;
use app\wap\model\store\StoreOrder;
use app\wap\model\user\User;
use app\wap\model\user\WechatUser;
use app\wap\model\user\UserBill;
use app\wap\model\user\UserExtract;
use app\wap\model\user\UserNotice;
use app\core\util\GroupDataService;
use app\wap\model\user\UserAddress;
use app\wap\model\user\UserSign;
use app\wap\model\user\UserRecharge;
use service\CacheService;
use app\core\util\SystemConfigService;
use think\Request;
use think\Url;
use think\DB;
use service\JsonService;
use service\UtilService;
use Api\Storage\Qiniu\Qiniu;
use service\UploadService;

class Activ extends AuthController
{

   //转盘
    public function turntable()
    {
    	if(!$this->userInfo['uid']){
        return $this->failed('信息错误',Url::build('Index/index'));
      }else{
        $uid=$this->userInfo['uid'];
        $user_info = User::find($this->userInfo['uid']);
    }
      $this->assign('user_info',$user_info);

        return $this->fetch();
    }
    	//减积分
     public function ajax_integral($integral){
     	$user = User::find($this->userInfo['uid']);
     			 if($user['integral']<$integral){
        return JsonService::fail('积分不足');
      }
      $us['integral']=$user['integral']-$integral;
      $us['uid']=$this->userInfo['uid'];
     		 $res = User::update($us);

     	 UserBill::expend('成语填词在线抽奖',$this->userInfo['uid'],'integral','game_jian_jf',$integral,0,$user['integral'],'在线抽奖支付'.floatval($integral).'积分');
     	 return JsonService::successful('成功！',$us);
      
   }

 
}
