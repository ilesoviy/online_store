<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/12
 */

namespace app\wap\controller;


use Api\Express;
use app\core\util\WechatService;
use app\wap\model\store\StoreBargain;
use app\wap\model\store\StoreBargainUser;
use app\wap\model\store\StoreBargainUserHelp;
use app\wap\model\store\StoreCouponIssue;
use app\wap\model\store\StoreCouponIssueUser;
use app\wap\model\store\StoreOrderCartInfo;
use app\wap\model\store\StorePink;
use app\wap\model\store\StoreProductReply;
use app\wap\model\store\StoreService;
use app\wap\model\store\StoreServiceLog;
use app\wap\model\store\StoreCart;
use app\wap\model\store\StoreCategory;
use app\wap\model\store\StoreCouponUser;
use app\wap\model\store\StoreOrder;
use app\wap\model\store\StoreProduct;
use app\wap\model\store\StoreProductAttr;
use app\wap\model\store\StoreProductRelation;
use app\wap\model\user\User;
use app\wap\model\store\Robot;
use app\wap\model\user\UserNum;
use app\wap\model\user\UserAddress;
use app\wap\model\user\UserBill;
use app\wap\model\user\UserExtract;
use app\wap\model\user\UserRecharge;
use app\wap\model\user\UserNotice;
use app\wap\model\user\UserSign;
use app\wap\model\user\WechatUser;
use behavior\wap\StoreProductBehavior;
use app\core\util\WechatTemplateService;
use service\CacheService;
use service\HookService;
use service\JsonService;
use app\core\util\SystemConfigService;
use app\admin\model\system\SystemConfig;
use service\UtilService;
use think\Cache;
use think\Request;
use think\Url;
use think\Session;

class AuthApi extends AuthController
{

    public function user_sign()
    {
        $signed = UserSign::checkUserSigned($this->userInfo['uid']);
        if($signed) return JsonService::fail('已签到');
        if(false !== $integral = UserSign::sign($this->userInfo))
            return JsonService::successful(L('簽到獲得 $'.floatval($integral),'Check in to get $'.floatval($integral)));
        else
            return JsonService::fail('签到失败!');
    }

    public function set_cart($productId = '',$cartNum = 1,$uniqueId = '')
    {
        if(!$productId || !is_numeric($productId)) return $this->failed(L('參數錯誤','Parameter error'));
        $res = StoreCart::setCart($this->userInfo['uid'],$productId,$cartNum,$uniqueId,'product');
        if(!$res)
            return $this->failed(StoreCart::getErrorInfo('加入购物车失败!'));
        else{
            HookService::afterListen('store_product_set_cart_after',$res,$this->userInfo,false,StoreProductBehavior::class);
            return $this->successful('ok',['cartId'=>$res->id]);
        }
    }

    public function now_buy($productId = '',$cartNum = 1,$uniqueId = '',$combinationId = 0,$secKillId=0,$bargainId = 0,$pin_type=0)
    {
        if($pin_type==1){
            $user_num=UserNum::where('user_id',$this->userInfo['uid'])->whereTime('add_time', 'today')->find();
            if($user_num && $user_num['user_num'] <=0){
                return $this->failed(L('拼團次數已用完','The number of collages has been used up'));
            }
        }
        if($productId == '') return $this->failed(L('參數錯誤','Parameter error'));
        if($bargainId && StoreBargainUserHelp::getSurplusPrice($bargainId,$this->userInfo['uid'])) return JsonService::fail('请先砍价');
        $res = StoreCart::setCart($this->userInfo['uid'],$productId,$cartNum,$uniqueId,'product',1,$combinationId,$secKillId,$bargainId,$pin_type);
        if(!$res)
            return $this->failed(StoreCart::getErrorInfo('订单生成失败!'));
        else {
            return $this->successful('ok', ['cartId' => $res->id]);
        }
    }

    public function like_product($productId = '',$category = 'product')
    {
        if(!$productId || !is_numeric($productId)) return $this->failed(L('參數錯誤','Parameter error'));
        $res = StoreProductRelation::productRelation($productId,$this->userInfo['uid'],'like',$category);
        if(!$res)
            return $this->failed(StoreProductRelation::getErrorInfo('点赞失败!'));
        else
            return $this->successful();
    }

    public function unlike_product($productId = '',$category = 'product')
    {

        if(!$productId || !is_numeric($productId)) return $this->failed(L('參數錯誤','Parameter error'));
        $res = StoreProductRelation::unProductRelation($productId,$this->userInfo['uid'],'like',$category);
        if(!$res)
            return $this->failed(StoreProductRelation::getErrorInfo('取消点赞失败!'));
        else
            return $this->successful();
    }

    public function collect_product($productId,$category = 'product')
    {
        if(!$productId || !is_numeric($productId)) return $this->failed(L('參數錯誤','Parameter error'));
        $res = StoreProductRelation::productRelation($productId,$this->userInfo['uid'],'collect',$category);
        if(!$res)
            return $this->failed(StoreProductRelation::getErrorInfo('收藏失败!'));
        else
            return $this->successful();
    }

    public function uncollect_product($productId,$category = 'product')
    {
        if(!$productId || !is_numeric($productId)) return $this->failed(L('參數錯誤','Parameter error'));
        $res = StoreProductRelation::unProductRelation($productId,$this->userInfo['uid'],'collect',$category);
        if(!$res)
            return $this->failed(StoreProductRelation::getErrorInfo('取消收藏失败!'));
        else
            return $this->successful();
    }

