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

class Gameapi extends GameController
{

 
    public function index() {
//         $data['energy']=11;
// $data['energy_time']=time()+600;
// Db::name('user_idiom')->where('uid', $this->userInfo['uid'])->update($data);
      // var_dump(date('Y-m-d H:i:s', time()+300));exit();
      if(!$this->userInfo['uid']){
        $this->userInfo['uid']=1;
      }
      if(!$this->userInfo['uid']){
        return $this->failed('信息错误',Url::build('Index/index'));
      }else{
        $uid=$this->userInfo['uid'];
        $ret=Db::name('user_idiom')->where('uid',$uid)->find();
        if($ret){
          $u_info=Db::name('user_idiom')->where('uid',$uid)->find();
        }else{
          $data=['uid'=>$this->userInfo['uid'],'ci_water'=>10,'time_water'=>time()+300,'is_buy'=>0];
          Db::name('user_idiom')->insert($data);
        }

      }
      //更新day
      $this->is_day($this->userInfo['uid']);
      $user = User::find($this->userInfo['uid']);
      $last_info=Db::name('user_idiom')->where('uid',$uid)->find();
      $last_info['integral']=floatval($user['integral']);
      $last_info['nickname']=$user['nickname'];
      $last_info['avatar']=$user['avatar'];
      //种子列表
      $pet_list=Db::name('idiom_pets')->where('status',1)->order('id desc')->select();
     
        $differ_time=$last_info['time_water']- time();
        if($last_info['time_water']>0 && $differ_time>0){
           $time=$differ_time%300*1000;
           if($time==0){
              $time=1000*60*5;
           }
        }else{
           $time=0;
        }
        $last_info['time_water']=$time;
        //当前种的果实
         $fruit_list=Db::name('idiom_pets')->where('status',1)->where('id',$last_info['is_zhong'])->find();
         //获取仓库
         $fruit_info=Db::name('idiom_fruit')->where(['status'=>1,'uid'=>$this->userInfo['uid']])->order('id desc')->select();
         foreach ($fruit_info as $key => $value) {
           $qian=Db::name('idiom_pets')->where('status',1)->where('id',$value['fid'])->find();
           $fruit_info[$key]['add_energy']=$qian['add_energy'];
           $fruit_info[$key]['img']=$qian['pet_simg'];
           $fruit_info[$key]['name']=$qian['pet_name'];

         }

         //获取记录
         //采摘水果记录
         $record_fruit=Db::name('game_bill')->where(['type'=>'fruit_nums','status'=>1,'uid'=>$this->userInfo['uid']])->order('id desc')->select();
         $record_other=Db::name('game_bill')->where(['status'=>1,'uid'=>$this->userInfo['uid']])->where('type != "fruit_nums"')->order('id desc')->select();
         foreach ($record_other as $key => $value) {
           $record_other[$key]['number']=ceil($value['number']);
         }
         $this->assign('record_fruit',$record_fruit);
         $this->assign('record_other',$record_other);
         $this->assign('fruit_list',$fruit_list);
         $this->assign('fruit_info',$fruit_info);
      $this->assign('pet_list',$pet_list);
      $this->assign('last_info',$last_info);
      $this->assign('user_id',$this->userInfo['uid']);
    return $this->fetch();
	}

//自动更新精力
   public function is_day($uid){
      $u_info=Db::name('user_idiom')->where('uid',$uid)->find();
      $last_y=date('Y',$u_info['new_time']);
      $last_m=date('m',$u_info['new_time']);
      $last_d=date('d',$u_info['new_time']);
      $now_y=date('Y',time());
      $now_m=date('m',time());
      $now_d=date('d',time());
      // var_dump('last_y'.$last_y);
      // var_dump('last_m'.$last_m);
      // var_dump('last_d'.$last_d);
      // var_dump('now_y'.$now_y);
      // var_dump('now_m'.$now_m);
      // var_dump('now_d'.$now_d);
      if($u_info['is_day']==1){
      if($now_y > $last_y){
        $data['is_day']=0;
        $data['new_time']=time();
      }else if ($now_m > $last_m) {
          $data['is_day']=0;
          $data['new_time']=time();
      }else if ($now_d > $last_d) {
          $data['is_day']=0;
          $data['new_time']=time();
      }else{
          $data['is_day']=$u_info['is_day'];
          $data['new_time']=$u_info['new_time'];
      }
    }else{
      $data['is_day']=$u_info['is_day'];
          $data['new_time']=$u_info['new_time'];
    }
        
         Db::name('user_idiom')->where('uid', $uid)->update($data);
      
   }

