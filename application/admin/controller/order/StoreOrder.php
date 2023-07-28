<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */
namespace app\admin\controller\order;

use Api\Express;
use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use app\admin\model\order\StoreOrderStatus;
use app\admin\model\ump\StorePink;
use app\admin\model\user\User;
use app\admin\model\user\UserBill;
use basic\ModelBasic;
use behavior\admin\OrderBehavior;
use behavior\wechat\PaymentBehavior;
use EasyWeChat\Core\Exception;
use service\CacheService;
use service\HookService;
use service\JsonService;
use app\core\util\SystemConfigService;
use service\UtilService as Util;
use service\JsonService as Json;
use think\Db;
use think\Request;
use think\Url;
use app\admin\model\order\StoreOrder as StoreOrderModel;
/**
 * 订单管理控制器 同一个订单表放在一个控制器
 * Class StoreOrder
 * @package app\admin\controller\store
 */
class StoreOrder extends AuthController
{
    /**
     * @return mixed
     */
    public function index()
    {
        $this->assign([
            'year'=>getMonth('y'),
            'real_name'=>$this->request->get('real_name',''),
            'status'=>$this->request->param('status',''),
            'orderCount'=>StoreOrderModel::orderCount(),
        ]);
        return $this->fetch();
    }
    /**
     * 获取头部订单金额等信息
     * return json
     *
     */
    public function getBadge(){
        $where = Util::postMore([
            ['status',''],
            ['real_name',''],
            ['is_del',0],
            ['data',''],
            ['type',''],
            ['order','']
        ]);
        return JsonService::successful(StoreOrderModel::getBadge($where));
    }
    /**
     * 获取订单列表
     * return json
     */
    public function order_list(){
        $where = Util::getMore([
            ['status',''],
            ['real_name',$this->request->param('real_name','')],
            ['is_del',0],
            ['data',''],
            ['type',''],
            ['order',''],
            ['page',1],
            ['limit',20],
            ['excel',0]
        ]);
        return JsonService::successlayui(StoreOrderModel::OrderList($where));
    }
    public function orderchart(){
        $where = Util::getMore([
            ['status',''],
            ['real_name',''],
            ['is_del',0],
            ['data',''],
            ['combination_id',''],
            ['export',0],
            ['order','id desc']
        ],$this->request);
        $limitTimeList = [
            'today'=>implode(' - ',[date('Y/m/d'),date('Y/m/d',strtotime('+1 day'))]),
            'week'=>implode(' - ',[
                date('Y/m/d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)),
                date('Y-m-d', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600))
            ]),
            'month'=>implode(' - ',[date('Y/m').'/01',date('Y/m').'/'.date('t')]),
            'quarter'=>implode(' - ',[
                date('Y').'/'.(ceil((date('n'))/3)*3-3+1).'/01',
                date('Y').'/'.(ceil((date('n'))/3)*3).'/'.date('t',mktime(0,0,0,(ceil((date('n'))/3)*3),1,date('Y')))
            ]),
            'year'=>implode(' - ',[
                date('Y').'/01/01',date('Y/m/d',strtotime(date('Y').'/01/01 + 1year -1 day'))
            ])
        ];
        if($where['data'] == '') $where['data'] = $limitTimeList['today'];
        $orderCount = [
            urlencode('未支付')=>StoreOrderModel::getOrderWhere($where,StoreOrderModel::statusByWhere(0))->count(),
            urlencode('未发货')=>StoreOrderModel::getOrderWhere($where,StoreOrderModel::statusByWhere(1))->count(),
            urlencode('待收货')=>StoreOrderModel::getOrderWhere($where,StoreOrderModel::statusByWhere(2))->count(),
            urlencode('待评价')=>StoreOrderModel::getOrderWhere($where,StoreOrderModel::statusByWhere(3))->count(),
            urlencode('交易完成')=>StoreOrderModel::getOrderWhere($where,StoreOrderModel::statusByWhere(4))->count(),
            urlencode('退款中')=>StoreOrderModel::getOrderWhere($where,StoreOrderModel::statusByWhere(-1))->count(),
            urlencode('已退款')=>StoreOrderModel::getOrderWhere($where,StoreOrderModel::statusByWhere(-2))->count()
        ];
        $model = StoreOrderModel::getOrderWhere($where,new StoreOrderModel())->field('sum(total_num) total_num,count(*) count,sum(total_price) total_price,sum(refund_price) refund_price,from_unixtime(add_time,\'%Y-%m-%d\') add_time')
            ->group('from_unixtime(add_time,\'%Y-%m-%d\')');
        $orderPrice = $model->select()->toArray();
        $orderDays = [];
        $orderCategory = [
            ['name'=>'商品数','type'=>'line','data'=>[]],
            ['name'=>'订单数','type'=>'line','data'=>[]],
            ['name'=>'订单金额','type'=>'line','data'=>[]],
            ['name'=>'退款金额','type'=>'line','data'=>[]]
        ];
        foreach ($orderPrice as $price){
            $orderDays[] = $price['add_time'];
            $orderCategory[0]['data'][] = $price['total_num'];
            $orderCategory[1]['data'][] = $price['count'];
            $orderCategory[2]['data'][] = $price['total_price'];
            $orderCategory[3]['data'][] = $price['refund_price'];
        }
        $this->assign(StoreOrderModel::systemPage($where,$this->adminId));
        $this->assign('price',StoreOrderModel::getOrderPrice($where));
        $this->assign(compact('limitTimeList','where','orderCount','orderPrice','orderDays','orderCategory'));
        return $this->fetch();
    }
    /**
     * 修改支付金额等
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function edit($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::input('order_id','订单编号',$product->getData('order_id'))->disabled(1);
        $f[] = Form::number('total_price','商品总价',$product->getData('total_price'))->min(0);
        $f[] = Form::number('total_postage','原始邮费',$product->getData('total_postage'))->min(0);
        $f[] = Form::number('pay_price','实际支付金额',$product->getData('pay_price'))->min(0);
        $f[] = Form::number('pay_postage','实际支付邮费',$product->getData('pay_postage'));
        // $f[] = Form::number('gain_integral','赠送积分',$product->getData('gain_integral'));
//        $f[] = Form::radio('status','状态',$product->getData('status'))->options([['label'=>'开启','value'=>1],['label'=>'关闭','value'=>0]]);
        $form = Form::make_post_form('修改订单',$f,Url::build('update',array('id'=>$id)));
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');

    }

    /** 修改订单提交更新
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            'order_id',
            'total_price',
            'total_postage',
            'pay_price',
            'pay_postage',
            'gain_integral',
        ],$request);
        if($data['total_price'] <= 0) return Json::fail('请输入商品总价');
        if($data['pay_price'] <= 0) return Json::fail('请输入实际支付金额');
        $data['order_id'] = StoreOrderModel::changeOrderId($data['order_id']);
        StoreOrderModel::edit($data,$id);
        HookService::afterListen('store_product_order_edit',$data,$id,false,OrderBehavior::class);
        StoreOrderStatus::setStatus($id,'order_edit','修改商品总价为：'.$data['total_price'].' 实际支付金额'.$data['pay_price']);
        return Json::successful('修改成功!');
    }

    /**
     * TODO 填写送货信息
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function delivery($id){
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        if($product['paid'] == 1 && $product['status'] == 0) {
            $f = array();
            $f[] = Form::input('delivery_name','送货人姓名')->required('送货人姓名不能为空','required:true;');
            $f[] = Form::input('delivery_id','送货人电话')->required('请输入正确电话号码','telephone');
            $form = Form::make_post_form('修改订单',$f,Url::build('updateDelivery',array('id'=>$id)),5);
            $this->assign(compact('form'));
            return $this->fetch('public/form-builder');
        }
        else $this->failedNotice('订单状态错误');
    }

    /**
     * TODO 送货信息提交
     * @param Request $request
     * @param $id
     */
    public function updateDelivery(Request $request, $id){
        $data = Util::postMore([
            'delivery_name',
            'delivery_id',
        ],$request);
        $data['delivery_type'] = 'send';
        if(!$data['delivery_name']) return Json::fail('请输入送货人姓名');
        if(!(int)$data['delivery_id']) return Json::fail('请输入送货人电话号码');
        else if(!preg_match("/^1[3456789]{1}\d{9}$/",$data['delivery_id']))  return Json::fail('请输入正确的送货人电话号码');
        $data['status'] = 1;
        StoreOrderModel::edit($data,$id);
        HookService::afterListen('store_product_order_delivery',$data,$id,false,OrderBehavior::class);
        StoreOrderStatus::setStatus($id,'delivery','已配送 发货人：'.$data['delivery_name'].' 发货人电话：'.$data['delivery_id']);
        return Json::successful('修改成功!');
    }