    public function get_cart_num()
    {
        return JsonService::successful('ok',StoreCart::getUserCartNum($this->userInfo['uid'],'product'));
    }

    public function get_cart_list()
    {
        return JsonService::successful('ok',StoreCart::getUserProductCartList($this->userInfo['uid']));
    }

    public function change_cart_num($cartId = '',$cartNum = '')
    {
        if(!$cartId || !$cartNum || !is_numeric($cartId) || !is_numeric($cartNum)) return JsonService::fail(L('參數錯誤','Parameter error'));
        StoreCart::changeUserCartNum($cartId,$cartNum,$this->userInfo['uid']);
        return JsonService::successful();
    }

    public function remove_cart($ids='')
    {
        if(!$ids) return JsonService::fail(L('參數錯誤','Parameter error'));
        StoreCart::removeUserCart($this->userInfo['uid'],$ids);
        return JsonService::successful();
    }


    public function get_use_coupon()
    {
        return JsonService::successful('',StoreCouponUser::getUserValidCoupon($this->userInfo['uid']));
    }

    public function get_user_collect_product($first = 0,$limit = 8)
    {
        $productList = StoreProductRelation::getProductRelation($this->userInfo['uid'], $first, $limit);
        $seckillList = StoreProductRelation::getSeckillRelation($this->userInfo['uid'], $first, $limit);
        $sort = [];
        $list = [];
        foreach ($productList as $key=>&$product){
            if($product['pid']){
                $product['is_fail'] = $product['is_del'] && $product['is_show'];
                $sort[] = $product['add_time'];
                array_push($list,$product);
            }else{
                unset($productList[$key]);
            }
        }
        foreach ($seckillList as $key=>&$seckill){
            if($seckill['pid']){
                $seckill['is_fail'] = $seckill['is_del'] && $seckill['is_show'];
                $sort[] = $seckill['add_time'];
                array_push($list,$seckill);
            }else{
                unset($seckillList[$key]);
            }
        }
        array_multisort($sort,SORT_DESC,SORT_NUMERIC,$list);
        return JsonService::successful($list);
    }

    public function remove_user_collect_product($productId = '')
    {
        if(!$productId || !is_numeric($productId)) return JsonService::fail(L('參數錯誤','Parameter error'));
        StoreProductRelation::unProductRelation($productId,$this->userInfo['uid'],'collect','product');
        return JsonService::successful();
    }

    public function set_user_default_address($addressId = '')
    {
        if(!$addressId || !is_numeric($addressId)) return JsonService::fail(L('參數錯誤','Parameter error'));
        if(!UserAddress::be(['is_del'=>0,'id'=>$addressId,'uid'=>$this->userInfo['uid']]))
            return JsonService::fail('地址不存在!');
        $res = UserAddress::setDefaultAddress($addressId,$this->userInfo['uid']);
        if(!$res)
            return JsonService::fail('地址不存在!');
        else
            return JsonService::successful();
    }

    public function edit_user_address()
    {
        $request = Request::instance();
        if(!$request->isPost()) return JsonService::fail(L('參數錯誤','Parameter error'));
        $addressInfo = UtilService::postMore([
            ['address',[]],
            ['is_default',false],
            ['real_name',''],
            ['post_code',''],
            ['phone',''],
            ['detail',''],
            ['newaddress',''],
            ['id',0]
        ],$request);
        // dump($addressInfo);die;
        $addressInfo['province'] = $addressInfo['newaddress'];
        $addressInfo['city'] = $addressInfo['address']['city'];
        $addressInfo['district'] = $addressInfo['address']['district'];
        $addressInfo['is_default'] = $addressInfo['is_default'] == true ? 1 : 0;
        $addressInfo['uid'] = $this->userInfo['uid'];
        unset($addressInfo['address']);

        if($addressInfo['id'] && UserAddress::be(['id'=>$addressInfo['id'],'uid'=>$this->userInfo['uid'],'is_del'=>0])){
            $id = $addressInfo['id'];
            unset($addressInfo['id']);
            if(UserAddress::edit($addressInfo,$id,'id')){
                if($addressInfo['is_default'])
                    UserAddress::setDefaultAddress($id,$this->userInfo['uid']);
                return JsonService::successful();
            }else
                return JsonService::fail('编辑收货地址失败!');
        }else{
            if($address = UserAddress::set($addressInfo)){
                if($addressInfo['is_default'])
                    UserAddress::setDefaultAddress($address->id,$this->userInfo['uid']);
                return JsonService::successful();
            }else
                return JsonService::fail('添加收货地址失败!');
        }


    }

    public function user_default_address()
    {
        $defaultAddress = UserAddress::getUserDefaultAddress($this->userInfo['uid'],'id,real_name,phone,province,city,district,detail,is_default');
        if($defaultAddress)
            return JsonService::successful('ok',$defaultAddress);
        else
            return JsonService::successful('empty',[]);
    }