   //ajax更新水滴
    public function ajax_energy($energy){
      $res['energy']=$energy;

      $u_info=Db::name('user_idiom')->where('uid',$this->userInfo['uid'])->find();
      
           $data['time_water']=time()+300;
         
         $data['z_water']=$u_info['z_water']+$energy;
         Db::name('user_idiom')->where('uid', $this->userInfo['uid'])->update($data);
      
      $pu_info=Db::name('user_idiom')->where('uid',$this->userInfo['uid'])->find();
      $differ_times=$pu_info['time_water']- time();
        if($pu_info['time_water']>0 && $differ_times>0){
           $res['time']=$differ_times%300*1000;
           if($res['time']==0){
              $res['time']=1000*60*5;
           }
        }else{
           $res['time']=0;
        }

        $res['z_water']=$pu_info['z_water'];
        $data_bill=['uid'=>$this->userInfo['uid'],'pm'=>1,'title'=>'采集收获水滴','category'=>'z_water','type'=>'z_water','number'=>$energy,'balance'=>$res['z_water'],'mark'=>'采集收获水滴'.$energy,'add_time'=>time(),'status'=>1];
          Db::name('game_bill')->insert($data_bill);
        return JsonService::successful('成功！',$res);
    }

     //每日更新水滴
    public function add_day($add_water){

      $u_info=Db::name('user_idiom')->where('uid',$this->userInfo['uid'])->find();
         
         $data['z_water']=$u_info['z_water']+$add_water;
         $data['is_day']=1;
         Db::name('user_idiom')->where('uid', $this->userInfo['uid'])->update($data);
          $data_bill=['uid'=>$this->userInfo['uid'],'pm'=>1,'title'=>'每日登录水滴','category'=>'z_water','type'=>'z_water','number'=>$add_water,'balance'=>$data['z_water'],'mark'=>date('Y/m/d',time()).'登录收获水滴'.$add_water,'add_time'=>time(),'status'=>1];
          Db::name('game_bill')->insert($data_bill);
        return JsonService::successful('成功！',$data);
    }
     //更新浇水
    public function waterings($cut_water){
      $u_info=Db::name('user_idiom')->where('uid',$this->userInfo['uid'])->find();
         if($u_info['ci_water']==0){
          $data['status']=0;
            return JsonService::successful('失败',$data);
         }
         $data['z_water']=$u_info['z_water']-$cut_water;
         $data['ci_water']=$u_info['ci_water']-1;
         Db::name('user_idiom')->where('uid', $this->userInfo['uid'])->update($data);
          $data_bill=['uid'=>$this->userInfo['uid'],'pm'=>0,'title'=>'浇水消耗水滴','category'=>'z_water','type'=>'z_water','number'=>$cut_water,'balance'=>$data['z_water'],'mark'=>'浇水消耗水滴'.$cut_water,'add_time'=>time(),'status'=>1];
          Db::name('game_bill')->insert($data_bill);
        return JsonService::successful('成功！',$data);
    }
    //收获果实
     public function fruits($zhong_id){
      // $data['status']=1;
      // return JsonService::successful('失败',$data);
      $u_info=Db::name('user_idiom')->where('uid',$this->userInfo['uid'])->find();
         if($zhong_id==0){
          $data['status']=0;
            return JsonService::successful('失败',$data);
         }
        $ret=Db::name('idiom_fruit')->where(['uid'=> $this->userInfo['uid'],'fid'=>$zhong_id])->find();
        if($ret){
          $zhong_nums=$ret['fruit_nums']+1;
          Db::name('idiom_fruit')->where(['uid'=> $this->userInfo['uid'],'fid'=>$zhong_id])->update(['fruit_nums'=>$ret['fruit_nums']+1]);
        }else{
          $zhong_nums=1;
          $datas=['uid'=>$this->userInfo['uid'],'fruit_nums'=>1,'fid'=>$zhong_id,'status'=>1];
          Db::name('idiom_fruit')->insert($datas);
        }
         $data['is_zhong']=0;
         $data['ci_water']=10;
         Db::name('user_idiom')->where('uid', $this->userInfo['uid'])->update($data);
         $r=Db::name('idiom_pets')->where(['id'=>$zhong_id])->find();
          $data_bill=['uid'=>$this->userInfo['uid'],'pm'=>1,'title'=>'收获果实','category'=>$r['pet_name'],'type'=>'fruit_nums','number'=>1,'balance'=>$zhong_nums,'mark'=>'收获果实1','add_time'=>time(),'status'=>1];
          Db::name('game_bill')->insert($data_bill);
        return JsonService::successful('成功！',$data);
    }
     //购买种子并种下
     public function zhongxia($pet_id){
      $u_info=Db::name('user_idiom')->where('uid',$this->userInfo['uid'])->find();

         if($pet_id==0){
          $data['status']=0;
            return JsonService::successful('失败',$data);
         }
         $pet_info=Db::name('idiom_pets')->where('id',$pet_id)->find();
         $data['is_zhong']=$pet_id;
         $data['ci_water']=10;

         Db::name('user_idiom')->where('uid', $this->userInfo['uid'])->update($data);
         $user = User::find($this->userInfo['uid']);
         if($user['integral']<$pet_info['need_integral']){
            $data['status']=1;
            return JsonService::successful('失败',$data);
         }
        $jfs['integral'] = $user['integral']-$pet_info['need_integral'];
        $jfs['uid']=$this->userInfo['uid'];
        $data['integral']=$jfs['integral'];
        $res = User::update($jfs);
          $data_bill=['uid'=>$this->userInfo['uid'],'pm'=>0,'title'=>'购买种子','category'=>'integral','type'=>'integral','number'=>$pet_info['need_integral'],'balance'=>$jfs['integral'],'mark'=>'购买种子并种下'.$pet_info['need_integral'],'add_time'=>time(),'status'=>1];
          Db::name('game_bill')->insert($data_bill);
          UserBill::expend('购买种子并种下',$this->userInfo['uid'],'integral','buyzhong_add_',$pet_info['need_integral'],0,$jfs['integral'],'购买种子并种下,消耗'.floatval($pet_info['need_integral']).'积分');
        return JsonService::successful('成功！',$data);
    }