    /**
     * TODO 填写发货信息
     * @param $id
     * @return mixed|void
     * @throws \think\exception\DbException
     */
    public function deliver_goods($id){
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        if($product['paid'] == 1 && $product['status'] == 0){
            $f = array();
            $f[] = Form::select('delivery_name','快递公司')->setOptions(function(){
                        $list =  Db::name('express')->where('is_show',1)->order('sort DESC')->column('id,name');
                        $menus = [];
                        foreach ($list as $k=>$v){
                            $menus[] = ['value'=>$v,'label'=>$v];
                        }
                        return $menus;
                    })->filterable(1);
            $f[] = Form::input('delivery_id','快递单号');
            $form = Form::make_post_form('修改订单',$f,Url::build('updateDeliveryGoods',array('id'=>$id)),2);
            $this->assign(compact('form'));
            return $this->fetch('public/form-builder');
        }
        else return $this->failedNotice('订单状态错误');
    }

    /**
     * TODO 发货信息提交
     * @param Request $request
     * @param $id
     */
    public function updateDeliveryGoods(Request $request, $id){
        $data = Util::postMore([
            'delivery_name',
            'delivery_id',
        ],$request);
        $data['delivery_type'] = 'express';
        if(!$data['delivery_name']) return Json::fail('请选择快递公司');
        if(!$data['delivery_id']) return Json::fail('请输入快递单号');
        $data['status'] = 1;
        StoreOrderModel::edit($data,$id);
        HookService::afterListen('store_product_order_delivery_goods',$data,$id,false,OrderBehavior::class);
        StoreOrderStatus::setStatus($id,'delivery_goods','已发货 快递公司：'.$data['delivery_name'].' 快递单号：'.$data['delivery_id']);
        return Json::successful('修改成功!');
    }
    /**
     * 修改状态为已收货
     * @param $id
     * @return \think\response\Json|void
     */
    public function take_delivery($id){
        if(!$id) return $this->failed('数据不存在');
        $order = StoreOrderModel::get($id);
        if(!$order) return Json::fail('数据不存在!');
        if($order['status'] == 2) return Json::fail('不能重复收货!');
        if($order['paid'] == 1 && $order['status'] == 1) $data['status'] = 2;
        else if($order['pay_type'] == 'offline') $data['status'] = 2;
        else return Json::fail('请先发货或者送货!');
        if(!StoreOrderModel::edit($data,$id))
            return Json::fail(StoreOrderModel::getErrorInfo('收货失败,请稍候再试!'));
        else{
            try{
                HookService::listen('store_product_order_take_delivery',$order,$id,false,OrderBehavior::class);
            }catch (\Exception $e){
                return Json::fail($e->getMessage());
            }
            StoreOrderStatus::setStatus($id,'take_delivery','已收货');
            return Json::successful('收货成功!');
        }
    }
    /**
     * 修改退款状态
     * @param $id
     * @return \think\response\Json|void
     */
    public function refund_y($id){
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        if($product['paid'] == 1){
            $f = array();
            $f[] = Form::input('order_id','退款单号',$product->getData('order_id'))->disabled(1);
            $f[] = Form::number('refund_price','退款金额',$product->getData('pay_price'))->precision(2)->min(0.01);
            $f[] = Form::radio('type','状态',1)->options([['label'=>'直接退款','value'=>1],['label'=>'退款后,返回原状态','value'=>2]]);
            $form = Form::make_post_form('退款处理',$f,Url::build('updateRefundY',array('id'=>$id)),2);
            $this->assign(compact('form'));
            return $this->fetch('public/form-builder');
        }
        else return Json::fail('数据不存在!');
    }