    public function remove_user_address($addressId = '')
    {
        if(!$addressId || !is_numeric($addressId)) return JsonService::fail(L('參數錯誤','Parameter error'));
        if(!UserAddress::be(['is_del'=>0,'id'=>$addressId,'uid'=>$this->userInfo['uid']]))
            return JsonService::fail('地址不存在!');
        if(UserAddress::edit(['is_del'=>'1'],$addressId,'id'))
            return JsonService::successful();
        else
            return JsonService::fail('删除地址失败!');
    }

    /**
     * 创建订单
     * @param string $key
     * @return \think\response\Json
     */
    public function create_order($key = '')
    {
        if(!$key) return JsonService::fail(L('參數錯誤','Parameter error'));
        if(StoreOrder::be(['order_id|unique'=>$key,'uid'=>$this->userInfo['uid'],'is_del'=>0]))
            return JsonService::status('extend_order','订单已生成',['orderId'=>$key,'key'=>$key]);
        list($addressId,$couponId,$payType,$useIntegral,$mark,$combinationId,$pinkId,$seckill_id,$bargainId,$pin_type) = UtilService::postMore([
            'addressId','couponId','payType','useIntegral','mark',['combinationId',0],['pinkId',0],['seckill_id',0],['bargainId',0],['pin_type',0]
        ],Request::instance(),true);
        // dump($pin_type);die;
        $payType = strtolower($payType);
        if($bargainId) StoreBargainUser::setBargainUserStatus($bargainId,$this->userInfo['uid']);//修改砍价状态
        if($pinkId) if(StorePink::getIsPinkUid($pinkId)) return JsonService::status('ORDER_EXIST','订单生成失败，你已经在该团内不能再参加了',['orderId'=>StoreOrder::getStoreIdPink($pinkId)]);
        if($pinkId) if(StoreOrder::getIsOrderPink($pinkId)) return JsonService::status('ORDER_EXIST','订单生成失败，你已经参加该团了，请先支付订单',['orderId'=>StoreOrder::getStoreIdPink($pinkId)]);
        $order = StoreOrder::cacheKeyCreateOrder($this->userInfo['uid'],$key,$addressId,$payType,$useIntegral,$couponId,$mark,$combinationId,$pinkId,$seckill_id,$bargainId,$pin_type);
        $orderId = $order['order_id'];
        $info = compact('orderId','key');
        if($orderId){
            if($payType == 'weixin'){
                $orderInfo = StoreOrder::where('order_id',$orderId)->find();
                if(!$orderInfo || !isset($orderInfo['paid'])) exception(L('支付訂單不存在','Payment order does not exist'));
                if($orderInfo['paid']) exception(L('支付已支付！','Payment paid!'));
                if(bcsub((float)$orderInfo['pay_price'],0,2) <= 0){
                    if(StoreOrder::jsPayPrice($orderId,$this->userInfo['uid']))
                        return JsonService::status('success',L('微信支付成功','Wechat payment succeeded'),$info);
                    else
                        return JsonService::status('pay_error',StoreOrder::getErrorInfo());
                }else{
                    try{
                        $jsConfig = StoreOrder::jsPay($orderId);
                    }catch (\Exception $e){
                        return JsonService::status('pay_error',$e->getMessage(),$info);
                    }
                    $info['jsConfig'] = $jsConfig;
                    return JsonService::status('wechat_pay',L('訂單創建成功','Order created successfully'),$info);
                }
            }else if($payType == 'yue'){
                if(StoreOrder::yuePay($orderId,$this->userInfo['uid']))
                    return JsonService::status('success',L('餘額支付成功','Balance payment succeeded'),$info);
                else
                    return JsonService::status('pay_error',StoreOrder::getErrorInfo());
            }else if($payType == 'offline'){
                StoreOrder::createOrderTemplate($order);
                return JsonService::status('success',L('訂單創建成功','Order created successfully'),$info);
            }
        }else{
            return JsonService::fail(StoreOrder::getErrorInfo(L('訂單生成失敗！','Order generation failed!')));
        }
    }

    public function get_user_order_list($type = '',$first = 0, $limit = 8,$search = '',$pin_type=0)
    {
//        StoreOrder::delCombination();//删除拼团未支付订单

        $type=='null' && $type='';
        if($search){
            $order = StoreOrder::searchUserOrder($this->userInfo['uid'],$search,$pin_type)?:[];
            $list = $order == false ? [] : [$order];
        }else{
            if(!is_numeric($type)) $type = '';
            $list = StoreOrder::getUserOrderList($this->userInfo['uid'],$type,$first,$limit,$pin_type);
        }
        // dump($list);die;
        foreach ($list as $k=>$order){
            $list[$k] = StoreOrder::tidyOrder($order,true);
            if($list[$k]['_status']['_type'] == 3){
                foreach ($order['cartInfo']?:[] as $key=>$product){
                    $list[$k]['cartInfo'][$key]['is_reply'] = StoreProductReply::isReply($product['unique'],'product');
                }
            }
        }

        return JsonService::successful($list);
    }

    public function user_remove_order($uni = '')
    {
        if(!$uni) return JsonService::fail(L('參數錯誤','Parameter error'));
        $res = StoreOrder::removeOrder($uni,$this->userInfo['uid']);
        if($res)
            return JsonService::successful();
        else
            return JsonService::fail(StoreOrder::getErrorInfo());
    }
  //发送curl 默认get请求 如果$data不为空则是post请求
    public function https_request($url, $data = null)
    {
        $data = json_encode($data);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $AjaxReturn = curl_exec($curl);
        //获取access_token和openid,转换为数组
        $data = json_decode($AjaxReturn, true);
        curl_close($curl);
        return $data;
    }


