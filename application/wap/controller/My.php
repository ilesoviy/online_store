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
use app\wap\model\store\Robot;
use app\wap\model\user\User;
use app\wap\model\user\UserNum;
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
use think\Cookie;
use think\Session;

class My extends AuthController
{

    public function user_cut(){
        $list = StoreBargainUser::getBargainUserAll($this->userInfo['uid']);
        if($list){
            foreach ($list as $k=>$v){
                $list[$k]['con_price'] = bcsub($v['bargain_price'],$v['price'],2);
                $list[$k]['helpCount'] = StoreBargainUserHelp::getBargainUserHelpPeopleCount($v['bargain_id'],$this->userInfo['uid']);
            }
            $this->assign('list',$list);
        }else return $this->failed('暂无参与砍价',Url::build('My/index'));
        return $this->fetch();
    }
    public function test(){
        // die;
        // $order = StoreOrder::where('order_id','wx2021080921122810007')->find();

       // $user_num=UserNum::where('user_id',$order['uid'])->whereTime('add_time', 'today')->find();
       //  if($user_num){
       //      UserNum::where('user_id',$order['uid'])->whereTime('add_time', 'today')->update(['user_num' => $user_num['user_num']-1]);
       //  }else{
       //      $data=[
       //          'user_id'=>$order['uid'],
       //          'user_num'=>19,
       //          'add_time'=>time()
       //      ];
       //      UserNum::insert($data);
       //      UserNum::income('初始化',$order['uid'],20,20,'用户初始化次数');
       //  }
       //   UserNum::expend('拼团购物',$order['uid'],1,$user_num['user_num']-1,'用户拼团购物');
       // Robot::JoinRobot('wx2021080217053610002');

        // Robot::Probability('wx2021080217053610002');
        // $orderinfo=StoreOrder::where('order_id','wx2021080510431510026')->find();
        // $res=Robot::backOrderBrokerage($orderinfo);
         // dump($res);die;
    }
    public function index()
    {
//        echo date('Y-m-d,H:i:s',1521516681);
            // dump(GroupDataService::getData('my_index_menu'));die;
      $pin_list_count['puzzle']=StoreOrder::where(['uid'=>$this->userInfo['uid']])->where('paid',1)->where('pin_type',1)->where('status',0)->where('refund_status',0)->where('pin_status',1)->count();
      $pin_list_count['success']=StoreOrder::where(['uid'=>$this->userInfo['uid']])->where('paid',1)->where('pin_type',1)->where('status',0)->where('refund_status',2)->where('pin_status',2)->count();
      $pin_list_count['fail']=StoreOrder::where(['uid'=>$this->userInfo['uid']])->where('paid',1)->where('pin_type',1)->where('status','-1')->where('refund_status',2)->where('pin_status',3)->count();
       //未到账的佣金
        $no_integral=UserBill::where(['uid'=>$this->userInfo['uid'],'category'=>'integral','status'=>0,'pm'=>1])->column('number');
        $no_integral=array_sum($no_integral);
      // dump($pin_list_count);die;
        $this->assign([
            'pin_list_count'=>$pin_list_count,
            'menus'=>GroupDataService::getData('my_index_menu')?:[],
            'orderStatusNum'=>StoreOrder::getOrderStatusNum($this->userInfo['uid']),
            'notice'=>UserNotice::getNotice($this->userInfo['uid']),
            'statu' =>(int)SystemConfig::getValue('store_brokerage_statu'),
            'now_money'=>array_sum(UserBill::where('uid',$this->userInfo['uid'])->where('category','now_money')
        ->where('status',1)->column('number')),
            'appservice'=>GroupDataService::getData('app_customer_service')?:[],
            'no_integral'=>$no_integral
        ]);
        return $this->fetch();
    }


    public function sign_in()
    {
        $signed = UserSign::checkUserSigned($this->userInfo['uid']);
        $signCount = UserSign::userSignedCount($this->userInfo['uid']);
        $signList = UserSign::userSignBillWhere($this->userInfo['uid'])
            ->field('number,add_time,title,en_title')->order('id DESC')
            ->limit(30)->select()->toArray();
        $goodsList = StoreProduct::getNewProduct('image,price,IFNULL(sales,0) + IFNULL(ficti,0) AS sales,store_name,id','0,20')->toArray();
        //未到账的佣金
        $no_integral=UserBill::where(['uid'=>$this->userInfo['uid'],'category'=>'integral','status'=>0,'pm'=>1])->column('number');
        $no_integral=array_sum($no_integral);
        $this->assign(compact('signed','signCount','signList','goodsList','no_integral'));
        return $this->fetch();
    }