     //售出果实得积分
     public function shouchu($num,$pet_id){
      $fruit_info=Db::name('idiom_fruit')->where(['uid'=> $this->userInfo['uid'],'fid'=>$pet_id])->find();;
        $pet_info=Db::name('idiom_pets')->where('id',$pet_id)->find();
         if($num<$fruit_info['fruit_nums']){
          Db::name('idiom_fruit')->where(['uid'=> $this->userInfo['uid'],'fid'=>$pet_id])->update(['fruit_nums'=>$fruit_info['fruit_nums']-$num]);
          $data['num']=$fruit_info['fruit_nums']-$num;
         }else{
          Db::name('idiom_fruit')->where(['uid'=> $this->userInfo['uid'],'fid'=>$pet_id])->delete();
          $data['num']=0;
         }
         
         $user = User::find($this->userInfo['uid']);
         $jifen=$num*$pet_info['add_energy'];
        $jfs['integral'] = $user['integral']+$jifen;
        $jfs['uid']=$this->userInfo['uid'];
        $data['integral']=$jfs['integral'];
        $res = User::update($jfs);
          $data_bill=['uid'=>$this->userInfo['uid'],'pm'=>1,'title'=>'售出'.$pet_info['pet_name'],'category'=>'integral','type'=>'integral','number'=>$jifen,'balance'=>$jfs['integral'],'mark'=>'售出果实得积分'.$jifen,'add_time'=>time(),'status'=>1];
          Db::name('game_bill')->insert($data_bill);
          UserBill::income('售出果实得积分',$this->userInfo['uid'],'integral','shouchu_',$jifen,0,$jfs['integral'],'售出果实得积分,获得'.floatval($jifen).'积分');
        return JsonService::successful('成功！',$data);
    }
}
