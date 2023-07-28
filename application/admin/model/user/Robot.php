<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */
namespace app\admin\model\user;
use app\admin\model\order\StoreOrder;
use app\admin\model\system\SystemUserLevel;
use traits\ModelTrait;
use app\admin\model\user\UserBill;
use basic\ModelBasic;
use app\admin\model\wechat\WechatUser;
use app\admin\model\store\StoreCouponUser;
use app\admin\model\user\UserExtract;
use app\core\util\SystemConfigService;
use think\Db;
/**
 * 机器人管理 model
 * Class User
 * @package app\admin\model\user
 */
class Robot extends ModelBasic
{
    use ModelTrait;
    /**
     * 异步获取当前用户 信息
     * @param $where
     * @return array
     */
    public static function getUserList($where){
        $list = self::
            page((int)$where['page'],(int)$where['limit'])
            ->select()
            ->each(function ($item){
                $item['add_time']=date('Y-m-d H:i:s',$item['add_time']);
            });//->toArray();
        $count=self::count();
        return ['count'=>$count,'data'=>$list];
    }
    /**
     *  修改用户状态
     * @param $uids 用户uid
     * @param $status 修改状态
     * @return array
     */
    public static function destrSyatus($uids,$status){
        if(empty($uids) && !is_array($uids)) return false;
        if($status=='') return false;
        self::beginTrans();
        try{
            $res=self::where('uid','in',$uids)->update(['status'=>$status]);
            self::checkTrans($res);
            return true;
        }catch (\Exception $e){
            self::rollbackTrans();
            return Json::fail($e->getMessage());
        }
    }
    /*
     *  获取某季度,某年某年后的时间戳
     *
     * self::getMonth('n',1) 获取当前季度的上个季度的时间戳
     * self::getMonth('n') 获取当前季度的时间戳
     */
    public static function getMonth($time='',$ceil=0){
        if(empty($time)){
            $firstday = date("Y-m-01",time());
            $lastday = date("Y-m-d",strtotime("$firstday +1 month -1 day"));
        }else if($time=='n'){
            if($ceil!=0)
                $season = ceil(date('n') /3)-$ceil;
            else
                $season = ceil(date('n') /3);
            $firstday=date('Y-m-01',mktime(0,0,0,($season - 1) *3 +1,1,date('Y')));
            $lastday=date('Y-m-t',mktime(0,0,0,$season * 3,1,date('Y')));
        }else if($time=='y'){
            $firstday=date('Y-01-01');
            $lastday=date('Y-12-31');
        }else if($time=='h'){
            $firstday = date('Y-m-d', strtotime('this week +'.$ceil.' day')) . ' 00:00:00';
            $lastday = date('Y-m-d', strtotime('this week +'.($ceil+1).' day')) . ' 23:59:59';
        }
        return array($firstday,$lastday);
    }
    public static function getcount(){
        return self::count();
    }
    /*
    *获取用户某个时间段的消费信息
    *
    * reutrn Array || number
    */
    public static function consume($where,$status='',$keep=''){
        $model = new self;
        $user_id=[];
        if(is_array($where)){
            if($where['is_promoter']!='') $model=$model->where('is_promoter',$where['is_promoter']);
            if($where['status']!='')  $model=$model->where('status',$where['status']);
            switch ($where['date']){
                case null:case 'today':case 'week':case 'year':
                if($where['date']==null){
                    $where['date']='month';
                }
                if($keep){
                    $model=$model->whereTime('add_time',$where['date'])->whereTime('last_time',$where['date']);
                }else{
                    $model=$model->whereTime('add_time',$where['date']);
                }
                break;
                case 'quarter':
                    $quarter=self::getMonth('n');
                    $startTime=strtotime($quarter[0]);
                    $endTime=strtotime($quarter[1]);
                    if($keep){
                        $model = $model->where('add_time','>',$startTime)->where('add_time','<',$endTime)->where('last_time','>',$startTime)->where('last_time','<',$endTime);
                    }else{
                        $model = $model->where('add_time','>',$startTime)->where('add_time','<',$endTime);
                    }
                    break;
                default:
                    //自定义时间
                    if(strstr($where['date'],'-')!==FALSE){
                        list($startTime,$endTime)=explode('-',$where['date']);
                        $model = $model->where('add_time','>',strtotime($startTime))->where('add_time','<',strtotime($endTime));
                    }else{
                        $model=$model->whereTime('add_time','month');
                    }
                    break;
            }
        }else{
            if(is_array($status)){
                $model=$model->where('add_time','>',$status[0])->where('add_time','<',$status[1]);
            }
        }
        if($keep===true){
            return $model->count();
        }
        if($status==='default'){
            return $model->group('from_unixtime(add_time,\'%Y-%m-%d\')')->field('count(uid) num,from_unixtime(add_time,\'%Y-%m-%d\') add_time,uid')->select()->toArray();
        }
        if($status==='grouping'){
            return $model->group('user_type')->field('user_type')->select()->toArray();
        }
        $uid=$model->field('uid')->select()->toArray();
        foreach ($uid as $val){
            $user_id[]=$val['uid'];
        }
        if(empty($user_id)){
            $user_id=[0];
        }
        if($status==='xiaofei'){
            $list=UserBill::where('uid','in',$user_id)
                ->group('type')
                ->field('sum(number) as top_number,title')
                ->select()
                ->toArray();
            $series=[
                'name'=>isset($list[0]['title'])?$list[0]['title']:'',
                'type'=>'pie',
                'radius'=> ['40%', '50%'],
                'data'=>[]
            ];
            foreach($list as $key=>$val){
                $series['data'][$key]['value']=$val['top_number'];
                $series['data'][$key]['name']=$val['title'];
            }
            return $series;
        }else if($status==='form'){
            $list=WechatUser::where('uid','in',$user_id)->group('city')->field('count(city) as top_city,city')->limit(0,10)->select()->toArray();
            $count=self::getcount();
            $option=[
                'legend_date'=>[],
                'series_date'=>[]
            ];
            foreach($list as $key=>$val){
                $num=$count!=0?(bcdiv($val['top_city'],$count,2))*100:0;
                $t=['name'=>$num.'%  '.(empty($val['city'])?'未知':$val['city']),'icon'=>'circle'];
                $option['legend_date'][$key]=$t;
                $option['series_date'][$key]=['value'=>$num,'name'=>$t['name']];
            }
            return $option;
        }else{
            $number=UserBill::where('uid','in',$user_id)->where('type','pay_product')->sum('number');
            return $number;
        }
    }
    /*
     * 获取 用户某个时间段的钱数或者TOP20排行
     *
     * return Array  || number
     */
    public static function getUserSpend($date,$status=''){
        $model=new self();
        $model=$model->alias('A');
        switch ($date){
            case null:case 'today':case 'week':case 'year':
            if($date==null) $date='month';
            $model=$model->whereTime('A.add_time',$date);
            break;
            case 'quarter':
                list($startTime,$endTime)=User::getMonth('n');
                $model = $model->where('A.add_time','>',strtotime($startTime));
                $model = $model->where('A.add_time','<',strtotime($endTime));
                break;
            default:
                list($startTime,$endTime)=explode('-',$date);
                $model = $model->where('A.add_time','>',strtotime($startTime));
                $model = $model->where('A.add_time','<',strtotime($endTime));
                break;
        }
        if($status===true){
            return $model->join('user_bill B','B.uid=A.uid')->where('B.type','pay_product')->where('B.pm',0)->sum('B.number');
        }
        $list=$model->join('user_bill B','B.uid=A.uid')
            ->where('B.type','pay_product')
            ->where('B.pm',0)
            ->field('sum(B.number) as totel_number,A.nickname,A.avatar,A.now_money,A.uid,A.add_time')
            ->order('totel_number desc')
            ->limit(0,20)
            ->select()
            ->toArray();
        if(!isset($list[0]['totel_number'])){
            $list=[];
        }
        return $list;
    }
    /*
     * 获取 相对于上月或者其他的数据
     *
     * return Array
     */
    public static function getPostNumber($date,$status=false,$field='A.add_time',$t='消费'){
        $model=new self();
        if(!$status) $model=$model->alias('A');
        switch ($date){
            case null:case 'today':case 'week':case 'year':
            if($date==null) {
                $date='last month';
                $title='相比上月用户'.$t.'增长';
            }
            if($date=='today') {
                $date='yesterday';
                $title='相比昨天用户'.$t.'增长';
            }
            if($date=='week') {
                $date='last week';
                $title='相比上周用户'.$t.'增长';
            }
            if($date=='year') {
                $date='last year';
                $title='相比去年用户'.$t.'增长';
            }
            $model=$model->whereTime($field,$date);
            break;
            case 'quarter':
                $title='相比上季度用户'.$t.'增长';
                list($startTime,$endTime)=User::getMonth('n',1);
                $model = $model->where($field,'>',$startTime);
                $model = $model->where($field,'<',$endTime);
                break;
            default:
                list($startTime,$endTime)=explode('-',$date);
                $title='相比'.$startTime.'-'.$endTime.'时间段用户'.$t.'增长';
                $Time=strtotime($endTime)-strtotime($startTime);
                $model = $model->where($field,'>',strtotime($startTime)+$Time);
                $model = $model->where($field,'<',strtotime($endTime)+$Time);
                break;
        }
        if($status){
            return [$model->count(),$title];
        }
        $number=$model->join('user_bill B','B.uid=A.uid')->where('B.type','pay_product')->where('B.pm',0)->sum('B.number');
        return [$number,$title];
    }
    //获取用户新增,头部信息
    public static function getBadgeList($where){
        $user_count=self::setWherePage(self::getModelTime($where,new self),$where,['is_promoter','status'])->count();
        $user_count_old=self::getOldDate($where)->count();
        $fenxiao=self::setWherePage(self::getModelTime($where,new self),$where,['is_promoter','status'])->where('spread_uid','<>',0)->count();
        $fenxiao_count=self::getOldDate($where)->where('spread_uid','neq',0)->count();
        $newFemxiao_count=bcsub($fenxiao,$fenxiao_count,0);
        $order_count=bcsub($user_count,$user_count_old,0);
        return [
            [
                'name'=>'会员人数',
                'field'=>'个',
                'count'=>$user_count,
                'content'=>'会员总人数',
                'background_color'=>'layui-bg-blue',
                'sum'=>self::count(),
                'class'=>'fa fa-bar-chart',
            ],
            [
                'name'=>'会员增长',
                'field'=>'个',
                'count'=>$order_count,
                'content'=>'会员增长率',
                'background_color'=>'layui-bg-cyan',
                'sum'=>$user_count_old ? bcdiv($order_count,$user_count_old,2)*100:0,
                'class'=>'fa fa-line-chart',
            ],
            [
                'name'=>'分销人数',
                'field'=>'个',
                'count'=>$fenxiao,
                'content'=>'分销总人数',
                'background_color'=>'layui-bg-green',
                'sum'=>self::where('spread_uid','neq',0)->count(),
                'class'=>'fa fa-bar-chart',
            ],
            [
                'name'=>'分销增长',
                'field'=>'个',
                'count'=>$newFemxiao_count,
                'content'=>'分销总人数',
                'background_color'=>'layui-bg-orange',
                'sum'=>$fenxiao_count ? bcdiv($newFemxiao_count,$fenxiao_count,2)*100:0,
                'class'=>'fa fa-cube',
            ],
        ];
    }
    /*
     * 获取会员增长曲线图和分布图
     *  $where 查询条件
     *  $limit 显示条数,是否有滚动条
     */
    public static function getUserChartList($where,$limit=20){
        $list=self::setWherePage(self::getModelTime($where,new self),$where,['is_promoter','status'])
            ->where('add_time','neq',0)
            ->field(['FROM_UNIXTIME(add_time,"%Y-%m-%d") as _add_time','count(uid) as num'])
            ->order('_add_time asc')
            ->group('_add_time')
            ->select();
         count($list) && $list=$list->toArray();
         $seriesdata=[];
         $xdata=[];
         $Zoom='';
         foreach ($list as $item){
             $seriesdata[]=$item['num'];
             $xdata[]=$item['_add_time'];
         }
        (count($xdata) > $limit) && $Zoom=$xdata[$limit-5];
        //多次购物会员数量饼状图
        $count=self::setWherePage(self::getModelTime($where,new self),$where,['is_promoter'])->count();
        $user_count=self::setWherePage(self::getModelTime($where,self::alias('a')->join('__STORE_ORDER__ r','r.uid=a.uid'),'a.add_time'),$where,['is_promoter'])
            ->where('r.paid',1)->count('a.uid');
        $shop_xdata=['多次购买数量占比','无购买数量占比'];
        $shop_data=[];
        $count >0 && $shop_data=[
            [
                'value'=>bcdiv($user_count,$count,2)*100,
                'name'=>$shop_xdata[0],
                'itemStyle'=>[
                    'color'=>'#D789FF',
                ]
            ],
            [
                'value'=>bcdiv($count-$user_count,$count,2)*100,
                'name'=>$shop_xdata[1],
                'itemStyle'=>[
                    'color'=>'#7EF0FB',
                ]
            ]
        ];
       return compact('shop_data','shop_xdata','fenbu_data','fenbu_xdata','seriesdata','xdata','Zoom');
    }
    //获取$date的前一天或者其他的时间段
    public static function getOldDate($where,$moedls=null){
        $model=$moedls ===null ? self::setWherePage(new self(),$where,['is_promoter','status']) :$moedls;
        switch ($where['data']){
            case 'today':
                $model=$model->whereTime('add_time','yesterday');
                break;
            case 'week':
                $model=$model->whereTime('add_time','last week');
                break;
            case 'month':
                $model=$model->whereTime('add_time','last month');
                break;
            case 'year':
                $model=$model->whereTime('add_time','last year');
                break;
            case 'quarter':
                $time=self::getMonth('n',1);
                $model=$model->where('add_time','between',$time);
                break;
        }
        return $model;
    }
    //获取用户属性和性别分布图
    public static function getEchartsData($where){
        $model=self::alias('a');
        $data=self::getModelTime($where,$model,'a.add_time')
            ->join('wechat_user r','r.uid=a.uid')
            ->group('r.province')
            ->field('count(r.province) as count,province')
            ->order('count desc')
            ->limit(15)
            ->select();
        if(count($data)) $data=$data->toArray();
        $legdata=[];
        $dataList=[];
        foreach ($data as $value){
            $value['province']=='' && $value['province']='未知省份';
            $legdata[]=$value['province'];
            $dataList[]=$value['count'];
        }
        $model=self::alias('a');
        $sex=self::getModelTime($where,$model,'a.add_time')
            ->join('wechat_user r','r.uid=a.uid')
            ->group('r.sex')
            ->field('count(r.uid) as count,sex')
            ->order('count desc')
            ->select();
        if(count($sex)) $sex=$sex->toArray();
        $sexlegdata=['男','女','未知'];
        $sexcount=self::getModelTime($where,new self())->count();
        $sexList=[];
        $color=['#FB7773','#81BCFE','#91F3FE'];
        foreach ($sex as $key=>$item){
            if($item['sex']==1){
                $item_date['name']='男';
            }else if($item['sex']==2){
                $item_date['name']='女';
            }else{
                $item_date['name']='未知性别';
            }
            $item_date['value']=bcdiv($item['count'],$sexcount,2)*100;
            $item_date['itemStyle']['color']=$color[$key];
            $sexList[]=$item_date;
        }
       return compact('sexList','sexlegdata','legdata','dataList');
    }
    //获取佣金记录列表
    public static function getCommissionList($where){
        $list=self::setCommissionWhere($where)
            ->page((int)$where['page'],(int)$where['limit'])
            ->select();
        count($list) && $list=$list->toArray();
        foreach ($list as &$value){
            $value['ex_price']= Db::name('user_extract')->where(['uid'=>$value['uid']])->sum('extract_price');
            $value['extract_price']= Db::name('user_extract')->where(['uid'=>$value['uid'],'status'=>1])->sum('extract_price');
        }
        $count=self::setCommissionWhere($where)->count();
        return ['data'=>$list,'count'=>$count];
    }
    //获取佣金记录列表的查询条件
    public static function setCommissionWhere($where){
        $models=self::setWherePage(self::alias('A'),$where,[],['A.nickname','A.uid'])
            ->join('user_bill B','B.uid=A.uid')
            ->group('A.uid')
            ->where(['B.category'=>'now_money','B.type'=>'brokerage'])
            ->field(['sum(B.number) as sum_number','A.nickname','A.uid','A.now_money']);
        if($where['order']==''){
            $models=$models->order('sum_number desc');
        }else{
            $models=$models->order($where['order']==1 ? 'sum_number desc':'sum_number asc');
        }
        if($where['price_max']!='' && $where['price_min']!=''){
            $models=$models->where('now_money','between',[$where['price_max'],$where['price_min']]);
        }
        return $models;
    }