    public function coupon()
    {
        $uid = $this->userInfo['uid'];
        $couponList = StoreCouponUser::all(function($query) use($uid){
            $query->where('status','0')->where('uid',$uid)->order('is_fail ASC,status ASC,add_time DESC')->whereOr(function($query) use($uid){
                $query->where('uid',$uid)->where('status','<>',0)->where('end_time','>',time()-(7*86400));
            });
        })->toArray();
        $couponList = StoreCouponUser::tidyCouponList($couponList);
        $this->assign([
            'couponList'=>$couponList
        ]);
        return $this->fetch();
    }

    public function collect()
    {
        return $this->fetch();
    }

    public function address()
    {
        $this->assign([
            'address'=>UserAddress::getUserValidAddressList($this->userInfo['uid'],'id,real_name,phone,province,city,district,detail,is_default')
        ]);
        return $this->fetch();
    }

    public function recharge()
    {
       $this->assign([
            'recharge_name'=>SystemConfig::getValue('recharge_name'),
            'recharge_url'=>SystemConfig::getValue('recharge_url'),
            'recharge_code'=>SystemConfig::getValue('recharge_code'),
        ]);
        return $this->fetch();
    }
     /* 邀请码 */
    public function code()
    {
        // dump(db('user')->where('uid',$this->userInfo['uid'])->find());die;
         $this->assign([
            'userInfo'=>db('user')->where('uid',$this->userInfo['uid'])->find(),
            'info'=>db('article')->where('n.id',6)->alias('n')->field('n.*,c.content,c.en_content')->join('ArticleContent c','c.nid=n.id')->find(),
        ]);
        return $this->fetch('index/code');
    }

    public function edit_address($addressId = '')
    {
        if($addressId && is_numeric($addressId) && UserAddress::be(['is_del'=>0,'id'=>$addressId,'uid'=>$this->userInfo['uid']])){
            $addressInfo = UserAddress::find($addressId)->toArray();
        }else{
            $addressInfo = [];
        }
        $this->assign(compact('addressInfo'));
        return $this->fetch();
    }
    public function edit_userinfo(Request $request)
    {
        if($this->userInfo['uid']){
            $userInfo = User::where('uid',$this->userInfo['uid'])->find()->toArray();
            $wuserInfo = WechatUser::where('uid',$this->userInfo['uid'])->find()->toArray();
        }else{
            $userInfo = [];
            $wuserInfo = [];
        }
        // var_dump($wuserInfo);exit();
        $this->assign($userInfo);
        $this->assign('wuserInfo',$wuserInfo);
        list($nickname,$name,$account,$nationality,$birthday,$avatar) = UtilService::postMore(['nickname','name','account','nationality','birthday','avatar'],$request,true);
        if($account){
             $zz_u="/^[a-zA-Z\d_]{1,}$/";
            $zz_p="/^[a-zA-Z\d_]{6,}$/";
            $zz_h="/^0?(13[0-9]|15[012356789]|17[013678]|18[0-9]|14[57])[0-9]{8}$/";
          //       preg_match($zz_u, $account, $account);
               
          //       preg_match($zz_h, $phone, $phone);
          // if(count($account)==0) return $this->failed('账号请输入数字字母下划线');
          // if($pwd){
          //    preg_match($zz_p, $pwd, $pwd);
          //    if(count($pwd)==0)return $this->failed('密码请输入数字字母下划线');
          //    $pwd=md5($pwd[0]);
          // }else{
          //   $pwd=$userInfo['pwd'];
          // }
          // if(count($phone)==0) return $this->failed('手机号不正确');
          // $phone=$phone[0];
          User::edit([
            'nickname'=>$nickname?:'',
            'account'=>$account?:'',
            'nationality'=>$nationality?:'',
            'birthday'=>$birthday?:'',
             'name'=>$name?:'',
            'avatar'=>$avatar?:$userInfo['avatar']
        ],$this->userInfo['uid'],'uid');
          WechatUser::edit([
            'nickname'=>$nickname?:'',
            'headimgurl'=>$avatar?:$userInfo['avatar']
        ],$this->userInfo['uid'],'uid');

          return $this->successful('保存成功',Url::build('wap/my/index'));
        }
        return $this->fetch();
    }
public function upload()
{
    $file = request() -> file('fileToUpload');  
    // var_dump($file);
    //图片上传
  if($file){
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads'. DS .'avatar');
        if($info){
            return JsonService::status('success','上传成功','/public/uploads/avatar/'.$info->getSaveName());
            // 成功上传后 获取上传信息
            // 输出 jpg
            // echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            // echo $info->getSaveName();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            // echo $info->getFilename(); 
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
    }
}
    public function order($uni = '')
    {
        if(!$uni || !$order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni)) return $this->redirect(Url::build('order_list'));
         list($CollageInfo,$num)=Robot::getCollageInfo($uni);
        // dump($CollageInfo);die;
        $this->assign([
            'order'=>StoreOrder::tidyOrder($order,true),
            'CollageInfo'=>$CollageInfo,
            'appservice'=>GroupDataService::getData('app_customer_service')?:[],
        ]);
        return $this->fetch();
    }