    /**
     * 支付订单
     * @param string $uni
     * @return \think\response\Json
     */
    public function pay_order($uni = '')
    {  

        if(!$uni) return JsonService::fail(L('參數錯誤','Parameter error'));
        $order= StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni);
        if(!$order) return JsonService::fail('订单不存在!');
        if($order['paid']) return JsonService::fail('该订单已支付!');
        if($order['pink_id']) if(StorePink::isPinkStatus($order['pink_id'])) return JsonService::fail('该订单已失效!');
        if($order['pay_type'] == 'weixin'){
            try{
                $jsConfig = StoreOrder::jsPay($order);
            }catch (\Exception $e){
                if($e->getMessage()=="微信支付错误: ORDERPAID  原因:该订单已支付"){
                     StoreOrder::paySuccess($uni);
                     return JsonService::successful('该订单已支付');
                }else if($e->getMessage()=="微信支付错误: INVALID_REQUEST  原因:201 商户订单号重复"){
                    return JsonService::fail('该订单已失效，请重新下单');
                }
                return JsonService::fail($e->getMessage());
            }
            
        // $this->https_request($jsConfig);
            return JsonService::status('wechat_pay',['jsConfig'=>$jsConfig,'order_id'=>$order['order_id']]);
        }else if($order['pay_type'] == 'yue'){
            if($res = StoreOrder::yuePay($order['order_id'],$this->userInfo['uid']))
                return JsonService::successful(L('餘額支付成功','Balance payment succeeded'));
            else
                return JsonService::fail(StoreOrder::getErrorInfo());
        }else if($order['pay_type'] == 'offline'){
            StoreOrder::createOrderTemplate($order);
            return JsonService::successful('订单创建成功');
        }
    }
   
    public function apply_order_refund($uni = '',$text = '')
    {
        if(!$uni || $text == '') return JsonService::fail(L('參數錯誤','Parameter error'));
        $res = StoreOrder::orderApplyRefund($uni,$this->userInfo['uid'],$text);
        if($res)
            return JsonService::successful();
        else
            return JsonService::fail(StoreOrder::getErrorInfo());
    }

    public function user_take_order($uni = '')
    {
        if(!$uni) return JsonService::fail(L('參數錯誤','Parameter error'));

        $res = StoreOrder::takeOrder($uni,$this->userInfo['uid']);
        if($res)
            return JsonService::successful();
        else
            return JsonService::fail(StoreOrder::getErrorInfo());
    }

    public function user_wechat_recharge($price = 0)
    {
        if(!$price || $price <=0) return JsonService::fail('参数错误');
        $storeMinRecharge = SystemConfigService::get('store_user_min_recharge');
        if($price < $storeMinRecharge) return JsonService::fail('充值金额不能低于'.$storeMinRecharge);
        $rechargeOrder = UserRecharge::addRecharge($this->userInfo['uid'],$price);
        if(!$rechargeOrder) return JsonService::fail('充值订单生成失败!');
        // try{
            return JsonService::successful(UserRecharge::jsPay($rechargeOrder));
            // return UserRecharge::jsPay($rechargeOrder);
        // }catch (\Exception $e){
            // return JsonService::fail($e->getMessage());
        // }
    }

    public function user_balance_list($first = 0,$limit = 8)
    {   

        if (!Session::has('lang')) {
            $lang='en_';
            }else{
              $lang='';  
            }
        $list = UserBill::where('uid',$this->userInfo['uid'])->where('category','now_money')
            ->field($lang.'mark as new_mark,pm,number,add_time')->order('add_time DESC')->limit($first,$limit)->select()->toArray();
        foreach ($list as &$v){
            $v['add_time'] = date('Y/m/d H:i',$v['add_time']);
        }
        return JsonService::successful($list);
    }

    public function user_integral_list($first = 0,$limit = 8,$to='')
    {
        if (!Session::has('lang')) {
            $lang='en_';
            }else{
              $lang='';  
            }
            if($to){
                $list = UserBill::where('uid',$this->userInfo['uid'])->where('category','integral')->whereTime('add_time','today')
        ->field($lang.'mark as new_mark,pm,number,add_time,en_mark')->order('add_time DESC')->limit($first,$limit)->select()->toArray();
    }else{
        $list = UserBill::where('uid',$this->userInfo['uid'])->where('category','integral')
        ->field($lang.'mark as new_mark,pm,number,add_time,en_mark')->order('add_time DESC')->limit($first,$limit)->select()->toArray();
    }
        
        foreach ($list as &$v){
            $v['add_time'] = date('Y/m/d H:i',$v['add_time']);
            $v['number'] = floatval($v['number']);
        }
        return JsonService::successful($list);

    }
