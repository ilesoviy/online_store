<?php

namespace app\admin\controller\agent;

use app\admin\controller\AuthController;
use app\admin\model\order\StoreOrder;
use app\admin\model\system\SystemAttachment;
use app\admin\model\user\User;
use app\admin\model\wechat\WechatUser as UserModel;
use app\admin\library\FormBuilder;
use app\core\model\routine\RoutineQrcode;
use app\core\model\user\UserBill;
use service\JsonService;
use service\UploadService;
use service\UtilService as Util;

/**
 * 分销商管理控制器
 * Class AgentManage
 * @package app\admin\controller\agent
 */
class AgentManage extends AuthController
{

    /**
     * @return mixed
     */
    public function index()
    {
        $this->assign('store_brokerage_statu',\app\core\util\SystemConfigService::get('store_brokerage_statu'));
        return $this->fetch();
    }
    public function get_spread_list()
    {
        $where=Util::getMore([
            ['nickname',''],
            ['start_time',''],
            ['end_time',''],
            ['sex',''],
            ['excel',''],
            ['subscribe',''],
            ['order',''],
            ['page',1],
            ['limit',20],
            ['user_type',''],
        ]);
        return JsonService::successlayui(UserModel::agentSystemPage($where));
    }

    /**
     * 一级推荐人页面
     * @return mixed
     */
    public function stair($uid = ''){
        if($uid == '') return $this->failed('参数错误');
        $list = User::alias('u')
            ->where('u.spread_uid',$uid)
            ->field('u.avatar,u.nickname,u.now_money,u.spread_time,u.uid')
            ->where('u.status',1)
            ->order('u.add_time DESC')
            ->select()
            ->toArray();
        foreach ($list as $key=>$value) $list[$key]['orderCount'] = StoreOrder::getOrderCount($value['uid'])?:0;
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 二级推荐人页面
     * @return mixed
     */
    public function stair_two($uid = '')
    {
        if($uid == '') return $this->failed('参数错误');
        $spread_uid=User::where('spread_uid',$uid)->column('uid');
        if(count($spread_uid))
            $spread_uid_two=User::where('spread_uid','in',$spread_uid)->column('uid');
        else
            $spread_uid_two=[0];
        $list = User::alias('u')
            ->where('u.uid','in',$spread_uid_two)
            ->field('u.avatar,u.nickname,u.now_money,u.spread_time,u.uid')
            ->where('u.status',1)
            ->order('u.add_time DESC')
            ->select()
            ->toArray();
        foreach ($list as $key=>$value) $list[$key]['orderCount'] = StoreOrder::getOrderCount($value['uid'])?:0;
        $this->assign('list',$list);
        return $this->fetch('stair');
    }

    /*
     * 批量清除推广权限
     * */
    public function delete_promoter()
    {
        list($uids)=Util::postMore([
            ['uids',[]]
        ],$this->request,true);
        if(!count($uids)) return JsonService::fail('请选择需要解除推广权限的用户！');
        User::beginTrans();
        try{
            if(User::where('uid','in',$uids)->update(['is_promoter'=>0])){
                User::commitTrans();
                return JsonService::successful('解除成功');
            }else{
                User::rollbackTrans();
                return JsonService::fail('解除失败');
            }
        }catch (\PDOException $e){
            User::rollbackTrans();
            return JsonService::fail('数据库操作错误',['line'=>$e->getLine(),'message'=>$e->getMessage()]);
        }catch (\Exception $e){
            User::rollbackTrans();
            return JsonService::fail('系统错误',['line'=>$e->getLine(),'message'=>$e->getMessage()]);
        }

    }

    /*
     * 查看公众号推广二维码
     * @param int $uid
     * @return json
     * */
    public function look_code($uid='')
    {
        if($uid=='') return JsonService::fail('缺少参数');
        try{
            $qr_code = \app\core\util\QrcodeService::getForeverQrcode('spread',$uid);
            if(isset($qr_code['url']))
                return JsonService::successful(['code_src'=>$qr_code['url']]);
            else
                return JsonService::fail('获取失败，请稍后再试！');
        }catch (\Exception $e){
            return JsonService::fail('获取推广二维码失败，请检查您的微信配置',['line'=>$e->getLine(),'messag'=>$e->getMessage()]);
        }
    }

    /**
     * TODO 查看小程序推广二维码
     * @param string $uid
     */
    public function look_xcx_code($uid = '')
    {
        if(!strlen(trim($uid))) return JsonService::fail('缺少参数');
        try{
            $userInfo = User::getUserInfos($uid);
            $name = $userInfo['uid'].'_'.$userInfo['is_promoter'].'_user.jpg';
            $imageInfo = SystemAttachment::getInfo($name,'name');
            if(!$imageInfo){
                $res = \app\core\model\routine\RoutineCode::getShareCode($uid, 'spread', '', '');
                if(!$res) return JsonService::fail('二维码生成失败');
                $imageInfo = UploadService::imageStream($name,$res['res'],'routine/spread/code');
                if(!is_array($imageInfo)) return JsonService::fail($imageInfo);
                SystemAttachment::attachmentAdd($imageInfo['name'],$imageInfo['size'],$imageInfo['type'],$imageInfo['dir'],$imageInfo['thumb_path'],1,$imageInfo['image_type'],$imageInfo['time']);
                RoutineQrcode::setRoutineQrcodeFind($res['id'],['status'=>1,'time'=>time(),'qrcode_url'=>$imageInfo['dir']]);
                $urlCode = $imageInfo['dir'];
            }else $urlCode = $imageInfo['att_dir'];
            return JsonService::successful(['code_src'=>$urlCode]);
        }catch (\Exception $e){
            return JsonService::fail('查看推广二维码失败！',['line'=>$e->getLine(),'meassge'=>$e->getMessage()]);
        }
    }
    /*
     * 解除单个用户的推广权限
     * @param int $uid
     * */
    public function delete_spread($uid=0)
    {
        if(!$uid) return JsonService::fail('缺少参数');
        if(User::where('uid',$uid)->update(['is_promoter'=>0]))
            return JsonService::successful('解除成功');
        else
            return JsonService::fail('解除失败');
    }

    /*
     * 清除推广人
     * */
    public function empty_spread($uid=0)
    {
        if(!$uid) return JsonService::fail('缺少参数');
        $res=true;
        $spread_uid = User::where('spread_uid',$uid)->column('uid');
        if(count($spread_uid)) $res = $res && false !== User::where('spread_uid','in',$spread_uid)->update(['spread_uid'=>0]);
        $res = $res && false !== User::where('spread_uid',$uid)->update(['spread_uid'=>0]);
        if($res)
            return JsonService::successful('清除成功');
        else
            return JsonService::fail('清除失败');
    }
    /**
     * 个人资金详情页面
     * @return mixed
     */
    public function now_money($uid = ''){
        if($uid == '') return $this->failed('参数错误');
        $list = UserBill::where('uid',$uid)->where('category','now_money')
            ->field('mark,pm,number,add_time')
            ->where('status',1)->order('add_time DESC')->select()->toArray();
        foreach ($list as &$v){
            $v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

}