    public function orderPinkOld($uni = '')
    {
        if(!$uni || !$order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni)) return $this->redirect(Url::build('order_list'));
       
        $this->assign([
            'order'=>StoreOrder::tidyOrder($order,true),


        ]);
        return $this->fetch('order');
    }

    /*  订单列表   */
        public function order_list()
    {
        $this->assign([
                'site_service_phone'=>SystemConfig::getValue('site_service_phone'),
                'lang'=>Session::has('lang')?'zh':'en',
                'appservice'=>GroupDataService::getData('app_customer_service')?:[],
            ]);
        return $this->fetch();
    }
    /*  拼团列表   */
        public function pin_list()
    {
        $this->assign([
                'site_service_phone'=>SystemConfig::getValue('site_service_phone'),
                'lang'=>Session::has('lang')?'zh':'en',
                'appservice'=>GroupDataService::getData('app_customer_service')?:[],
            ]);
        return $this->fetch();
    }
    /*     设置    */
    public function set ()
    {
        return $this->fetch();
    }
    /*     关于我们    */
    public function about ()
    {
        $this->assign([
            'info'=>db('article')->where('n.id',3)->alias('n')->field('n.*,c.content,c.en_content')->join('ArticleContent c','c.nid=n.id')->find(),
        ]);
        return $this->fetch();
    }
    /**
     * 用户指南
     */
    public function guide(){
      $this->assign([
            'info'=>db('article')->where('n.id',8)->alias('n')->field('n.*,c.content,c.en_content')->join('ArticleContent c','c.nid=n.id')->find(),
        ]);
        return $this->fetch();
    }
    /*     修改邮箱    */
    public function mailbox (Request $request)
    {
        list($account,$new_account) = UtilService::postMore(['account','new_account'],$request,true);
        if($account){
            $userInfo = User::where('uid',$this->userInfo['uid'])->where('account',$account)->find();
            if(!$userInfo){
                return $this->failed(L('輸入郵箱不正確','The mailbox entered is incorrect'));
            }else if(User::where('account',$new_account)->find()){
                return $this->failed(L('郵箱被佔用','Mailbox occupied'));
            }else{
                User::edit([
            'account'=>$new_account?:''
            ],$this->userInfo['uid'],'uid');
                return $this->successful(L('修改成功','Modified successfully'),Url::build('wap/my/index'));
            }

        }
        return $this->fetch();
    }

   /*     修改密码    */
    public function modify (Request $request)
    {   
        list($account,$pwd) = UtilService::postMore(['account','pwd'],$request,true);
        if($account){
            $userInfo = User::where('uid',$this->userInfo['uid'])->where('account',$account)->find();
            if(!$userInfo){
                return $this->failed(L('輸入郵箱不正確','The mailbox entered is incorrect'));
            }else{
                User::edit([
            'pwd'=>md5($pwd)?:''
            ],$this->userInfo['uid'],'uid');
                $this->_logout();
                return $this->successful(L('修改成功','Modified successfully'),Url::build('wap/login/index'));
            }

        }
        return $this->fetch();
    }   
    /*     隐私协议    */
     public function privacy ()
     {  
         $this->assign([
            'info'=>db('article')->where('n.id',4)->alias('n')->field('n.*,c.content,c.en_content')->join('ArticleContent c','c.nid=n.id')->find(),
        ]);
         return $this->fetch();
     }
 /*     分销    */
     public function distribution ()
     {
        $userInfo = User::where('uid',$this->userInfo['uid'])->find();
        //已提现
        $extract_ti=UserBill::where(['uid'=>$this->userInfo['uid'],'pm'=>0,'category'=>'now_money','type'=>'extract'])->column('number');
        $extract_fan=UserBill::where(['uid'=>$this->userInfo['uid'],'pm'=>1,'category'=>'now_money','type'=>'extract'])->column('number');
        //团队佣金
        $brokerage=UserBill::where(['uid'=>$this->userInfo['uid'],'pm'=>1,'category'=>'integral','type'=>'brokerage'])->column('number');
        $this->assign([
          'userInfo'=>$userInfo,
          'extract'=>array_sum($extract_ti)-array_sum($extract_fan) <0? 0:array_sum($extract_ti)-array_sum($extract_fan),
          'brokerage'=>array_sum($brokerage),
        ]);
         return $this->fetch();
     }
     /*     分销提现记录    */
         public function distributionintegral ()
         {
        //已提现
        $extract_ti=UserBill::where(['uid'=>$this->userInfo['uid'],'pm'=>0,'category'=>'now_money','type'=>'extract'])->column('number');
        $extract_fan=UserBill::where(['uid'=>$this->userInfo['uid'],'pm'=>1,'category'=>'now_money','type'=>'extract'])->column('number');
        $this->assign([
          'extract'=>array_sum($extract_ti)-array_sum($extract_fan) <0? 0:array_sum($extract_ti)-array_sum($extract_fan),
        ]);
             return $this->fetch();
         }
         /*     分销团队    */
         public function team ()
         {
            $userInfo = User::where('uid',$this->userInfo['uid'])->find();
            //获取上级
            $first_leader= User::where('spread_uid',$this->userInfo['uid'])->column('uid');

            //获取上上级
            if(count($first_leader)>0){
              $next_leader=User::where(['spread_uid'=>['in',$first_leader]])->column('uid');
            }else{
              $next_leader=[];
            }
            
            //合并
            $leader=array_merge($first_leader,$next_leader);
            //订单总数
            $order_num=StoreOrder::where(['uid'=>['in',$leader],'pin_type'=>1,'pin_status'=>['in',[2,3]]])->count();
            //团队订单额
            $brokerage=StoreOrder::where(['uid'=>['in',$leader],'pin_type'=>1,'pin_status'=>['in',[2,3]]])->column('pay_price');

            $leader_info=User::where(['uid'=>['in',$leader]])->select();
            foreach ($leader_info as $key => $value) {
              $leader_info[$key]['add_times']=date('Y-m-d H:i:s',$leader_info[$key]['add_time']);
               $leader_info[$key]['brokerage']=array_sum(StoreOrder::where(['uid'=>$value['uid'],'pin_type'=>1,'pin_status'=>['in',[2,3]]])->column('pay_price'));
               $leader_info[$key]['order_num']=StoreOrder::where(['uid'=>$value['uid'],'pin_type'=>1,'pin_status'=>['in',[2,3]]])->count();
               $leader_info[$key]['order_num_month']=StoreOrder::where(['uid'=>$value['uid'],'pin_type'=>1,'pin_status'=>['in',[2,3]]])->whereTime('pay_time', 'month')->count();
            }
            // dump($leader_info);die;
            $this->assign([
              'userInfo'=>$userInfo,
              'conut_leader'=>count($leader),
              'order_num'=>$order_num,
              'leader_info'=>$leader_info,
              'brokerage'=>array_sum($brokerage),
          ]);
             return $this->fetch();
         }

    /*    邀请拼团    */
     public function invite ($order_id='')
     {
            if(!$order_id){
                return $this->failed(L('訂單不存在！','order does not exist!'));
            }
            $orderInfo=StoreOrder::where(['order_id'=>$order_id])->find();
             list($CollageInfo,$num)=Robot::getCollageInfo($order_id);
        // dump($CollageInfo);die;
        $spell_time=(int)SystemConfig::getValue('spell_time');
        $spell_time=date('Y/m/d H:i:s',$orderInfo['pay_time']+$spell_time*60);
        $this->assign([
            'Collage_num'=>$num,
            'CollageInfo'=>$CollageInfo,
            'spell_time'=>$spell_time,
            'info'=>db('article')->where('n.id',7)->alias('n')->field('n.*,c.content,c.en_content')->join('ArticleContent c','c.nid=n.id')->find(),
        ]);

         return $this->fetch();
     }

    public function order_reply($unique = '')
    {
        if(!$unique || !StoreOrderCartInfo::be(['unique'=>$unique]) || !($cartInfo = StoreOrderCartInfo::where('unique',$unique)->find())) return $this->failed('评价产品不存在!');
        $this->assign(['cartInfo'=>$cartInfo]);
        return $this->fetch();
    }
    public function querys(){
        $con=$_GET['con'];
        $order_id=$_GET['order_id'];
        $result = \wxpay\Query::exec($order_id);
  
    if ($result['trade_state']=='SUCCESS') {
        $orderInfo = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni);
        UserBill::expend('购买商品',$this->userInfo['uid'],'now_money','pay_product',$orderInfo['pay_price'],$orderInfo['id'],$userInfo['now_money'],'微信支付'.floatval($orderInfo['pay_price']).'元购买商品');
            StoreOrder::paySuccess($uni);
            return $this->successful('微信支付',Url::build('wap/my/order',['uni'=>$uni]),'支付成功');
        }
             echo $con;
    }
    public function queryss($order_id = ''){
              if(!$order_id) return $this->failed('参数错误!');
              $result = \wxpay\Query::exec($order_id);
              if ($result['trade_state']=='SUCCESS') {
                 $orderInfo = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni);
        UserBill::expend('购买商品',$this->userInfo['uid'],'now_money','pay_product',$orderInfo['pay_price'],$orderInfo['id'],$userInfo['now_money'],'微信支付'.floatval($orderInfo['pay_price']).'元购买商品');
            StoreOrder::paySuccess($order_id);
            return $this->successful('微信支付',Url::build('wap/my/order',['uni'=>$order_id]),'支付成功');
        }else{
            return $this->successful('支付失败',Url::build('wap/my/order',['uni'=>$order_id]),'订单已生成');
        }
    }
     /**
     * 支付后回调
     * @param string $uni
     * @return \think\response\Json
     */
    public function query($uni = '')
    {   
        if(!$uni) return JsonService::fail('参数错误!');
       
         
   $result = \wxpay\Query::exec($uni);
  
    if ($result['trade_state']=='SUCCESS') {
             $orderInfo = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni);
        UserBill::expend('购买商品',$this->userInfo['uid'],'now_money','pay_product',$orderInfo['pay_price'],$orderInfo['id'],$userInfo['now_money'],'微信支付'.floatval($orderInfo['pay_price']).'元购买商品');
            StoreOrder::paySuccess($uni);
            return $this->successful('微信支付',Url::build('wap/my/order',['uni'=>$uni]),'支付成功');
        }
        // halt($result);
       $order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni);
        $this->assign('order',$order);
        return $this->fetch();
    }


     public function userquerys(){
        $con=$_GET['con'];
        $order_id=$_GET['order_id'];
        $result = \wxpay\Query::exec($order_id);
  
    if ($result['trade_state']=='SUCCESS') {
            UserRecharge::rechargeSuccess($uni);
            return $this->successful('微信支付',Url::build('wap/my/balance'),'充值成功');
        }
             echo $con;
    }
    public function userqueryss($order_id = ''){
              if(!$order_id) return $this->failed('参数错误!');
              $result = \wxpay\Query::exec($order_id);
              if ($result['trade_state']=='SUCCESS') {
            UserRecharge::rechargeSuccess($uni);
            return $this->successful('微信支付',Url::build('wap/my/balance'),'充值成功');
        }else{
            return $this->successful('微信支付',Url::build('wap/my/balance'),'支付失败');
        }
    }
     /**
     * 充值后回调
     * @param string $uni
     * @return \think\response\Json
     */
    public function userquery($uni = '')
    {   
        if(!$uni) return JsonService::fail('参数错误!');
       
         
   $result = \wxpay\Query::exec($uni);
  
    if ($result['trade_state']=='SUCCESS') {
            UserRecharge::rechargeSuccess($uni);
            return $this->successful('微信支付',Url::build('wap/my/balance'),'充值成功');
        }
        // halt($result);
       $order= Db::name('user_recharge')->where('order_id',$uni)->where('paid',0)->find();
        $this->assign('order',$order);
        return $this->fetch();
    }

    public function balance()
    { 
      //未到账的余额
        $no_now_money=UserBill::where(['uid'=>$this->userInfo['uid'],'category'=>'now_money','status'=>0,'pm'=>1])->column('number');
        $no_now_money=array_sum($no_now_money);
        $this->assign([
            'userMinRecharge'=>SystemConfigService::get('store_user_min_recharge'),
            'no_now_money'=>$no_now_money
        ]);
        return $this->fetch();
    }

    public function integral()
    {
        $now_money=UserBill::where('uid',$this->userInfo['uid'])->where('category','now_money')
        ->where('status',1)->column('number');
         //未到账的佣金
        $no_integral=UserBill::where(['uid'=>$this->userInfo['uid'],'category'=>'integral','status'=>0,'pm'=>1])->column('number');
        $no_integral=array_sum($no_integral);
        $this->assign(['now_money'=>array_sum($now_money),'no_integral'=>$no_integral]);
        return $this->fetch();
    }

    public function spread_list()
    {
        $statu = (int)SystemConfig::getValue('store_brokerage_statu');
        if($statu == 1){
            if(!User::be(['uid'=>$this->userInfo['uid'],'is_promoter'=>1]))
                return $this->failed('没有权限访问!');
        }
        $this->assign([
            'total'=>User::where('spread_uid',$this->userInfo['uid'])->count()
        ]);
        return $this->fetch();
    }

    public function notice()
    {

        return $this->fetch();
    }

    public function express($uni = '')
    {
        if(!$uni || !($order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni))) return $this->failed('查询订单不存在!');
        if($order['delivery_type'] != 'express' || !$order['delivery_id']) return $this->failed('该订单不存在快递单号!');
        $cacheName = $uni.$order['delivery_id'];
        $result = CacheService::get($cacheName,null);
        if($result === null){
            $result = Express::query($order['delivery_id']);
            if(is_array($result) &&
                isset($result['result']) &&
                isset($result['result']['deliverystatus']) &&
                $result['result']['deliverystatus'] >= 3)
                $cacheTime = 0;
            else
                $cacheTime = 1800;
            CacheService::set($cacheName,$result,$cacheTime);
        }
        $this->assign([
            'order'=>$order,
            'express'=>$result
        ]);
        return $this->fetch();
    }


    public function user_pro()
    {
        $statu = (int)SystemConfig::getValue('store_brokerage_statu');
        if($statu == 1){
            if(!User::be(['uid'=>$this->userInfo['uid'],'is_promoter'=>1]))
                return $this->failed('没有权限访问!');
        }
        $userBill = new UserBill();
        $number = $userBill->where('uid',$this->userInfo['uid'])
            ->where('add_time','BETWEEN',[strtotime('today -1 day'),strtotime('today')])
            ->where('category','now_money')
            ->where('type','brokerage')
            ->value('SUM(number)')?:0;
        $allNumber = $userBill
            ->where('uid',$this->userInfo['uid'])
            ->where('category','now_money')
            ->where('type','brokerage')
            ->value('SUM(number)')?:0;
        $extractNumber = UserExtract::userExtractTotalPrice($this->userInfo['uid']);
        $this->assign([
            'number'=>$number,
            'allnumber'=>$allNumber,
            'extractNumber'=>$extractNumber
        ]);
        return $this->fetch();
    }


    public function commission()
    {
        $uid = (int)Request::instance()->get('uid',0);
        if(!$uid) return $this->failed('用户不存在!');
        $this->assign(['uid'=>$uid]);
        return $this->fetch();
    }


    /* 余额提现  */
    public function extract()
    {
        $minExtractPrice = floatval(SystemConfigService::get('user_extract_min_price'))?:0;
        $userextractservicecharge = floatval(SystemConfigService::get('user_extract_service_charge'))?:0;
        $withdrawal=GroupDataService::getData('withdrawal_method')?:[];
        $extractInfo = UserExtract::userLastInfo($this->userInfo['uid'])?:[
            'extract_type'=>$withdrawal[0]['name'],
            'real_name'=>'',
            'bank_code'=>'',
            'bank_address'=>'',
            'alipay_code'=>''
        ];
        
        $this->assign(compact('minExtractPrice','extractInfo','withdrawal','userextractservicecharge'));
        return $this->fetch();
    }


    /* 佣金提现  */
    public function yjextract()
    {
        $minExtractPrice = floatval(SystemConfigService::get('user_extract_min_price'))?:0;
        $userextractservicecharge = floatval(SystemConfigService::get('user_extract_service_charge'))?:0;
        $withdrawal=GroupDataService::getData('withdrawal_method')?:[];
        $extractInfo = UserExtract::userLastInfo($this->userInfo['uid'])?:[
            'extract_type'=>$withdrawal[0]['name'],
            'real_name'=>'',
            'bank_code'=>'',
            'bank_address'=>'',
            'alipay_code'=>''
        ];
        
        $this->assign(compact('minExtractPrice','extractInfo','withdrawal','userextractservicecharge'));
        return $this->fetch();
    }


    /**
     * 创建拼团
     * @param string $uni
     */