//提现
    public function user_withdrawal_list($first = 0,$limit = 8)
    {
        if (!Session::has('lang')) {
            $lang='en_';
            }else{
              $lang='';  
            }
        $list = UserBill::where('uid',$this->userInfo['uid'])->where('category','now_money')->where('type','extract')
        ->field($lang.'mark as new_mark,pm,number,add_time,en_mark')
        ->where('status',1)->order('add_time DESC')->limit($first,$limit)->select()->toArray();
        foreach ($list as &$v){
            $v['add_time'] = date('Y/m/d H:i',$v['add_time']);
            $v['number'] = floatval($v['number']);
        }
        return JsonService::successful($list);

    }

    public function user_comment_product($unique = '')
    {
        if(!$unique) return JsonService::fail(L('參數錯誤','Parameter error'));
        $cartInfo = StoreOrderCartInfo::where('unique',$unique)->find();
        $uid = $this->userInfo['uid'];
        if(!$cartInfo || $uid != $cartInfo['cart_info']['uid']) return JsonService::fail('评价产品不存在!');
        if(StoreProductReply::be(['oid'=>$cartInfo['oid'],'unique'=>$unique]))
            return JsonService::fail('该产品已评价!');
        $group = UtilService::postMore([
            ['comment',''],['pics',[]],['product_score',5],['service_score',5]
        ],Request::instance());
        $group['comment'] = htmlspecialchars(trim($group['comment']));
        if(sensitive_words_filter($group['comment'])) return JsonService::fail('请注意您的用词，谢谢！！');
        if($group['product_score'] < 1) return JsonService::fail('请为产品评分');
        else if($group['service_score'] < 1) return JsonService::fail('请为商家服务评分');
        $group = array_merge($group,[
            'uid'=>$uid,
            'oid'=>$cartInfo['oid'],
            'unique'=>$unique,
            'product_id'=>$cartInfo['product_id'],
            'reply_type'=>'product'
        ]);
        StoreProductReply::beginTrans();
        $res = StoreProductReply::reply($group,'product');
        if(!$res) {
            StoreProductReply::rollbackTrans();
            return JsonService::fail('评价失败!');
        }
        try{
            HookService::listen('store_product_order_reply',$group,$cartInfo,false,StoreProductBehavior::class);
        }catch (\Exception $e){
            StoreProductReply::rollbackTrans();
            return JsonService::fail($e->getMessage());
        }
        StoreProductReply::commitTrans();
        return JsonService::successful();
    }

    public function get_product_category()
    {
        $parentCategory = StoreCategory::pidByCategory(0,'id,cate_name')->toArray();
        foreach ($parentCategory as $k=>$category){
            $category['child'] = StoreCategory::pidByCategory($category['id'],'id,cate_name')->toArray();
            $parentCategory[$k] = $category;
        }
        return JsonService::successful($parentCategory);
    }

    public function get_spread_list($first = 0,$limit = 20)
    {
        $list = User::where('spread_uid',$this->userInfo['uid'])->field('uid,nickname,avatar,add_time')->limit($first,$limit)->order('add_time DESC')->select()->toArray();
        foreach ($list as $k=>$user){
            $list[$k]['add_time'] = date('Y/m/d',$user['add_time']);
        }
        return JsonService::successful($list);
    }

    public function get_product_list($keyword = '', $cId = 0,$sId = 0,$priceOrder = '', $salesOrder = '', $news = 0, $first = 0, $limit = 8,$recommend=0)
    {
         if (!Session::has('lang')) {
            $lang='en_';
            }else{
              $lang='';  
            }
        if(!empty($keyword)){
            $encodedData = str_replace(' ','+',$keyword);
            $keyword = base64_decode(htmlspecialchars($encodedData));
            //关键词处理
        db('keyword')->insert(['uid'=>$this->userInfo['uid'],'name'=>$keyword,'add_time'=>time()]);
        }
        
        $model = StoreProduct::validWhere();
        if($cId){
            $sids = StoreCategory::pidBySidList($cId);
            $sids[] = $cId;
            if($sId) $sids[] = $sId;
            $sId = implode(',',$sids);
        }
        if($sId){
            $product_ids = \think\Db::name('store_product_cate')->where('cate_id','IN',$sId)->column('product_id');
            if(count($product_ids))
                $model=$model->where('id',"in",$product_ids);
            else
                $model=$model->where('cate_id',-1);
        }
        if($recommend==1){
            $model=$model->where('is_hot',1);
        }
        if($recommend==2){
            $model=$model->where('is_benefit',1);
        }
        if(!empty($keyword)) $model->where('keyword|store_name|en_store_name','LIKE',"%$keyword%");
        if($news) $model->where('is_new',1);
        $baseOrder = '';
        if($priceOrder) $baseOrder = $priceOrder == 'desc' ? 'price DESC' : 'price ASC';
//        if($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';
        if($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'ficti DESC' : 'ficti ASC';
        if($baseOrder) $baseOrder .= ', ';
        $model->order($baseOrder.'sort DESC, add_time DESC');
        $list = $model->limit($first,$limit)->field('id,'.$lang.'store_name as new_store_name,image,sales,ficti,price,stock')->select()->toArray();
        if($list) setView($this->uid,0,$sId,'search','product',$keyword);
        return JsonService::successful($list);
    }

    public function user_get_coupon($couponId = '')
    {
        if(!$couponId || !is_numeric($couponId)) return JsonService::fail(L('參數錯誤','Parameter error'));
        if(StoreCouponIssue::issueUserCoupon($couponId,$this->userInfo['uid'])){
            return JsonService::successful('领取成功');
        }else{
            return JsonService::fail(StoreCouponIssue::getErrorInfo('领取失败!'));
        }
    }

    public function product_reply_list($productId = '',$first = 0,$limit = 8, $filter = 'all')
    {
        if(!$productId || !is_numeric($productId)) return JsonService::fail(L('參數錯誤','Parameter error'));
        $list = StoreProductReply::getProductReplyList($productId,$filter,$first,$limit);
        return JsonService::successful($list);
    }

    public function product_attr_detail($productId = '')
    {
        if(!$productId || !is_numeric($productId)) return JsonService::fail(L('參數錯誤','Parameter error'));
        list($productAttr,$productValue) = StoreProductAttr::getProductAttrDetail($productId);
        return JsonService::successful(compact('productAttr','productValue'));

    }

    public function user_address_list()
    {
        $list = UserAddress::getUserValidAddressList($this->userInfo['uid'],'id,real_name,phone,province,city,district,detail,is_default');
        return JsonService::successful($list);
    }

    public function get_notice_list($page = 0, $limit = 8)
    {
        $list = UserNotice::getNoticeList($this->userInfo['uid'],$page,$limit);
        return JsonService::successful($list);
    }
    public function see_notice($nid){
        UserNotice::seeNotice($this->userInfo['uid'],$nid);
        return JsonService::successful();
    }

    public function refresh_msn(Request $request)
    {
        $params = $request->post();
        $remind_where = "mer_id = ".$params["mer_id"]." AND uid = ".$params["uid"]." AND to_uid = ".$params["to_uid"]." AND type = 0 AND remind = 0";
        $remind_list = StoreServiceLog::where($remind_where)->order("add_time asc")->select();
        foreach ($remind_list as $key => $value) {
            if(time() - $value["add_time"] > 3){
                StoreServiceLog::edit(array("remind"=>1),$value["id"]);
                $now_user = StoreService::field("uid,nickname")->where(array("uid"=>$params["uid"]))->find();
                if(!$now_user)$now_user = User::field("uid,nickname")->where(array("uid"=>$params["uid"]))->find();
                if($params["to_uid"]) {
                    $userInfo = WechatUser::where('uid',$params["to_uid"])->field('nickname,subscribe,openid,headimgurl')->find();
                    $head = '客服提醒';
                    $description = '您有新的消息，请注意查收！';
                    $url = Url::build('service/service_ing',['to_uid'=>$now_user["uid"],'mer_id'=>$params["mer_id"]],true,true);
                    $message = WechatService::newsMessage($head,$description,$url,$userInfo['headimgurl']);
                    if($userInfo){
                        $userInfo = $userInfo->toArray();
                        if($userInfo['subscribe'] && $userInfo['openid']){
                            try {
                                WechatService::staffService()->message($message)->to($userInfo['openid'])->send();
                            } catch (\Exception $e) {
                                $errorLog = $userInfo['nickname'].'发送失败'.$e->getMessage();
                            }
                        }
                    }
//                    WechatTemplateService::sendTemplate(WechatUser::uidToOpenid($params["to_uid"]),WechatTemplateService::SERVICE_NOTICE,[
//                        'first'=>$head,
//                        'keyword1'=>$now_user["nickname"],
//                        'keyword2'=>"客服提醒",
//                        'keyword3'=> preg_replace('/<img.*? \/>/','[图片]',$value["msn"]),
//                        'keyword4'=>date('Y-m-d H:i:s',time()),
//                        'remark'=>'点击立即查看消息'
//                    ],Url::build('service/service_ing',['to_uid'=>$now_user["uid"],'mer_id'=>$params["mer_id"]],true,true));
                }
            }
        }
        $where = "mer_id = ".$params["mer_id"]." AND uid = ".$params["to_uid"]." AND to_uid = ".$params["uid"]." AND type = 0";
        $list = StoreServiceLog::where($where)->order("add_time asc")->select()->toArray();
        $ids = [];
        foreach ($list as $key => $value) {
            //设置发送人与接收人区别
            if($value["uid"] == $params["uid"])
                $list[$key]['my'] = "my";
            else
                $list[$key]['my'] = "to";

            array_push($ids,$value["id"]);
        }

        //设置这些消息为已读
        StoreServiceLog::where(array("id"=>array("in",$ids)))->update(array("type"=>1,"remind"=>1));
        return JsonService::successful($list);
    }

    public function add_msn(Request $request){
        $params = $request->post();
        if($params["type"] == "html")
            $data["msn"] = htmlspecialchars_decode($params["msn"]);
        else
            $data["msn"] = $params["msn"];
        $data["uid"] = $params["uid"];
        $data["to_uid"] = $params["to_uid"];
        $data["mer_id"] = $params["mer_id"] > 0 ? $params["mer_id"] : 0;
        $data["add_time"] = time();
        StoreServiceLog::set($data);
        return JsonService::successful();
    }

    public function get_msn(Request $request){
        $params = $request->post();
        $size = 10;
        $page = $params["page"]>=0 ? $params["page"] : 1;
        $where = "(mer_id = ".$params["mer_id"]." AND uid = ".$params["uid"]." AND to_uid = ".$params["to_uid"].") OR (mer_id = ".$params["mer_id"]." AND uid = ".$params["to_uid"]." AND to_uid = ".$params["uid"].")";
        $list = StoreServiceLog::where($where)->limit(($page-1)*$size,$size)->order("add_time desc")->select()->toArray();
        foreach ($list as $key => $value) {
            //设置发送人与接收人区别
            if($value["uid"] == $params["uid"])
                $list[$key]['my'] = "my";
            else
                $list[$key]['my'] = "to";

            //设置这些消息为已读
            if($value["uid"] == $params["to_uid"] && $value["to_uid"] == $params["uid"])StoreServiceLog::edit(array("type"=>1,"remind"=>1),$value["id"]);
        }
        $list=array_reverse($list);
        return JsonService::successful($list);
    }

    public function refresh_msn_new(Request $request){
        $params = $request->post();
        $now_user = User::getUserInfo($this->userInfo['uid']);
        if($params["last_time"] > 0)
            $where = "(uid = ".$now_user["uid"]." OR to_uid = ".$now_user["uid"].") AND add_time>".$params["last_time"];
        else
            $where = "uid = ".$now_user["uid"]." OR to_uid = ".$now_user["uid"];


        $msn_list = StoreServiceLog::where($where)->order("add_time desc")->select()->toArray();
        $info_array = $list = [];
        foreach ($msn_list as $key => $value){
            $to_uid = $value["uid"] == $now_user["uid"] ? $value["to_uid"] : $value["uid"];
            if(!in_array(["to_uid"=>$to_uid,"mer_id"=>$value["mer_id"]],$info_array)){
                $info_array[count($info_array)] = ["to_uid"=>$to_uid,"mer_id"=>$value["mer_id"]];

                $to_user = StoreService::field("uid,nickname,avatar")->where(array("uid"=>$to_uid))->find();
                if(!$to_user)$to_user = User::field("uid,nickname,avatar")->where(array("uid"=>$to_uid))->find();
                $to_user["mer_id"] = $value["mer_id"];
                $to_user["mer_name"] = '';
                $value["to_info"] = $to_user;
                $value["count"] = StoreServiceLog::where(array("mer_id"=>$value["mer_id"],"uid"=>$to_uid,"to_uid"=>$now_user["uid"],"type"=>0))->count();
                $list[count($list)] = $value;
            }
        }
        return JsonService::successful($list);
    }

    public function get_user_brokerage_list($uid, $first = 0,$limit = 8)
    {
        if(!$uid)
            return $this->failed('用户不存在');
        $list = UserBill::field('A.mark,A.add_time,A.number,A.pm')->alias('A')->limit($first,$limit)
            ->where('A.category','now_money')->where('A.type','brokerage')
            ->where('A.uid',$this->userInfo['uid'])
            ->join('__STORE_ORDER__ B','A.link_id = B.id AND B.uid = '.$uid)->select()->toArray();
        return JsonService::successful($list);
    }

    public function user_extract()
    {
        if(UserExtract::userExtract($this->userInfo,UtilService::postMore([
            ['type','','','extract_type'],'real_name','alipay_code','type_ti','service_charge','bank_code','bank_address',['price','','','extract_price']
        ])))
            return JsonService::successful('申请提现成功!');
        else
            return JsonService::fail(Extract::getErrorInfo());
    }

    public function get_issue_coupon_list($limit = 2)
    {
        $list = StoreCouponIssue::validWhere('A')->join('__STORE_COUPON__ B','A.cid = B.id')
            ->field('A.*,B.coupon_price,B.use_min_price')->order('B.sort DESC,A.id DESC')->limit($limit)->select()->toArray()?:[];
        $list_coupon=[];
        foreach ($list as $k=>&$v){
            if(!($v['is_use']=StoreCouponIssueUser::be(['uid'=>$this->userInfo['uid'],'issue_coupon_id'=>$v['id']]))){
                if($v['is_permanent'] == 0 && $v['total_count'] > 0 && $v['remain_count'] >0){
                    array_push($list_coupon,$v);
                }else if($v['is_permanent'] == 1){
                    array_push($list_coupon,$v);
                }
            }
        }
        return JsonService::successful($list_coupon);
    }

    public function clear_cache($uni = '')
    {
        if($uni)CacheService::clear();
    }

    /**
     * 获取今天正在拼团的人的头像和名称
     * @return \think\response\Json
     */
    public function get_pink_second_one(){
        $addTime =  mt_rand(time()-30000,time());
        $storePink = StorePink::where('p.add_time','GT',$addTime)->alias('p')->where('p.status',1)->join('User u','u.uid=p.uid')->field('u.nickname,u.avatar as src')->find();
        return JsonService::successful($storePink);
    }

    public function order_details($uni = ''){

        if(!$uni) return JsonService::fail(L('參數錯誤','Parameter error'));
        $order = StoreOrder::getUserOrderDetail($this->userInfo['uid'],$uni);
        if(!$order) return JsonService::fail('订单不存在!');
        $order = StoreOrder::tidyOrder($order,true);
        $res = array();
        foreach ($order['cartInfo'] as $v) {
            if($v['combination_id']) return JsonService::fail('拼团产品不能再来一单，请在拼团产品内自行下单!');
            else  $res[] = StoreCart::setCart($this->userInfo['uid'], $v['product_id'], $v['cart_num'], isset($v['productInfo']['attrInfo']['unique']) ? $v['productInfo']['attrInfo']['unique'] : '', 'product', 0, 0);
        }
        $cateId = [];
        foreach ($res as $v){
            if(!$v) return JsonService::fail('再来一单失败，请重新下单!');
            $cateId[] = $v['id'];
        }
        return JsonService::successful('ok',implode(',',$cateId));

    }


    /**
     * 帮好友砍价
     * @param int $bargainId
     * @param int $bargainUserId
     * @return \think\response\Json
     */
    public function set_bargain_help(){
        list($bargainId,$bargainUserId) = UtilService::postMore([
            'bargainId','bargainUserId'],Request::instance(),true);
        if(!$bargainId || !$bargainUserId) return JsonService::fail('参数错误');
        $res = StoreBargainUserHelp::setBargainUserHelp($bargainId,$bargainUserId,$this->userInfo['uid']);
        if($res) {
            if(!StoreBargainUserHelp::getSurplusPrice($bargainId,$bargainUserId)){//砍价成功，发模板消息
                $bargainUserTableId = StoreBargainUser::getBargainUserTableId($bargainId,$bargainUserId);
                $bargain = StoreBargain::where('id',$bargainId)->find()->toArray();
                $bargainUser = StoreBargainUser::where('id',$bargainUserTableId)->find()->toArray();
            }
            return JsonService::status('SUCCESS','砍价成功');
        }
        else return JsonService::status('ERROR','砍价失败，请稍后再帮助朋友砍价');
    }

    /**
     * 砍价分享添加次数
     * @param int $bargainId
     */
    public function add_bargain_share($bargainId = 0){
        if(!$bargainId) return JsonService::successful();
        StoreBargain::addBargainShare($bargainId);
        return JsonService::successful();
    }

    /**
     * 定时让机器人加入拼团
     */
    public function add_robot(){
        
        $order=StoreOrder::where(['paid'=>1,'pin_type'=>1,'pin_status'=>1,'uid'=>$this->userInfo['uid']])->select();
        if($order){
            foreach ($order as $key => $value) {
                Robot::JoinRobot($value['order_id']);
            }
           
        }
        return JsonService::successful();
    }
    /**
     * 定时拼团成功与否
     */
    public function is_robot(){

        $spell_time=(int)SystemConfig::getValue('spell_time');
        $spell_time=time()-$spell_time*60;
        $order=StoreOrder::where(['add_time'=>['<',$spell_time],'paid'=>1,'pin_type'=>1,'pin_status'=>1,'uid'=>$this->userInfo['uid']])->select();

        if($order){
            foreach ($order as $key => $value) {
                Robot::Probability($value['order_id']);
            }
            
        }
        return JsonService::successful(1);

    }
     /**
     * 定时反佣金和余额
     */
    public function return_money(){
        $integral_withdrawal_time=time()-(int)SystemConfig::getValue('integral_withdrawal_time')*86400000;
        $now_money_success_time=time()-(int)SystemConfig::getValue('now_money_success_time')*86400000;
        $now_money_fail_time=time()-(int)SystemConfig::getValue('now_money_fail_time')*86400000;
        //加入佣金
        $integral_info=UserBill::where('category','integral')->where('uid',$this->userInfo['uid'])->where('status',0)->where(['add_time'=>['<',$integral_withdrawal_time]])->select();
        if(count($integral_info)>0){
            foreach ($integral_info as $key => $value) {
                $user=User::where('uid',$value['uid'])->find();
                if($user){
                    User::bcInc($value['uid'],'integral',$value['number'],'uid');
                    UserBill::where(['id'=>$value['id']])->update(['balance'=>bcadd($user['integral'],$value['number'],2),'status'=>1,'qr_add_time'=>time()]);
                }
            }
        }
        //拼团成功
        $money_success_info=UserBill::where('category','now_money')->where('uid',$this->userInfo['uid'])->where('type','pin_success')->where('status',0)->where(['add_time'=>['<',$now_money_success_time]])->select();
        if(count($money_success_info)>0){
            foreach ($money_success_info as $key => $value) {
                $user=User::where('uid',$value['uid'])->find();
                if($user){
                    User::bcInc($value['uid'],'now_money',$value['number'],'uid');
                    UserBill::where(['id'=>$value['id']])->update(['balance'=>bcadd($user['now_money'],$value['number'],2),'status'=>1,'qr_add_time'=>time()]);
                }
            }
        }
        //拼团失败
        //拼团成功
        $money_fail_info=UserBill::where('category','now_money')->where('uid',$this->userInfo['uid'])->where('type','pin_fail')->where('status',0)->where(['add_time'=>['<',$now_money_success_time]])->select();
        if(count($money_fail_info)>0){
            foreach ($money_fail_info as $key => $value) {
                $user=User::where('uid',$value['uid'])->find();
                if($user){
                    User::bcInc($value['uid'],'now_money',$value['number'],'uid');
                    UserBill::where(['id'=>$value['id']])->update(['balance'=>bcadd($user['now_money'],$value['number'],2),'status'=>1,'qr_add_time'=>time()]);
                }
            }
        }


        return JsonService::successful(1);
    }

   /**
     * 判断是否登录
     */
    public function is_logins(){
        return JsonService::fail($this->userInfo['uid']);
        if($this->userInfo['uid']){
            return JsonService::successful(1);
        }else{
            return JsonService::fail(L('暫未登入','Not logged in yet'));
        }
    }


}