    /**退款处理
     * @param Request $request
     * @param $id
     */
    public function updateRefundY(Request $request, $id){
        $data = Util::postMore([
            'refund_price',
            ['type',1],
        ],$request);
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        if($product['pay_price'] == $product['refund_price']) return Json::fail('已退完支付金额!不能再退款了');
        if(!$data['refund_price']) return Json::fail('请输入退款金额');
        $refund_price = $data['refund_price'];
        $data['refund_price'] = bcadd($data['refund_price'],$product['refund_price'],2);
        $bj = bccomp((float)$product['pay_price'],(float)$data['refund_price'],2);
        if($bj < 0) return Json::fail('退款金额大于支付金额，请修改退款金额');
        if($data['type'] == 1){
            $data['refund_status'] = 2;
        }else if($data['type'] == 2){
            $data['refund_status'] = 0;
        }
        $type =  $data['type'];
        unset($data['type']);
        $refund_data['pay_price'] = $product['pay_price'];
        $refund_data['refund_price'] = $refund_price;
        if($product['pay_type'] == 'weixin'){
            if($product['is_channel']){//小程序
                try{
                    HookService::listen('routine_pay_order_refund',$product['order_id'],$refund_data,true,PaymentBehavior::class);
                }catch(\Exception $e){
                    return Json::fail($e->getMessage());
                }
            }else{
                try{
                    HookService::listen('wechat_pay_order_refund',$product['order_id'],$refund_data,true,PaymentBehavior::class);
                }catch(\Exception $e){
                    return Json::fail($e->getMessage());
                }
            }
        }else if($product['pay_type'] == 'yue'){
            ModelBasic::beginTrans();
            $usermoney = User::where('uid',$product['uid'])->value('now_money');
            $res1 = User::bcInc($product['uid'],'now_money',$refund_price,'uid');
            $res2 = $res2 = UserBill::income('商品退款',$product['uid'],'now_money','pay_product_refund',$refund_price,$product['id'],bcadd($usermoney,$refund_price,2),'訂單退款到餘額 $'.floatval($refund_price),'Merchandise refund','Order refund to balance $'.floatval($refund_price));
            try{
                HookService::listen('store_order_yue_refund',$product,$refund_data,false,OrderBehavior::class);
            }catch (\Exception $e){
                ModelBasic::rollbackTrans();
                return Json::fail($e->getMessage());
            }
            $res = $res1 && $res2;
            ModelBasic::checkTrans($res);
            if(!$res) return Json::fail('余额退款失败!');
        }
        $resEdit = StoreOrderModel::edit($data,$id);
        if($resEdit){
            $data['type'] = $type;
            if($data['type'] == 1)  StorePink::setRefundPink($id);
            HookService::afterListen('store_product_order_refund_y',$data,$id,false,OrderBehavior::class);
            StoreOrderStatus::setStatus($id,'refund_price','退款给用户'.$refund_price.'元');
            ModelBasic::commitTrans();
            return Json::successful('修改成功!');
        }else{
            StoreOrderStatus::setStatus($id,'refund_price','退款给用户'.$refund_price.'元失败');
            return Json::successful('修改失败!');
        }
    }
    public function order_info($oid = '')
    {
        if(!$oid || !($orderInfo = StoreOrderModel::get($oid)))
            return $this->failed('订单不存在!');
        $userInfo = User::getUserInfos($orderInfo['uid']);
        if($userInfo['spread_uid']){
            $spread = User::where('uid',$userInfo['spread_uid'])->value('nickname');
        }else{
            $spread ='';
        }
        $this->assign(compact('orderInfo','userInfo','spread'));
        return $this->fetch();
    }
    public function express($oid = '')
    {
        if(!$oid || !($order = StoreOrderModel::get($oid)))
            return $this->failed('订单不存在!');
        if($order['delivery_type'] != 'express' || !$order['delivery_id']) return $this->failed('该订单不存在快递单号!');
        $cacheName = $order['order_id'].$order['delivery_id'];
        $result = CacheService::get($cacheName,null);
        if($result === null || 1==1){
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
    /**
     * 修改配送信息
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function distribution($id){
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $f = array();
        $f[] = Form::input('order_id','退款单号',$product->getData('order_id'))->disabled(1);
        if($product['delivery_type'] == 'send'){
            $f[] = Form::input('delivery_name','送货人姓名',$product->getData('delivery_name'));
            $f[] = Form::input('delivery_id','送货人电话',$product->getData('delivery_id'));
        }else if($product['delivery_type'] == 'express'){
            $f[] = Form::select('delivery_name','快递公司',$product->getData('delivery_name'))->setOptions(function (){
                $list =  Db::name('express')->where('is_show',1)->column('id,name');
                $menus = [];
                foreach ($list as $k=>$v){
                    $menus[] = ['value'=>$v,'label'=>$v];
                }
                return $menus;
            });
            $f[] = Form::input('delivery_id','快递单号',$product->getData('delivery_id'));
        }
        $form = Form::make_post_form('配送信息',$f,Url::build('updateDistribution',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**修改配送信息
     * @param Request $request
     * @param $id
     */
    public function updateDistribution(Request $request, $id){
        $data = Util::postMore([
            'delivery_name',
            'delivery_id',
        ],$request);
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        if($product['delivery_type'] == 'send'){
            if(!$data['delivery_name']) return Json::fail('请输入送货人姓名');
            if(!(int)$data['delivery_id']) return Json::fail('请输入送货人电话号码');
            else if(!preg_match("/^1[3456789]{1}\d{9}$/",$data['delivery_id']))  return Json::fail('请输入正确的送货人电话号码');
        }else if($product['delivery_type'] == 'express'){
            if(!$data['delivery_name']) return Json::fail('请选择快递公司');
            if(!$data['delivery_id']) return Json::fail('请输入快递单号');
        }
        StoreOrderModel::edit($data,$id);
        HookService::afterListen('store_product_order_distribution',$data,$id,false,OrderBehavior::class);
        StoreOrderStatus::setStatus($id,'distribution','修改发货信息为'.$data['delivery_name'].'号'.$data['delivery_id']);
        return Json::successful('修改成功!');
    }
    /**
     * 修改退款状态
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function refund_n($id){
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $f[] = Form::input('order_id','退款单号',$product->getData('order_id'))->disabled(1);
        $f[] = Form::input('refund_reason','退款原因')->type('textarea');
        $form = Form::make_post_form('退款',$f,Url::build('updateRefundN',array('id'=>$id)),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**不退款原因
     * @param Request $request
     * @param $id
     */
    public function updateRefundN(Request $request, $id){
        $data = Util::postMore([
            'refund_reason',
        ],$request);
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        if(!$data['refund_reason']) return Json::fail('请输入退款原因');
        $data['refund_status'] = 0;
        StoreOrderModel::edit($data,$id);
        HookService::afterListen('store_product_order_refund_n',$data['refund_reason'],$id,false,OrderBehavior::class);
        StoreOrderStatus::setStatus($id,'refund_n','不退款原因:'.$data['refund_reason']);
        return Json::successful('修改成功!');
    }
    /**
     * 立即支付
     * @param $id
     */
    public function offline($id){
        $res = StoreOrderModel::updateOffline($id);
        if($res){
            try{
                HookService::listen('store_product_order_offline',$id,false,OrderBehavior::class);
            }catch (Exception $e){
                return Json::fail($e->getMessage());
            }
            StoreOrderStatus::setStatus($id,'offline','线下付款');
            return Json::successful('修改成功!');
        }else{
            return Json::fail('修改失败!');
        }
    }
    /**
     * 修改积分和金额
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function integral_back($id){
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        if($product['paid'] == 1){
            $f[] = Form::input('order_id','退款单号',$product->getData('order_id'))->disabled(1);
            $f[] = Form::number('use_integral','使用的积分',$product->getData('use_integral'))->min(0)->disabled(1);
            $f[] = Form::number('use_integrals','已退积分',$product->getData('back_integral'))->min(0)->disabled(1);
            $f[] = Form::number('back_integral','可退积分',bcsub($product->getData('use_integral'),$product->getData('use_integral')))->min(0);
            $form = Form::make_post_form('退积分',$f,Url::build('updateIntegralBack',array('id'=>$id)));
            $this->assign(compact('form'));
            return $this->fetch('public/form-builder');
        }else{
            return Json::fail('参数错误!');
        }
        return $this->fetch('public/form-builder');
    }

    /** 退积分保存
     * @param Request $request
     * @param $id
     */
    public function updateIntegralBack(Request $request, $id){
        $data = Util::postMore([
            'back_integral',
        ],$request);
        if(!$id) return $this->failed('数据不存在');
        $product = StoreOrderModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        if($data['back_integral'] <= 0) return Json::fail('请输入积分');
        if($product['use_integral'] == $product['back_integral']) return Json::fail('已退完积分!不能再积分了');
        $back_integral = $data['back_integral'];
        $data['back_integral'] = bcadd($data['back_integral'],$product['back_integral'],2);
        $bj = bccomp((float)$product['use_integral'],(float)$data['back_integral'],2);
        if($bj < 0) return Json::fail('退积分大于支付积分，请修改退积分');
        ModelBasic::beginTrans();
        $integral = User::where('uid',$product['uid'])->value('integral');
        $res1 = User::bcInc($product['uid'],'integral',$back_integral,'uid');
        $res2 = UserBill::income('商品退积分',$product['uid'],'integral','pay_product_integral_back',$back_integral,$product['id'],bcadd($integral,$back_integral,2),'订单退积分'.floatval($back_integral).'积分到用户积分');
        try{
            HookService::listen('store_order_integral_back',$product,$back_integral,false,OrderBehavior::class);
        }catch (\Exception $e){
            ModelBasic::rollbackTrans();
            return Json::fail($e->getMessage());
        }
        $res = $res1 && $res2;
        ModelBasic::checkTrans($res);
        if(!$res) return Json::fail('退积分失败!');
        StoreOrderModel::edit($data,$id);
        StoreOrderStatus::setStatus($id,'integral_back','商品退积分：'.$data['back_integral']);
        return Json::successful('退积分成功!');
    }
    public function remark(Request $request){
        $data = Util::postMore(['id','remark'],$request);
        if(!$data['id']) return Json::fail('参数错误!');
        if($data['remark'] == '')  return Json::fail('请输入要备注的内容!');
        $id = $data['id'];
        unset($data['id']);
        StoreOrderModel::edit($data,$id);
        return Json::successful('备注成功!');
    }
    public function order_status($oid){
       if(!$oid) return $this->failed('数据不存在');
       $this->assign(StoreOrderStatus::systemPage($oid));
       return $this->fetch();
    }
}