//    public function createPink($uni = ''){
//        if(!$uni || !$order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni)) return $this->redirect(Url::build('order_list'));
//        $order = StoreOrder::tidyOrder($order,true)->toArray();
//        if($order['pink_id']){//拼团存在
//            $res = false;
//            $pink['uid'] = $order['uid'];//用户id
//            if(StorePink::isPinkBe($pink,$order['pink_id'])) return $this->redirect('order_pink',['id'=>$order['pink_id']]);
//            $pink['order_id'] = $order['order_id'];//订单id  生成
//            $pink['order_id_key'] = $order['id'];//订单id  数据库id
//            $pink['total_num'] = $order['total_num'];//购买个数
//            $pink['total_price'] = $order['pay_price'];//总金额
//            $pink['k_id'] = $order['pink_id'];//拼团id
//            foreach ($order['cartInfo'] as $v){
//                $pink['cid'] = $v['combination_id'];//拼团产品id
//                $pink['pid'] = $v['product_id'];//产品id
//                $pink['people'] = StoreCombination::where('id',$v['combination_id'])->value('people');//几人拼团
//                $pink['price'] = $v['productInfo']['price'];//单价
//                $pink['stop_time'] = 0;//结束时间
//                $pink['add_time'] = time();//开团时间
//                $res = StorePink::set($pink)->toArray();
//            }
//            if($res) $this->redirect('order_pink',['id'=>$res['id']]);
//            else $this->failed('创建拼团失败,请退款后再次拼团',Url::build('my/index'));
//            $this->redirect('order_pink',['id'=>$order['pink_id']]);
//        }else{
//            $res = false;
//            $pink['uid'] = $order['uid'];//用户id
//            $pink['order_id'] = $order['order_id'];//订单id  生成
//            $pink['order_id_key'] = $order['id'];//订单id  数据库id
//            $pink['total_num'] = $order['total_num'];//购买个数
//            $pink['total_price'] = $order['pay_price'];//总金额
//            $pink['k_id'] = 0;//拼团id
//            foreach ($order['cartInfo'] as $v){
//                $pink['cid'] = $v['combination_id'];//拼团产品id
//                $pink['pid'] = $v['product_id'];//产品id
//                $pink['people'] = StoreCombination::where('id',$v['combination_id'])->value('people');//几人拼团
//                $pink['price'] = $v['productInfo']['price'];//单价
//                $pink['stop_time'] = time()+86400;//结束时间
//                $pink['add_time'] = time();//开团时间
//                $res1 = StorePink::set($pink)->toArray();
//                $res2 = StoreOrder::where('id',$order['id'])->update(['pink_id'=>$res1['id']]);
//                $res = $res1 && $res2;
//            }
//            if($res) $this->redirect('order_pink',['id'=>$res1['id']]);
//            else $this->failed('创建拼团失败,请退款后再次拼团',Url::build('my/index'));
//        }
//    }

     /**
     * 参团详情页
     */
    public function order_pink($id = 0){
        if(!$id) return $this->failed('参数错误',Url::build('my/index'));
        $pink = StorePink::getPinkUserOne($id);
        if(isset($pink['is_refund']) && $pink['is_refund']) {
            if($pink['is_refund'] != $pink['id']){
                $id = $pink['is_refund'];
                return $this->order_pink($id);
            }else{
                return $this->failed('订单已退款',Url::build('store/combination_detail',['id'=>$pink['cid']]));
            }
        }
        if(!$pink) return $this->failed('参数错误',Url::build('my/index'));
        $pinkAll = array();//参团人  不包括团长
        $pinkT = array();//团长
        if($pink['k_id']){
            $pinkAll = StorePink::getPinkMember($pink['k_id']);
            $pinkT = StorePink::getPinkUserOne($pink['k_id']);
        }else{
            $pinkAll = StorePink::getPinkMember($pink['id']);
            $pinkT = $pink;
        }
        $store_combination = StoreCombination::getCombinationOne($pink['cid']);//拼团产品
        $count = count($pinkAll)+1;
        $count = (int)$pinkT['people']-$count;//剩余多少人
        $is_ok = 0;//判断拼团是否完成
        $idAll =  array();
        $uidAll =  array();
        if(!empty($pinkAll)){
            foreach ($pinkAll as $k=>$v){
                $idAll[$k] = $v['id'];
                $uidAll[$k] = $v['uid'];
            }
        }

        $userBool = 0;//判断当前用户是否在团内  0未在 1在
        $pinkBool = 0;//判断当前用户是否在团内  0未在 1在
        $idAll[] = $pinkT['id'];
        $uidAll[] = $pinkT['uid'];
        if($pinkT['status'] == 2){
            $pinkBool = 1;
        }else{
            if(!$count){//组团完成
                $idAll = implode(',',$idAll);
                $orderPinkStatus = StorePink::setPinkStatus($idAll);
                if($orderPinkStatus){
                    if(in_array($this->uid,$uidAll)){
                        StorePink::setPinkStopTime($idAll);
                        if(StorePink::isTpl($uidAll,$pinkT['id'])) StorePink::orderPinkAfter($uidAll,$pinkT['id']);
                        $pinkBool = 1;
                    }else  $pinkBool = 3;
                }else $pinkBool = 6;
            }
            else{
                if($pinkT['stop_time'] < time()){//拼团时间超时  退款
                    if($pinkAll){
                        foreach ($pinkAll as $v){
                            if($v['uid'] == $this->uid){
                                $res = StoreOrder::orderApplyRefund(StoreOrder::where('id',$v['order_id_key'])->value('order_id'),$this->uid,'拼团时间超时');
                                if($res){
                                    if(StorePink::isTpl($v['uid'],$pinkT['id'])) StorePink::orderPinkAfterNo($v['uid'],$v['k_id']);
                                    $pinkBool = 2;
                                }else return $this->failed(StoreOrder::getErrorInfo(),Url::build('index'));
                            }
                        }
                    }
                    if($pinkT['uid'] == $this->uid){
                        $res = StoreOrder::orderApplyRefund(StoreOrder::where('id',$pinkT['order_id_key'])->value('order_id'),$this->uid,'拼团时间超时');
                        if($res){
                            if(StorePink::isTpl($pinkT['uid'],$pinkT['id']))  StorePink::orderPinkAfterNo($pinkT['uid'],$pinkT['id']);
                            $pinkBool = 2;
                        }else return $this->failed(StoreOrder::getErrorInfo(),Url::build('index'));
                    }
                    if(!$pinkBool) $pinkBool = 3;
                }
            }
        }
        $store_combination_host =  StoreCombination::getCombinationHost();//获取推荐的拼团产品
        if(!empty($pinkAll)){
            foreach ($pinkAll as $v){
                if($v['uid'] == $this->uid) $userBool = 1;
            }
        }
        if($pinkT['uid'] == $this->uid) $userBool = 1;
        $combinationOne = StoreCombination::getCombinationOne($pink['cid']);
        if(!$combinationOne) return $this->failed('拼团不存在或已下架');
        $combinationOne['images'] = json_decode($combinationOne['images'],true);
        $combinationOne['userLike'] = StoreProductRelation::isProductRelation($combinationOne['product_id'],$this->userInfo['uid'],'like');
        $combinationOne['like_num'] = StoreProductRelation::productRelationNum($combinationOne['product_id'],'like');
        $combinationOne['userCollect'] = StoreProductRelation::isProductRelation($combinationOne['product_id'],$this->userInfo['uid'],'collect');
        $this->assign('storeInfo',$combinationOne);
        $this->assign('current_pink_order',StorePink::getCurrentPink($id));
        $this->assign(compact('pinkBool','is_ok','userBool','store_combination','pinkT','pinkAll','count','store_combination_host'));
        return $this->fetch();
    }

    /**
     * 参团详情页  失败或者成功展示页
     */
    public function order_pink_after($id = 0){
        if(!$id) return $this->failed('参数错误',Url::build('my/index'));
        $pink = StorePink::getPinkUserOne($id);
        if(!$pink) return $this->failed('参数错误',Url::build('my/index'));
        $pinkAll = array();//参团人  不包括团长
        $pinkT = array();//团长
        if($pink['k_id']){
            $pinkAll = StorePink::getPinkMember($pink['k_id']);
            $pinkT = StorePink::getPinkUserOne($pink['k_id']);
        }else{
            $pinkAll = StorePink::getPinkMember($pink['id']);
            $pinkT = $pink;
        }
        $store_combination = StoreCombination::getCombinationOne($pink['cid']);//拼团产品
        $count = count($pinkAll)+1;
        $count = (int)$pinkT['people']-$count;//剩余多少人
        $idAll =  array();
        $uidAll =  array();
        if(!empty($pinkAll)){
            foreach ($pinkAll as $k=>$v){
                $idAll[$k] = $v['id'];
                $uidAll[$k] = $v['uid'];
            }
        }
        $idAll[] = $pinkT['id'];
        $uidAll[] = $pinkT['uid'];
        $userBool = 0;//判断当前用户是否在团内是否完成拼团
        if(!$count) $userBool = 1;//组团完成
        $store_combination_host =  StoreCombination::getCombinationHost();//获取推荐的拼团产品
        $combinationOne = StoreCombination::getCombinationOne($pink['cid']);
        if(!$combinationOne) return $this->failed('拼团不存在或已下架');
        $combinationOne['images'] = json_decode($combinationOne['images'],true);
        $combinationOne['userLike'] = StoreProductRelation::isProductRelation($combinationOne['product_id'],$this->userInfo['uid'],'like');
        $combinationOne['like_num'] = StoreProductRelation::productRelationNum($combinationOne['product_id'],'like');
        $combinationOne['userCollect'] = StoreProductRelation::isProductRelation($combinationOne['product_id'],$this->userInfo['uid'],'collect');
        $this->assign('storeInfo',$combinationOne);
        $this->assign(compact('userBool','store_combination','pinkT','pinkAll','count','store_combination_host'));
        return $this->fetch();
    }

    /**
     * 售后服务  退款订单
     * @return mixed
     */
    public function order_customer(){
        return $this->fetch();
    }
    //活动
    public function activity(){
        return $this->fetch();
    }
    //游戏
    public function game_list(){
        return $this->fetch();
    }
    //清除登录
    private function _logout()
    {
        $lang=Session::get('lang');
        Session::clear('wap');
        if($lang=='zh'){
            Session::set('lang', 'zh');
        }
        Cookie::delete('is_login');
    }

    
     /* 搜索页 */
    public function search()
    {   
        if (!Session::has('lang')) {
            $lang='en_';
            }else{
              $lang='';  
            }
            $field='id,'.$lang.'store_name as new_store_name,image,sales,ficti,browse,stock';
            // dump($this->userInfo['uid']);die;
         $this->assign([
            'keyword'=>db('keyword')->where(['uid'=>$this->userInfo['uid']])->order('id DESC')->limit(12)->select()?:[],
            'hotproduct'=>StoreProduct::getHotSearch($field,10)?:[],
        ]);
        return $this->fetch('index/search');
    }

}