    /**获取用户详细信息
     * @param $uid
     * @return array
     */
    public static function getUserInfos($uid)
    {
        $userInfo = self::where('uid',$uid)->find();
        if(!$userInfo) exception('读取用户信息失败!');
        return $userInfo->toArray();
    }

    //获取某人用户推广信息
    public static function getUserinfo($uid){
        $userinfo=self::where(['uid'=>$uid])->field(['nickname','spread_uid','now_money','add_time'])->find()->toArray();
        $userinfo['number']=UserBill::where(['category'=>'now_money','type'=>'brokerage'])->sum('number');
        $userinfo['spread_name']=$userinfo['spread_uid'] ? self::where(['uid'=>$userinfo['spread_uid']])->value('nickname') :'';
        return $userinfo;
    }
    //获取某用户的详细信息
    public static function getUserDetailed($uid){
        $key_field=['real_name','phone','province','city','district','detail','post_code'];
        $Address=($thisAddress= Db::name('user_address')->where(['uid'=>$uid,'is_default'=>1])->field($key_field)->find()) ?
           $thisAddress :
            Db::name('user_address')->where(['uid'=>$uid])->field($key_field)->find();
        $UserInfo=self::get($uid);
        return [
            ['col'=>12,'name'=>'默认收货地址','value'=>$thisAddress ? '收货人:'.$thisAddress['real_name'].'邮编:'.$thisAddress['post_code'].' 收货人电话:'.$thisAddress['phone'].' 地址:'.$thisAddress['province'].' '.$thisAddress['city'].' '.$thisAddress['district'].' '.$thisAddress['detail'] : ''],
//            ['name'=>'微信OpenID','value'=>WechatUser::where(['uid'=>$uid])->value('openid'),'col'=>8],
            ['name'=>'手机号码','value'=>$UserInfo['phone']],
//            ['name'=>'ID','value'=>$uid],
            ['name'=>'姓名','value'=>''],
            ['name'=>'微信昵称','value'=>$UserInfo['nickname']],
            ['name'=>'邮箱','value'=>''],
            ['name'=>'生日','value'=>''],
            ['name'=>'积分','value'=>$UserInfo['integral']],
            ['name'=>'上级推广人','value'=>$UserInfo['spread_uid'] ? self::where(['uid'=>$UserInfo['spread_uid']])->value('nickname'):''],
            ['name'=>'账户余额','value'=>$UserInfo['now_money']],
            ['name'=>'佣金总收入','value'=>UserBill::where(['category'=>'now_money','type'=>'brokerage','uid'=>$uid])->sum('number')],
            ['name'=>'提现总金额','value'=> Db::name('user_extract')->where(['uid'=>$uid,'status'=>1])->sum('extract_price')],
        ];
    }
    //获取某用户的订单个数,消费明细
    public static function getHeaderList($uid){
        return [
            [
                'title'=>'总计订单',
                'value'=>StoreOrder::where(['uid'=>$uid])->count(),
                'key'=>'笔',
                'class'=>'',
            ],
            [
                'title'=>'总消费金额',
                'value'=>StoreOrder::where(['uid'=>$uid,'paid'=>1])->sum('total_price'),
                'key'=>'元',
                'class'=>'',
            ],
            [
                'title'=>'本月订单',
                'value'=>StoreOrder::where(['uid'=>$uid])->whereTime('add_time','month')->count(),
                'key'=>'笔',
                'class'=>'',
            ],
            [
                'title'=>'本月消费金额',
                'value'=>StoreOrder::where(['uid'=>$uid])->whereTime('add_time','month')->sum('total_price'),
                'key'=>'元',
                'class'=>'',
            ]
        ];
    }
    /*
     * 获取 会员 订单个数,积分明细,优惠劵明细
     *
     * $uid 用户id;
     *
     * return array
     */
    public static function getCountInfo($uid){
        $order_count=StoreOrder::where(['uid'=>$uid])->count();
        $integral_count=UserBill::where(['uid'=>$uid,'category'=>'integral'])->where('type','in',['deduction','system_add'])->count();
        $sign_count=UserBill::where(['uid'=>$uid,'category'=>'integral','type'=>'sign'])->count();
        $balanceChang_count=UserBill::where(['uid'=>$uid,'category'=>'now_money'])
            ->where('type','in',['system_add','pay_product','extract','pay_product_refund','system_sub'])
            ->count();
        $coupon_count=StoreCouponUser::where(['uid'=>$uid])->count();
        $spread_count=self::where(['spread_uid'=>$uid])->count();
        return compact('order_count','integral_count','sign_count','balanceChang_count','coupon_count','spread_count');
    }
    /*
     * 获取 会员业务的
     * 购物会员统计
     *  会员访问量
     *
     * 曲线图
     *
     * $where 查询条件
     *
     * return array
     */
    public static function getUserBusinessChart($where,$limit=20){
        //获取购物会员人数趋势图
        $list=self::getModelTime($where,self::where('a.status',1)->alias('a')->join('__STORE_ORDER__ r','r.uid=a.uid'),'a.add_time')
            ->where(['r.paid'=>1,'a.is_promoter'=>0])
            ->where('a.add_time','neq',0)
            ->field(['FROM_UNIXTIME(a.add_time,"%Y-%m-%d") as _add_time','count(r.uid) as count_user'])
            ->group('_add_time')
            ->order('_add_time asc')
            ->select();
        count($list) && $list=$list->toArray();
        $seriesdata=[];
        $xdata=[];
        $zoom='';
        foreach ($list as $item){
            $seriesdata[]=$item['count_user'];
            $xdata[]=$item['_add_time'];
        }
        count($xdata) > $limit && $zoom=$xdata[$limit-5];
        //会员访问量
        $visit=self::getModelTime($where,self::alias('a')->join('__STORE_VISIT__ t','t.uid=a.uid'),'t.add_time')
            ->where('a.is_promoter',0)
            ->field(['FROM_UNIXTIME(t.add_time,"%Y-%m-%d") as _add_time','count(t.uid) as count_user'])
            ->group('_add_time')
            ->order('_add_time asc')
            ->select();
        count($visit) && $visit=$visit->toArray();
        $visit_data=[];
        $visit_xdata=[];
        $visit_zoom='';
        foreach ($visit as $item){
            $visit_data[]=$item['count_user'];
            $visit_xdata[]=$item['_add_time'];
        }
        count($visit_xdata) > $limit && $visit_zoom=$visit_xdata[$limit-5];
        //多次购物会员数量饼状图
        $count=self::getModelTime($where,self::where('is_promoter',0))->count();
        $user_count=self::getModelTime($where,self::alias('a')->join('__STORE_ORDER__ r','r.uid=a.uid'),'a.add_time')
            ->where('a.is_promoter',0)
            ->where('r.paid',1)
            ->group('a.uid')
            ->count();
        $shop_xdata=['多次购买数量占比','无购买数量占比'];
        $shop_data=[];
        $count >0 && $shop_data=[
            [
                'value'=>bcdiv($user_count,$count,2)*100,
                'name'=>$shop_xdata[0],
                'itemStyle'=>[
                    'color'=>'#D789FF',
                ]
            ],
            [
                'value'=>bcdiv($count-$user_count,$count,2)*100,
                'name'=>$shop_xdata[1],
                'itemStyle'=>[
                    'color'=>'#7EF0FB',
                ]
            ]
        ];
        return compact('seriesdata','xdata','zoom','visit_data','visit_xdata','visit_zoom','shop_data','shop_xdata');
    }
    /*
     * 获取用户
     * 积分排行
     * 会员余额排行榜
     * 分销商佣金总额排行榜
     * 购物笔数排行榜
     * 购物金额排行榜
     * 分销商佣金提现排行榜
     * 上月消费排行榜
     * $limit 查询多少条
     * return array
     */
    public static function getUserTop10List($limit=10,$is_promoter=0){
        //积分排行
        $integral=self::where('status',1)
            ->where('is_promoter',$is_promoter)
            ->order('integral desc')
            ->field(['nickname','phone','integral','FROM_UNIXTIME(add_time,"%Y-%m-%d") as add_time'])
            ->limit($limit)
            ->select();
        count($integral) && $integral=$integral->toArray();
        //会员余额排行榜
        $now_money=self::where('status',1)
            ->where('is_promoter',$is_promoter)
            ->order('now_money desc')
            ->field(['nickname','phone','now_money','FROM_UNIXTIME(add_time,"%Y-%m-%d") as add_time'])
            ->limit($limit)
            ->select();
        count($now_money) && $now_money=$now_money->toArray();
        //购物笔数排行榜
        $orderPayCount = StoreOrder::getOrderPayCount($is_promoter);
        if($orderPayCount){
            $shopcount=self::alias('a')
                ->join('__STORE_ORDER__ r','r.uid=a.uid')
                ->where(['r.paid'=>1,'a.is_promoter'=>$is_promoter])
                ->group('r.uid')
                ->field(['a.nickname','a.phone','count(r.uid) as sum_count','FROM_UNIXTIME(a.add_time,"%Y-%m-%d") as add_time'])
                ->order('sum_count desc')
                ->limit($limit)
                ->select();
        }else $shopcount = [];
        count($shopcount) && $shopcount=$shopcount->toArray();
        //购物金额排行榜
        if($orderPayCount){
            $order=self::alias('a')
                ->join('__STORE_ORDER__ r','r.uid=a.uid')
                ->where(['r.paid'=>1,'a.is_promoter'=>$is_promoter])
                ->group('r.uid')
                ->field(['a.nickname','a.phone','sum(r.pay_price) as sum_price','FROM_UNIXTIME(a.add_time,"%Y-%m-%d") as add_time','r.uid'])
                ->order('sum_price desc')
                ->limit($limit)
                ->select();
        }else $order = [];
        count($order) && $order=$order->toArray();
        //上月消费排行
        $orderPayCount = StoreOrder::getOrderPayMonthCount($is_promoter);
        if($orderPayCount){
            $lastorder=self::alias('a')
                ->join('__STORE_ORDER__ r','r.uid=a.uid')
                ->where(['r.paid'=>1,'a.is_promoter'=>$is_promoter])
//                ->whereTime('r.pay_time','last month')
                ->where('r.pay_time','between',[strtotime(date('Y-m',strtotime('-1 month'))),strtotime(date('Y-m'))])
                ->group('r.uid')
                ->field(['a.nickname','a.phone','sum(r.pay_price) as sum_price','FROM_UNIXTIME(a.add_time,"%Y-%m-%d") as add_time','r.uid'])
                ->order('sum_price desc')
                ->limit($limit)
                ->select();
        }else $lastorder = [];
        count($lastorder) && $lastorder=$lastorder->toArray();
        return compact('integral','now_money','shopcount','order','lastorder');
    }
    /*
     * 获取 会员业务
     * 会员总余额 会员总积分
     * $where 查询条件
     *
     * return array
     */
    public static function getUserBusinesHeade($where){
        return [
            [
                'name'=>'会员总余额',
                'field'=>'元',
                'count'=>self::getModelTime($where,self::where('status',1))->sum('now_money'),
                'background_color'=>'layui-bg-cyan',
                'col'=>6,
            ],
            [
                'name'=>'会员总积分',
                'field'=>'分',
                'count'=>self::getModelTime($where,self::where('status',1))->sum('integral'),
                'background_color'=>'layui-bg-cyan',
                'col'=>6
            ]
        ];
    }
    /*
     * 分销会员头部信息查询获取
     *
     * 分销商总佣金余额
     * 分销商总提现佣金
     * 本月分销商业务佣金
     * 本月分销商佣金提现金额
     * 上月分销商业务佣金
     * 上月分销商佣金提现金额
     * $where array 时间条件
     *
     * return array
     */
    public static function getDistributionBadgeList($where){
        return [
            [
                'name'=>'分销商总佣金',
                'field'=>'元',
                'count'=>self::getModelTime($where,UserBill::where('category','now_money')->where('type','brokerage'))->where('uid','in',function($query){
                    $query->name('user')->where('status',1)->where('is_promoter',1)->whereOr('spread_uid','neq',0)->field('uid');
                })->sum('number'),
                'background_color'=>'layui-bg-cyan',
                'col'=>3,
            ],
            [
                'name'=>'分销商总佣金余额',
                'field'=>'元',
                'count'=>self::getModelTime($where,self::where('status',1)->where('is_promoter',1))->sum('now_money'),
                'background_color'=>'layui-bg-cyan',
                'col'=>3,
            ],
            [
                'name'=>'分销商总提现佣金',
                'field'=>'元',
                'count'=>self::getModelTime($where,UserExtract::where('status',1))->sum('extract_price'),
                'background_color'=>'layui-bg-cyan',
                'col'=>3,
            ],
            [
                'name'=>'本月分销商业务佣金',
                'field'=>'元',
                'count'=>self::getModelTime(['data'=>'month'],UserBill::where('category','now_money')->where('type','brokerage'))
                    ->where('uid','in',function ($query){
                        $query->name('user')->where('status',1)->where('is_promoter',1)->whereOr('spread_uid','neq',0)->field('uid');
                    })->sum('number'),
                'background_color'=>'layui-bg-cyan',
                'col'=>3,
            ],
            [
                'name'=>'本月分销商佣金提现金额',
                'field'=>'元',
                'count'=>self::getModelTime(['data'=>'month'],UserExtract::where('status',1))
                    ->where('uid','in',function ($query){
                        $query->name('user')->where('status',1)->where('is_promoter',1)->field('uid');
                    })->sum('extract_price'),
                'background_color'=>'layui-bg-cyan',
                'col'=>4,
            ],
            [
                'name'=>'上月分销商业务佣金',
                'field'=>'元',
                'count'=>self::getOldDate(['data'=>'year'],UserBill::where('category','now_money')->where('uid','in',function ($query){
                    $query->name('user')->where('status',1)->where('is_promoter',1)->whereOr('spread_uid','neq',0)->field('uid');
                })->where('type','brokerage'))->sum('number'),
                'background_color'=>'layui-bg-cyan',
                'col'=>4,
            ],
            [
                'name'=>'上月分销商佣金提现金额',
                'field'=>'元',
                'count'=>self::getOldDate(['data'=>'year'],UserBill::where('category','now_money')->where('uid','in',function ($query){
                    $query->name('user')->where('status',1)->where('is_promoter',1)->whereOr('spread_uid','neq',0)->field('uid');
                })->where('type','brokerage'))->sum('number'),
                'background_color'=>'layui-bg-cyan',
                'col'=>4,
            ],
        ];
    }
    /*
     * 分销会员
     * 分销数量 饼状图
     * 分销商会员访问量 曲线
     * 获取购物会员人数趋势图 曲线
     * 多次购物分销会员数量 饼状图
     * $where array 条件
     * $limit int n条数据后出拖动条
     * return array
     */
    public static function getUserDistributionChart($where,$limit=20){
        //分销数量
        $fenbu_user=self::getModelTime($where,new self)->field(['count(uid) as num'])->group('is_promoter')->select();
        count($fenbu_user) && $fenbu_user=$fenbu_user->toArray();
        $sum_user=0;
        $fenbu_data=[];
        $fenbu_xdata=['分销商','非分销商'];
        $color=['#81BCFE','#91F3FE'];
        foreach($fenbu_user as $item){
            $sum_user+=$item['num'];
        }
        foreach ($fenbu_user as $key=>$item){
            $value['value']=bcdiv($item['num'],$sum_user,2)*100;
            $value['name']=isset($fenbu_xdata[$key]) ?$fenbu_xdata[$key].'  %'.$value['value']  :'';
            $value['itemStyle']['color']=$color[$key];
            $fenbu_data[]=$value;
        }
        //分销商会员访问量
        $visit=self::getModelTime($where,self::alias('a')->join('store_visit t','t.uid=a.uid'),'t.add_time')
            ->where('a.is_promoter',1)
            ->field(['FROM_UNIXTIME(t.add_time,"%Y-%m-%d") as _add_time','count(t.uid) as count_user'])
            ->group('_add_time')
            ->order('_add_time asc')
            ->select();
//        echo self::getLastSql();die;
        count($visit) && $visit=$visit->toArray();
        $visit_data=[];
        $visit_xdata=[];
        $visit_zoom='';
        foreach ($visit as $item){
            $visit_data[]=$item['count_user'];
            $visit_xdata[]=$item['_add_time'];
        }
        count($visit_xdata) > $limit && $visit_zoom=$visit_xdata[$limit-5];
        //获取购物会员人数趋势图
        $list=self::getModelTime($where,self::where('a.status',1)->alias('a')->join('__STORE_ORDER__ r','r.uid=a.uid'),'a.add_time')
            ->where(['r.paid'=>1,'a.is_promoter'=>1])
            ->where('a.add_time','neq',0)
            ->field(['FROM_UNIXTIME(a.add_time,"%Y-%m-%d") as _add_time','count(r.uid) as count_user'])
            ->group('_add_time')
            ->order('_add_time asc')
            ->select();
        count($list) && $list=$list->toArray();
        $seriesdata=[];
        $xdata=[];
        $zoom='';
        foreach ($list as $item){
            $seriesdata[]=$item['count_user'];
            $xdata[]=$item['_add_time'];
        }
        count($xdata) > $limit && $zoom=$xdata[$limit-5];
        //多次购物分销会员数量饼状图
        $count=self::getModelTime($where,self::where('is_promoter',1))->count();
        $user_count=self::getModelTime($where,self::alias('a')
            ->join('__STORE_ORDER__ r','r.uid=a.uid'),'a.add_time')
            ->where('a.is_promoter',1)
            ->where('r.paid',1)
            ->group('a.uid')
            ->count();
        $shop_xdata=['多次购买数量占比','无购买数量占比'];
        $shop_data=[];
        $count >0 && $shop_data=[
            [
                'value'=>bcdiv($user_count,$count,2)*100,
                'name'=>$shop_xdata[0].$user_count.'人',
                'itemStyle'=>[
                    'color'=>'#D789FF',
                ]
            ],
            [
                'value'=>bcdiv($count-$user_count,$count,2)*100,
                'name'=>$shop_xdata[1].($count-$user_count).'人',
                'itemStyle'=>[
                    'color'=>'#7EF0FB',
                ]
            ]
        ];
        return compact('fenbu_data','fenbu_xdata','visit_data','visit_xdata','visit_zoom','seriesdata','xdata','zoom','shop_xdata','shop_data');
    }
    /*
     * 分销商佣金提现排行榜
     * 分销商佣金总额排行榜
     * $limit 截取条数
     * return array
     */
    public static function getUserDistributionTop10List($limit){
        //分销商佣金提现排行榜
        $extract=self::alias('a')
            ->join('user_extract t','a.uid=t.uid')
            ->where(['t.status'=>1,'a.is_promoter'=>1])
            ->group('t.uid')
            ->field(['a.nickname','a.phone','sum(t.extract_price) as sum_price','FROM_UNIXTIME(a.add_time,"%Y-%m-%d") as add_time','t.uid'])
            ->order('sum_price desc')
            ->limit($limit)
            ->select();
        count($extract) && $extract=$extract->toArray();
        //分销商佣金总额排行榜
        $commission=UserBill::alias('l')
            ->join('user a','l.uid=a.uid')
            ->where(['l.status'=>'1','l.category'=>'now_money','l.type'=>'brokerage','a.is_promoter'=>1])
            ->group('l.uid')
            ->field(['a.nickname','a.phone','sum(number) as sum_number','FROM_UNIXTIME(a.add_time,"%Y-%m-%d") as add_time'])
            ->order('sum_number desc')
            ->limit($limit)
            ->select();
        count($commission) && $commission=$commission->toArray();
        return compact('extract','commission');
    }
    public static function getSpreadList($uid,$page,$limit){
        $list=self::where(['spread_uid'=>$uid])->field(['uid','nickname','now_money','integral','add_time'])
            ->order('uid desc')->page((int)$page,(int)$limit)->select();
        count($list) && $list=$list->toArray();
        foreach ($list as &$item){
            $item['add_time']=date('Y-m-d H',$item['add_time']);
        }
        return $list;
    }
    /**
     * 一级分销
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerage($orderInfo)
    {
        $userInfo = User::getUserInfo($orderInfo['uid']);
        if(!$userInfo || !$userInfo['spread_uid']) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ? : 1;//获取后台分销类型
        if($storeBrokerageStatu == 1){
            if(!User::be(['uid'=>$userInfo['spread_uid'],'is_promoter'=>1])) return true;
        }
        $brokerageRatio = (SystemConfigService::get('store_brokerage_ratio') ?: 0)/100;
        if($brokerageRatio <= 0) return true;
        $cost = isset($orderInfo['cost']) ? $orderInfo['cost'] : 0;//成本价
        if($cost > $orderInfo['pay_price']) return true;//成本价大于支付价格时直接返回
        $brokeragePrice = bcmul(bcsub($orderInfo['pay_price'],$cost,2),$brokerageRatio,2);
        //返佣之后余额
        $spNow_money = User::where(['uid'=>$userInfo['spread_uid']])->value('now_money');
        $balance = bcadd($spNow_money,$brokeragePrice,2);
        if($brokeragePrice <= 0) return true;
        $mark = $userInfo['nickname'].'成功消费'.floatval($orderInfo['pay_price']).'元,奖励推广佣金'.floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('获得推广佣金',$userInfo['spread_uid'],'now_money','brokerage',$brokeragePrice,$orderInfo['id'],$balance,$mark);
        $res2 = self::bcInc($userInfo['spread_uid'],'now_money',$brokeragePrice,'uid');
        $res = $res1 && $res2;
        self::checkTrans($res);
        if($res) self::backOrderBrokerageTwo($orderInfo);
        return $res;
    }

    /**
     * 二级推广
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerageTwo($orderInfo){
        $userInfo = User::getUserInfo($orderInfo['uid']);
        $userInfoTwo = User::getUserInfo($userInfo['spread_uid']);
        if(!$userInfoTwo || !$userInfoTwo['spread_uid']) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ? : 1;//获取后台分销类型
        if($storeBrokerageStatu == 1){
            if(!User::be(['uid'=>$userInfoTwo['spread_uid'],'is_promoter'=>1]))  return true;
        }
        $brokerageRatio = (SystemConfigService::get('store_brokerage_two') ?: 0)/100;
        if($brokerageRatio <= 0) return true;
        $cost = isset($orderInfo['cost']) ? $orderInfo['cost'] : 0;//成本价
        if($cost > $orderInfo['pay_price']) return true;//成本价大于支付价格时直接返回
        $brokeragePrice = bcmul(bcsub($orderInfo['pay_price'],$cost,2),$brokerageRatio,2);
        //返佣之后余额
        $spNow_money = User::where(['uid'=>$userInfo['spread_uid']])->value('now_money');
        $balance = bcadd($spNow_money,$brokeragePrice,2);
        if($brokeragePrice <= 0) return true;
        $mark = '二级推广人'.$userInfo['nickname'].'成功消费'.floatval($orderInfo['pay_price']).'元,奖励推广佣金'.floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('获得推广佣金',$userInfoTwo['spread_uid'],'now_money','brokerage',$brokeragePrice,$orderInfo['id'],$balance,$mark);
        $res2 = self::bcInc($userInfoTwo['spread_uid'],'now_money',$brokeragePrice,'uid');
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
    }
}