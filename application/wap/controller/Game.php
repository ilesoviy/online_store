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

class Game extends AuthController
{

   //成语填词
    public function idmio()
    {   
    	// var_dump($this->userInfo['uid']);exit();
    	if(!$this->userInfo['uid']){
    		return $this->failed('信息错误',Url::build('My/game_list'));
    	}else{
    		$uid=$this->userInfo['uid'];
    		$ret=Db::name('game_idmio')->where('uid',$uid)->find();
    		if($ret){
    			$u_info=Db::name('game_idmio')->where('uid',$uid)->find();
    		}else{
    			$data=['uid'=>$this->userInfo['uid']];
    			Db::name('game_idmio')->insert($data);
    		}

    	}
    	$this->assign('uid',$this->userInfo['uid']);
        return $this->fetch();
    }

    //初始化关卡数据
    public function index(Request $request) {

		$data = UtilService::postMore([


           	['uid', 0],

           

       	], $request);



		if (!$data['uid']) {

			return JsonService::fail('初始化数据失败！');	

		}



       	$user = User::find($data['uid']);
        $u_info=Db::name('game_idmio')->where('uid',$this->userInfo['uid'])->find();
       	$money = $u_info['num']*10;

       	$u_info['integral'] = $money;



       	if (!$u_info) {

       		return JsonService::fail('初始化数据失败！');	

       	}

		return JsonService::successful('初始化数据成功',$u_info);

	}


  // 成语填词更新数据

	public function idmioUpdate(Request $request) {
		$data = UtilService::postMore([

			// ['gid', 0],

           	['uid', 0],

           	['integral', 0]

       	], $request);

       	$user = User::find($data['uid']);
        $u_info=Db::name('game_idmio')->where('uid',$this->userInfo['uid'])->find();
       	$integral = $data['integral'];

 		
       

       	$data['integral'] += $user['integral'];
          $datas['num']=1;
       	$datas['num'] += $u_info['num'];
  
		 $res1=Db::name('game_idmio')->where('uid', $this->userInfo['uid'])->update(['num' => $datas['num']]);
		$res = User::update($data);


		if (!$res) {

			return JsonService::fail('数据出错！');	

		}

		$res1 = UserBill::income('成语填词游戏',$this->userInfo['uid'],'integral','game_add_'.$datas['num'],$integral,0,$user['integral'],'成语填词通关获得'.floatval($integral).'积分');

		return JsonService::successful('成功！',$data);

	}
	//飞机大战
	public function aircraft(){
		if(!$this->userInfo['uid']){
    		return $this->failed('信息错误',Url::build('My/game_list'));
    	}
    	$this->assign('uid',$this->userInfo['uid']);
        return $this->redirect('https://h5app.xinyuad.net/public/wap/first/newgame/aircraft/index.html');
	}

	public function aircraftUpdate(Request $request) {

		$data = UtilService::postMore([


           	['integral', 0]

       	], $request);
        $data['integral']=ceil($data['integral']*0.01);
         $data['uid']=$this->userInfo['uid'];
       	$user = User::find($data['uid']);

       	$integral = $data['integral'];



       	$data['integral'] += $user['integral'];

       

		$res = User::update($data);

		if (!$res) {

			return JsonService::fail('数据出错！');	

		}

		$res1 = UserBill::income('全名飞机大战',$user['uid'],'integral','game_add_fj',$integral,0,$user['integral'],'全名飞机大战获得'.floatval($integral).'积分');
          $data['jifen']=$integral;
		return JsonService::successful('成功！',$data);

	}

  //跳跳熊猫
  public function xiongmao(){
    if(!$this->userInfo['uid']){
        return $this->failed('信息错误',Url::build('My/game_list'));
      }
      $this->assign('uid',$this->userInfo['uid']);
        return $this->redirect('https://h5app.xinyuad.net/public/wap/first/newgame/xiongmao/index.html');
  }
  //拼图
  public function fjpt(){
    if(!$this->userInfo['uid']){
        return $this->failed('信息错误',Url::build('My/game_list'));
      }
      $this->assign('uid',$this->userInfo['uid']);
        return $this->redirect('https://h5app.xinyuad.net/public/wap/first/newgame/fjpt/index.htm');
  }

 
}
