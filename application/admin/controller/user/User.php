<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */
namespace app\admin\controller\user;

use app\admin\controller\AuthController;
use app\admin\model\system\SystemUserLevel;
use service\FormBuilder as Form;
use service\JsonService;
use think\Db;
use traits\CurdControllerTrait;
use service\UtilService as Util;
use service\JsonService as Json;
use think\Request;
use think\Url;
use app\admin\model\user\User as UserModel;
use app\admin\model\user\UserBill AS UserBillAdmin;
use basic\ModelBasic;
use service\HookService;
use app\admin\model\user\UserLevel;
use behavior\user\UserBehavior;
use app\admin\model\store\StoreVisit;
use app\admin\model\wechat\WechatMessage;
use app\admin\model\order\StoreOrder;
use app\admin\model\store\StoreCouponUser;

/**
 * 用户管理控制器
 * Class User
 * @package app\admin\controller\user
 */
class User extends AuthController
{
    use CurdControllerTrait;
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(){
        $this->assign('count_user',UserModel::getcount());
        return $this->fetch();
    }

    /*
     * 赠送会员等级
     * @paran int $uid
     * */
    public function give_level($uid=0)
    {
        if(!$uid) return $this->failed('缺少参数');
        $level=\app\core\model\user\UserLevel::getUserLevel($uid);
        //获取当前会员等级
        if($level===false)
            $grade=0;
        else
            $grade=\app\core\model\user\UserLevel::getUserLevelInfo($level,'grade');
        //查询高于当前会员的所有会员等级
        $systemLevelList=SystemUserLevel::where('grade','>',$grade)->where(['is_show'=>1,'is_del'=>0])->field(['name','id'])->select();
        $field[]=Form::select('level_id','会员等级')->setOptions(function() use($systemLevelList) {
            $menus=[];
            foreach ($systemLevelList as $menu){
                $menus[] = ['value'=>$menu['id'],'label'=>$menu['name']];
            }
            return $menus;
        })->filterable(1);
        $form = Form::make_post_form('赠送会员',$field,Url::build('save_give_level',['uid'=>$uid]),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /*
     * 赠送会员等级
     * @paran int $uid
     * @return json
     * */
    public function save_give_level($uid=0)
    {
        if(!$uid) return JsonService::fail('缺少参数');
        list($level_id)=Util::postMore([
            ['level_id',0],
        ],$this->request,true);
        //查询当前选择的会员等级
        $systemLevel=SystemUserLevel::where(['is_show'=>1,'is_del'=>0,'id'=>$level_id])->find();
        if(!$systemLevel) return JsonService::fail('您选择赠送的会员等级不存在！');
        //检查是否拥有此会员等级
        $level=UserLevel::where(['uid'=>$uid,'level_id'=>$level_id,'is_del'=>0])->field('valid_time,is_forever')->find();
        if($level) if(!$level['is_forever'] && time() < $level['valid_time']) return JsonService::fail('此用户已有该会员等级，无法再次赠送');
        //设置会员过期时间
        $add_valid_time=(int)$systemLevel->valid_date*86400;
        UserModel::commitTrans();
        try{
            //保存会员信息
            $res=UserLevel::set([
                'is_forever'=>$systemLevel->is_forever,
                'status'=>1,
                'is_del'=>0,
                'grade'=>$systemLevel->grade,
                'uid'=>$uid,
                'add_time'=>time(),
                'level_id'=>$level_id,
                'discount'=>$systemLevel->discount,
                'valid_time'=>$systemLevel->discount ? $add_valid_time + time() : 0,
                'mark'=>'尊敬的用户【'.UserModel::where('uid',$uid)->value('nickname').'】在'.date('Y-m-d H:i:s',time()).'赠送会员等级成为'.$systemLevel['name'].'会员',
            ]);
            //提取等级任务并记录完成情况
            $levelIds=[$level_id];
            $lowGradeLevelIds=SystemUserLevel::where('grade','<',$systemLevel->grade)->where(['is_show'=>1,'is_del'=>0])->column('id');
            if(count($lowGradeLevelIds)) $levelIds=array_merge($levelIds,$lowGradeLevelIds);
            $taskIds=Db::name('system_user_task')->where('level_id','in',$levelIds)->column('id');
            $inserValue=[];
            foreach ($taskIds as $id){
                $inserValue[]=['uid'=>$uid,'task_id'=>$id,'status'=>1,'add_time'=>time()];
            }
            $res=$res && Db::name('user_task_finish')->insertAll($inserValue);
            if($res){
                UserModel::commitTrans();
                return JsonService::successful('赠送成功');
            }else{
                UserModel::rollbackTrans();
                return JsonService::successful('赠送失败');
            }
        }catch (\Exception $e){
            UserModel::rollbackTrans();
            return JsonService::fail('赠送失败');
        }
    }
    /*
     * 清除会员等级
     * @param int $uid
     * @return json
     * */
    public function del_level($uid=0)
    {
        if(!$uid) return JsonService::fail('缺少参数');
        if(UserLevel::cleanUpLevel($uid))
            return JsonService::successful('清除成功');
        else
            return JsonService::fail('清除失败');
    }

    public function delete_user($uid=0)
    {
        if(!$uid) return JsonService::fail('缺少参数');
        // return JsonService::successful('删除成功');
        if(StoreOrder::where('uid',$uid)->find()) return JsonService::fail('该用户有订单无法删除');
        if(UserModel::where('uid',$uid)->delete() && db('wechat_user')->where('uid',$uid)->delete())
            return JsonService::successful('删除成功');
        else
            return JsonService::fail('删除失败');
    }
    /**
     * 修改user表状态
     *
     * @return json
     */
    public function set_status($status='',$uid=0,$is_echo=0){
        if($is_echo==0) {
            if ($status == '' || $uid == 0) return Json::fail('参数错误');
            UserModel::where(['uid' => $uid])->update(['status' => $status]);
        }else{
            $uids=Util::postMore([
                ['uids',[]]
            ]);
            UserModel::destrSyatus($uids['uids'],$status);
        }
        return Json::successful($status==0 ? '禁用成功':'解禁成功');
    }
    /**
     * 获取user表
     *
     * @return json
     */
    public function get_user_list(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['nickname',''],
            ['status',''],
            ['pay_count',''],
            ['is_promoter',''],
            ['order',''],
            ['data',''],
            ['user_type',''],
            ['country',''],
            ['province',''],
            ['city',''],
            ['user_time_type',''],
            ['user_time',''],
            ['sex',''],
        ]);
        return Json::successlayui(UserModel::getUserList($where));
    }
    /**
     * 编辑模板消息
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function edit($uid)
    {
        if(!$uid) return $this->failed('数据不存在');
        $user = UserModel::get($uid);
        if(!$user) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::input('uid','用户编号',$user->getData('uid'))->disabled(1);
        $f[] = Form::input('nickname','用户姓名',$user->getData('nickname'));
        $f[] = Form::input('name','密保姓名',$user->getData('name'));
        $f[] = Form::input('newage','密保年龄',$user->getData('newage'));
        $f[] = Form::input('birthday','密保生日',$user->getData('birthday'));
        $f[] = Form::radio('money_status','修改余额',1)->options([['value'=>1,'label'=>'增加'],['value'=>2,'label'=>'减少']]);
        $f[] = Form::number('money','余额')->min(0);
        $f[] = Form::radio('integration_status','修改佣金',1)->options([['value'=>1,'label'=>'增加'],['value'=>2,'label'=>'减少']]);
        $f[] = Form::number('integration','佣金')->min(0);
        $f[] = Form::radio('status','状态',$user->getData('status'))->options([['value'=>1,'label'=>'开启'],['value'=>0,'label'=>'锁定']]);
        $f[] = Form::radio('is_promoter','推广员',$user->getData('is_promoter'))->options([['value'=>1,'label'=>'开启'],['value'=>0,'label'=>'关闭']]);
        $form = Form::make_post_form('添加用户通知',$f,Url::build('update',array('id'=>$uid)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    public function update(Request $request, $uid)
    {
        $data = Util::postMore([
            ['money_status',0],
            ['is_promoter',1],
            ['money',0],
            ['integration_status',0],
            ['integration',0],
            ['status',0],
            'name',
            'newage',
            'birthday',
        ],$request);
        if(!$uid) return $this->failed('数据不存在');
        $user = UserModel::get($uid);
        if(!$user) return Json::fail('数据不存在!');
        ModelBasic::beginTrans();
        $res1 = false;
        $res2 = false;
        $edit = array();
        if($data['money_status'] && $data['money']){//余额增加或者减少
           if($data['money_status'] == 1){//增加
               $edit['now_money'] = bcadd($user['now_money'],$data['money'],2);
               $res1 = UserBillAdmin::income('系统增加余额',$user['uid'],'now_money','system_add',$data['money'],$this->adminId,$edit['now_money'],'系统增加了'.floatval($data['money']).'余额','','',1);
               try{
                   HookService::listen('admin_add_money',$user,$data['money'],false,UserBehavior::class);
               }catch (\Exception $e){
                   ModelBasic::rollbackTrans();
                   return Json::fail($e->getMessage());
               }
           }else if($data['money_status'] == 2){//减少
               $edit['now_money'] = bcsub($user['now_money'],$data['money'],2);
               $res1 = UserBillAdmin::expend('系统减少余额',$user['uid'],'now_money','system_sub',$data['money'],$this->adminId,$edit['now_money'],'系统扣除了'.floatval($data['money']).'余额','','',1);
               try{
                   HookService::listen('admin_sub_money',$user,$data['money'],false,UserBehavior::class);
               }catch (\Exception $e){
                   ModelBasic::rollbackTrans();
                   return Json::fail($e->getMessage());
               }
           }
        }else{
            $res1 = true;
        }
        if($data['integration_status'] && $data['integration']){//佣金增加或者减少
            if($data['integration_status'] == 1){//增加
                $edit['integral'] = bcadd($user['integral'],$data['integration'],2);
                $res2 = UserBillAdmin::income('系统增加佣金',$user['uid'],'integral','system_add',$data['integration'],$this->adminId,$edit['integral'],'系统增加了'.floatval($data['integration']).'佣金','','',1);
                try{
                    HookService::listen('admin_add_integral',$user,$data['integration'],false,UserBehavior::class);
                }catch (\Exception $e){
                    ModelBasic::rollbackTrans();
                    return Json::fail($e->getMessage());
                }
            }else if($data['integration_status'] == 2){//减少
                $edit['integral'] = bcsub($user['integral'],$data['integration'],2);
                $res2 = UserBillAdmin::expend('系统减少佣金',$user['uid'],'integral','system_sub',$data['integration'],$this->adminId,$edit['integral'],'系统扣除了'.floatval($data['integration']).'佣金','','',1);
                try{
                    HookService::listen('admin_sub_integral',$user,$data['integration'],false,UserBehavior::class);
                }catch (\Exception $e){
                    ModelBasic::rollbackTrans();
                    return Json::fail($e->getMessage());
                }
            }
        }else{
            $res2 = true;
        }
        $edit['status'] = $data['status'];
        $edit['newage'] = $data['newage'];
        $edit['name'] = $data['name'];
        $edit['birthday'] = $data['birthday'];
        $edit['is_promoter'] = $data['is_promoter'];
        if($edit) $res3 = UserModel::edit($edit,$uid);
        else $res3 = true;
        if($res1 && $res2 && $res3) $res =true;
        else $res = false;
        ModelBasic::checkTrans($res);
        if($res) return Json::successful('修改成功!');
        else return Json::fail('修改失败');
    }
    /**
     * 用户图表
     * @return mixed
     */
    public function user_analysis(){
        $where = Util::getMore([
            ['nickname',''],
            ['status',''],
            ['is_promoter',''],
            ['date',''],
            ['user_type',''],
            ['export',0]
        ],$this->request);
        $user_count=UserModel::consume($where,'',true);
        //头部信息
        $header=[
            [
                'name'=>'新增用户',
                'class'=>'fa-line-chart',
                'value'=>$user_count,
                'color'=>'red'
            ],
            [
                'name'=>'用户留存',
                'class'=>'fa-area-chart',
                'value'=>$this->gethreaderValue(UserModel::consume($where,'',true),$where).'%',
                'color'=>'lazur'
            ],
            [
                'name'=>'新增用户总消费',
                'class'=>'fa-bar-chart',
                'value'=>'￥'.UserModel::consume($where),
                'color'=>'navy'
            ],
            [
                'name'=>'用户活跃度',
                'class'=>'fa-pie-chart',
                'value'=>$this->gethreaderValue(UserModel::consume($where,'',true)).'%',
                'color'=>'yellow'
            ],
        ];
        $name=['新增用户','用户消费'];
        $dates=$this->get_user_index($where,$name);
        $user_index=['name'=>json_encode($name), 'date'=>json_encode($dates['time']), 'series'=>json_encode($dates['series'])];
        //用户浏览分析
        $view=StoreVisit::getVisit($where['date'],['','warning','info','danger']);
        $view_v1=WechatMessage::getViweList($where['date'],['','warning','info','danger']);
        $view=array_merge($view,$view_v1);
        $view_v2=[];
        foreach ($view as $val){
            $view_v2['color'][]='#'.rand(100000,339899);
            $view_v2['name'][]=$val['name'];
            $view_v2['value'][]=$val['value'];
        }
        $view=$view_v2;
        //消费会员排行用户分析
        $user_null=UserModel::getUserSpend($where['date']);
        //消费数据
        $now_number=UserModel::getUserSpend($where['date'],true);
        list($paren_number,$title)=UserModel::getPostNumber($where['date']);
        if($paren_number==0) {
            $rightTitle=[
                'number'=>$now_number>0?$now_number:0,
                'icon'=>'fa-level-up',
                'title'=>$title
            ];
        }else{
            $number=(float)bcsub($now_number,$paren_number,4);
            if($now_number==0){
                $icon='fa-level-down';
            }else{
                $icon=$now_number>$paren_number?'fa-level-up':'fa-level-down';
            }
            $rightTitle=['number'=>$number, 'icon'=>$icon, 'title'=>$title];
        }
        unset($title,$paren_number,$now_number);
        list($paren_user_count,$title)=UserModel::getPostNumber($where['date'],true,'add_time','');
        if($paren_user_count==0){
            $count=$user_count==0?0:$user_count;
            $icon=$user_count==0?'fa-level-down':'fa-level-up';
        }else{
            $count=(float)bcsub($user_count,$paren_user_count,4);
            $icon=$user_count<$paren_user_count?'fa-level-down':'fa-level-up';
        }
        $leftTitle=[
            'count'=>$count,
            'icon'=>$icon,
            'title'=>$title
        ];
        unset($count,$icon,$title);
        $consume=[
            'title'=>'消费金额为￥'.UserModel::consume($where),
            'series'=>UserModel::consume($where,'xiaofei'),
            'rightTitle'=>$rightTitle,
            'leftTitle'=>$leftTitle,
        ];
        $form=UserModel::consume($where,'form');
        $grouping=UserModel::consume($where,'grouping');
        $this->assign(compact('header','user_index','view','user_null','consume','form','grouping','where'));
        return $this->fetch();
    }
    public function gethreaderValue($chart,$where=[]){
        if($where){
            switch($where['date']){
                case null:case 'today':case 'week':case 'year':
                if($where['date']==null){
                    $where['date']='month';
                }
                $sum_user=UserModel::whereTime('add_time',$where['date'])->count();
                if($sum_user==0) return 0;
                $counts=bcdiv($chart,$sum_user,4)*100;
                return $counts;
                break;
                case 'quarter':
                    $quarter=UserModel::getMonth('n');
                    $quarter[0]=strtotime($quarter[0]);
                    $quarter[1]=strtotime($quarter[1]);
                    $sum_user=UserModel::where('add_time','between',$quarter)->count();
                    if($sum_user==0) return 0;
                    $counts=bcdiv($chart,$sum_user,4)*100;
                    return $counts;
                default:
                    //自定义时间
                    $quarter=explode('-',$where['date']);
                    $quarter[0]=strtotime($quarter[0]);
                    $quarter[1]=strtotime($quarter[1]);
                    $sum_user=UserModel::where('add_time','between',$quarter)->count();
                    if($sum_user==0) return 0;
                    $counts=bcdiv($chart,$sum_user,4)*100;
                    return $counts;
                    break;
            }
        }else{
            $num=UserModel::count();
            $chart=$num!=0?bcdiv($chart,$num,5)*100:0;
            return $chart;
        }
    }
    public function get_user_index($where,$name){
        switch ($where['date']){
            case null:
                $days = date("t",strtotime(date('Y-m',time())));
                $dates=[];
                $series=[];
                $times_list=[];
                foreach ($name as $key=>$val){
                    for($i=1;$i<=$days;$i++){
                        if(!in_array($i.'号',$times_list)){
                            array_push($times_list,$i.'号');
                        }
                        $time=$this->gettime(date("Y-m",time()).'-'.$i);
                        if($key==0){
                            $dates['data'][]=UserModel::where('add_time','between',$time)->count();
                        }else if($key==1){
                            $dates['data'][]=UserModel::consume(true,$time);
                        }
                    }
                    $dates['name']=$val;
                    $dates['type']='line';
                    $series[]=$dates;
                    unset($dates);
                }
                return ['time'=>$times_list,'series'=>$series];
            case 'today':
                $dates=[];
                $series=[];
                $times_list=[];
                foreach ($name as $key=>$val){
                    for($i=0;$i<=24;$i++){
                        $strtitle=$i.'点';
                        if(!in_array($strtitle,$times_list)){
                            array_push($times_list,$strtitle);
                        }
                        $time=$this->gettime(date("Y-m-d ",time()).$i);
                        if($key==0){
                            $dates['data'][]=UserModel::where('add_time','between',$time)->count();
                        }else if($key==1){
                            $dates['data'][]=UserModel::consume(true,$time);
                        }
                    }
                    $dates['name']=$val;
                    $dates['type']='line';
                    $series[]=$dates;
                    unset($dates);
                }
                return ['time'=>$times_list,'series'=>$series];
            case "week":
                $dates=[];
                $series=[];
                $times_list=[];
                foreach ($name as $key=>$val){
                    for($i=0;$i<=6;$i++){
                        if(!in_array('星期'.($i+1),$times_list)){
                            array_push($times_list,'星期'.($i+1));
                        }
                        $time=UserModel::getMonth('h',$i);
                        if($key==0){
                            $dates['data'][]=UserModel::where('add_time','between',[strtotime($time[0]),strtotime($time[1])])->count();
                        }else if($key==1){
                            $dates['data'][]=UserModel::consume(true,[strtotime($time[0]),strtotime($time[1])]);
                        }
                    }
                    $dates['name']=$val;
                    $dates['type']='line';
                    $series[]=$dates;
                    unset($dates);
                }
                return ['time'=>$times_list,'series'=>$series];
            case 'year':
                $dates=[];
                $series=[];
                $times_list=[];
                $year=date('Y');
                foreach ($name as $key=>$val){
                    for($i=1;$i<=12;$i++){
                        if(!in_array($i.'月',$times_list)){
                            array_push($times_list,$i.'月');
                        }
                        $t = strtotime($year.'-'.$i.'-01');
                        $arr= explode('/',date('Y-m-01',$t).'/'.date('Y-m-',$t).date('t',$t));
                        if($key==0){
                            $dates['data'][]=UserModel::where('add_time','between',[strtotime($arr[0]),strtotime($arr[1])])->count();
                        }else if($key==1){
                            $dates['data'][]=UserModel::consume(true,[strtotime($arr[0]),strtotime($arr[1])]);
                        }
                    }
                    $dates['name']=$val;
                    $dates['type']='line';
                    $series[]=$dates;
                    unset($dates);
                }
                return ['time'=>$times_list,'series'=>$series];
            case 'quarter':
                $dates=[];
                $series=[];
                $times_list=[];
                foreach ($name as $key=>$val){
                    for($i=1;$i<=4;$i++){
                        $arr=$this->gettime('quarter',$i);
                        if(!in_array(implode('--',$arr).'季度',$times_list)){
                            array_push($times_list,implode('--',$arr).'季度');
                        }
                        if($key==0){
                            $dates['data'][]=UserModel::where('add_time','between',[strtotime($arr[0]),strtotime($arr[1])])->count();
                        }else if($key==1){
                            $dates['data'][]=UserModel::consume(true,[strtotime($arr[0]),strtotime($arr[1])]);
                        }
                    }
                    $dates['name']=$val;
                    $dates['type']='line';
                    $series[]=$dates;
                    unset($dates);
                }
                return ['time'=>$times_list,'series'=>$series];
            default:
                $list=UserModel::consume($where,'default');
                $dates=[];
                $series=[];
                $times_list=[];
                foreach ($name as $k=>$v){
                    foreach ($list as $val){
                        $date=$val['add_time'];
                        if(!in_array($date,$times_list)){
                            array_push($times_list,$date);
                        }
                        if($k==0){
                            $dates['data'][]=$val['num'];
                        }else if($k==1){
                            $dates['data'][]=UserBillAdmin::where(['uid'=>$val['uid'],'type'=>'pay_product'])->sum('number');
                        }
                    }
                    $dates['name']=$v;
                    $dates['type']='line';
                    $series[]=$dates;
                    unset($dates);
                }
                return ['time'=>$times_list,'series'=>$series];
        }
    }
    public function gettime($time='',$season=''){
        if(!empty($time) && empty($season)){
            $timestamp0 = strtotime($time);
            $timestamp24 =strtotime($time)+86400;
            return [$timestamp0,$timestamp24];
        }else if(!empty($time) && !empty($season)){
            $firstday=date('Y-m-01',mktime(0,0,0,($season - 1) *3 +1,1,date('Y')));
            $lastday=date('Y-m-t',mktime(0,0,0,$season * 3,1,date('Y')));
            return [$firstday,$lastday];
        }
    }

    /**
     * 会员等级首页
     */
    public function group(){
        return $this->fetch();
    }
    /**
     * 会员详情
     */
    public function see($uid=''){
        $this->assign([
            'uid'=>$uid,
            'userinfo'=>UserModel::getUserDetailed($uid),
            'is_layui'=>true,
            'headerList'=>UserModel::getHeaderList($uid),
            'count'=>UserModel::getCountInfo($uid),
        ]);
        return $this->fetch();
    }
    /*
     * 获取某个用户的推广下线
     * */
    public function getSpreadList($uid,$page=1,$limit=20){
        return Json::successful(UserModel::getSpreadList($uid,(int)$page,(int)$limit));
    }
    /**
     * 获取某用户的订单列表
     */
    public function getOneorderList($uid,$page=1,$limit=20){
        return Json::successful(StoreOrder::getOneorderList(compact('uid','page','limit')));
    }
    /**
     * 获取某用户的佣金列表
     */
    public function getOneIntegralList($uid,$page=1,$limit=20){
        return Json::successful(UserBillAdmin::getOneIntegralList(compact('uid','page','limit')));
    }
    /**
     * 获取某用户的积分列表
     */
    public function getOneSignList($uid,$page=1,$limit=20){
        return Json::successful(UserBillAdmin::getOneSignList(compact('uid','page','limit')));
    }
    /**
     * 获取某用户的持有优惠劵
     */
    public function getOneCouponsList($uid,$page=1,$limit=20){
        return Json::successful(StoreCouponUser::getOneCouponsList(compact('uid','page','limit')));
    }
    /**
     * 获取某用户的余额变动记录
     */
    public function getOneBalanceChangList($uid,$page=1,$limit=20){
        return Json::successful(UserBillAdmin::getOneBalanceChangList(compact('uid','page','limit')));
    }
}
