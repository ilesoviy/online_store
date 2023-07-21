<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/28
 */

namespace app\admin\model\wechat;


use app\admin\model\order\StoreOrder;
use app\admin\model\user\User;
use app\admin\model\user\UserBill;
use app\admin\model\user\UserExtract;
use service\ExportService;
use app\core\util\QrcodeService;
use think\Cache;
use think\Config;
use traits\ModelTrait;
use basic\ModelBasic;
use app\core\util\WechatService;
use service\PHPExcelService;
use app\core\util\SystemConfigService;

/**
 * 微信用户 model
 * Class WechatUser
 * @package app\admin\model\wechat
 */
 class WechatUser extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

     /**
      * 用uid获得 微信openid
      * @param $uid
      * @return mixed
      */
     public static function uidToOpenid($uid,$update = false)
     {
         $cacheName = 'openid_'.$uid;
         $openid = Cache::get($cacheName);
         if($openid && !$update) return $openid;
         $openid = self::where('uid',$uid)->value('openid');
         if(!$openid) exception('对应的openid不存在!');
         Cache::set($cacheName,$openid,0);
         return $openid;
     }
     /**
      * 用uid获得 小程序 openid
      * @param $uid
      * @return mixed
      */
     public static function uidToRoutineOpenid($uid,$update = false)
     {
         $cacheName = 'routine_openid'.$uid;
         $openid = Cache::get($cacheName);
         if($openid && !$update) return $openid;
         $openid = self::where('uid',$uid)->value('routine_openid');
         if(!$openid) exception('对应的routine_openid不存在!');
         Cache::set($cacheName,$openid,0);
         return $openid;
     }

    public static function setAddTimeAttr($value)
    {
        return time();
    }

    /**
     * .添加新用户
     * @param $openid
     * @return object
     */
    public static function setNewUser($openid)
    {
        $userInfo = WechatService::getUserInfo($openid);
        $userInfo['tagid_list'] = implode(',',$userInfo['tagid_list']);
        return self::set($userInfo);
    }

    /**
     * 更新用户信息
     * @param $openid
     * @return bool
     */
    public static function updateUser($openid)
    {
        $userInfo = WechatService::getUserInfo($openid);
        $userInfo['tagid_list'] = implode(',',$userInfo['tagid_list']);
        return self::edit($userInfo,$openid,'openid');
    }

    /**
     * 用户存在就更新 不存在就添加
     * @param $openid
     */
    public static function saveUser($openid)
    {
        self::be($openid,'openid') == true ? self::updateUser($openid) : self::setNewUser($openid);
    }

    /**
     * 用户取消关注
     * @param $openid
     * @return bool
     */
    public static function unSubscribe($openid)
    {
        return self::edit(['subscribe'=>0],$openid,'openid');
    }

    /**
     * 获取微信用户
     * @param array $where
     * @return array
     */
    public static function systemPage($where = array(),$isall=false){
        $model = new self;
        $model = $model->where('user_type','mobile');
        if($where['nickname'] !== '') $model = $model->where('nickname','LIKE',"%$where[nickname]%");
        if($where['data'] !== ''){
            list($startTime,$endTime) = explode(' - ',$where['data']);
            $model = $model->where('add_time','>',strtotime($startTime));
            $model = $model->where('add_time','<',strtotime($endTime));
        }
        if(isset($where['tagid_list']) && $where['tagid_list'] !== ''){
            $tagid_list = explode(',',$where['tagid_list']);
            foreach ($tagid_list as $v){
                $model = $model->where('tagid_list','LIKE',"%$v%");
            }
        }
        if(isset($where['groupid']) && $where['groupid'] !== '-1' ) $model = $model->where('groupid',"$where[groupid]");
        if(isset($where['sex']) && $where['sex'] !== '' ) $model = $model->where('sex',"$where[sex]");
        if(isset($where['subscribe']) && $where['subscribe'] !== '' ) $model = $model->where('subscribe',"$where[subscribe]");
        $model = $model->order('uid desc');
        if(isset($where['export']) && $where['export'] == 1){
            $list = $model->select()->toArray();
            $export = [];
            foreach ($list as $index=>$item){
                $export[] = [
                    $item['nickname'],
                    $item['sex'],
                    $item['country'].$item['province'].$item['city'],
                    $item['subscribe'] == 1? '关注':'未关注',
                ];
                $list[$index] = $item;
            }
            PHPExcelService::setExcelHeader(['名称','性别','地区','是否关注公众号'])
                ->setExcelTile('微信用户导出','微信用户导出'.time(),' 生成时间：'.date('Y-m-d H:i:s',time()))
                ->setExcelContent($export)
                ->ExcelSave();
        }
        return self::page($model,$where);
    }

    public static function setSpreadWhere($where=[],$alias='',$model=null)
    {
        $model=is_null($model) ? new  self() : $model;
        if($alias){
            $model=$model->alias($alias);
            $alias.='.';
        }
        $status = (int)SystemConfigService::get('store_brokerage_statu');
        if ($status == 1) {
            if ($uids = User::where(['is_promoter' => 1])->column('uid'))
                $model = $model->where($alias.'uid', 'in', implode(',', $uids));
            else
                $model = $model->where($alias.'uid',-1);
        }
        if($where['nickname'] !== '') $model = $model->where("{$alias}nickname",'LIKE',"%$where[nickname]%");
        if($where['start_time'] !== '' && $where['end_time'] !== ''){
            $model = $model->where("{$alias}add_time",'between',[strtotime($where['start_time']),strtotime($where['end_time'])]);
        }
        if(isset($where['sex']) && $where['sex'] !== '' ) $model = $model->where($alias.'sex',$where['sex']);
        if(isset($where['subscribe']) && $where['subscribe'] !== '' ) $model = $model->where($alias.'subscribe',$where['subscribe']);
        if(isset($where['order']) && $where['order'] != '') $model = $model->order($where['order']);
        if(isset($where['user_type']) && $where['user_type']!='') {
            if($where['user_type']==1){
                $model=$model->where('unionid','neq','NULL');
            }else if($where['user_type']==2)
                $model=$model->where('openid','neq','NULL')->where('unionid','NULL');
            else if($where['user_type']==3)
                $model=$model->where('routine_openid','neq','NULL')->where('unionid','NULL');
        }
        return $model;
    }

    /**
     * 获取分销用户
     * @param array $where
     * @return array
     */
    public static function agentSystemPage($where = array()){
        self::setWechatUserOrder((int)$where['page'],(int)$where['limit']);
        $model=self::setSpreadWhere($where)->order('uid desc');
        $status =SystemConfigService::get('store_brokerage_statu');
        if(isset($where['excel']) && $where['excel'] == 1){
            $list = $model->select()->toArray();
            $export = [];
            foreach ($list as $index=>$item){
                $export[] = [
                    $item['nickname'],
                    $item['sex'],
                    $item['country'].$item['province'].$item['city'],
                    $item['stair'],
                    $item['second'],
                    $item['order_stair'],
                    $item['order_second'],
                    $item['now_money'],
                    $item['subscribe'] == 1? '关注':'未关注',
                ];
                $list[$index] = $item;
            }
            PHPExcelService::setExcelHeader(['名称','性别','地区','一级推荐人','二级推荐人','一级推荐订单个数','二级推荐订单个数','获得佣金','是否关注公众号'])
                ->setExcelTile('微信用户导出','微信用户导出'.time(),' 生成时间：'.date('Y-m-d H:i:s',time()))
                ->setExcelContent($export)
                ->ExcelSave();
        }
        $data = $model->page((int)$where['page'],(int)$where['limit'])->select();
        $data = count($data) ? $data->toArray() : [];
        foreach ($data as &$item){
            if((int)$status==2) $item['is_show']=false;
            else $item['is_show']=true;
            $item['extract_count_price'] = UserExtract::getUserCountPrice($item['uid']);//累计提现
            $item['extract_count_num'] = UserExtract::getUserCountNum($item['uid']);//提现次数
            if($item['unionid'])
                $item['type_name'] = '打通';
            else if($item['openid'] && !$item['unionid'])
                $item['type_name'] = '公众号';
            else if($item['routine_openid'] && !$item['unionid'])
                $item['type_name'] = '小程序';
            $item['subscribe_name']=$item['subscribe'] ? '已关注' : ($item['routine_openid'] && !$item['unionid'] ? '暂无' : '未关注') ;
            if($item['sex']==1)
                $item['sex_name'] = '男';
            else if($item['sex']==2)
                $item['sex_name'] = '女';
            else if($item['sex']==0)
                $item['sex_name'] = '未知';
            $item['spread_name']='暂无';
            if($spread_uid=User::where('uid',$item['uid'])->value('spread_uid')) {
                if($user=User::where('uid',$spread_uid)->field(['uid','nickname'])->find()){
                    $item['spread_name']=$user['nickname'].'/'.$user['uid'];
                }
            }
            //总共佣金
            $item['brokerage_money']=UserBill::where(['uid'=>$item['uid'],'category'=>'now_money','type'=>'brokerage','pm'=>1,'status'=>1])->sum('number');
            //可提现佣金
            $item['new_money']=bcsub($item['brokerage_money'],$item['extract_count_price'],2);
            //正在申请提现的金额
            $extract_price=UserExtract::where('uid',$item['uid'])->where('status',0)->sum('extract_price')?:0;
            //减去正在申请的金额
            $item['new_money']=bcsub($item['new_money'],$extract_price,2);
            if($item['new_money'] < 0) $item['new_money']=0;
        }
        $count = self::setSpreadWhere($where)->count();

        return compact('data','count');
    }

     /**
      * 获取筛选后的所有用户uid
      * @param array $where
      * @return array
      */
    public static function getAll($where = array()){
        $model = new self;
        if($where['nickname'] !== '') $model = $model->where('nickname','LIKE',"%$where[nickname]%");
        if($where['data'] !== ''){
            list($startTime,$endTime) = explode(' - ',$where['data']);
            $model = $model->where('add_time','>',strtotime($startTime));
            $model = $model->where('add_time','<',strtotime($endTime));
        }
        if($where['tagid_list'] !== ''){
            $model = $model->where('tagid_list','LIKE',"%$where[tagid_list]%");
        }
        if($where['groupid'] !== '-1' ) $model = $model->where('groupid',"$where[groupid]");
        if($where['sex'] !== '' ) $model = $model->where('sex',"$where[sex]");
        return $model->column('uid','uid');
    }

     /**
      * 获取已关注的用户
      * @param $field
      */
    public static function getSubscribe($field){
        return self::where('subscribe',1)->column($field);
    }

    public static function getUserAll($field){
        return self::column($field);
    }

     public static function getUserTag()
     {
         $tagName = Config::get('system_wechat_tag');
         return Cache::tag($tagName)->remember('_wechat_tag',function () use($tagName){
             Cache::tag($tagName,['_wechat_tag']);
             $tag = WechatService::userTagService()->lists()->toArray()['tags']?:array();
             $list = [];
             foreach ($tag as $g){
                 $list[$g['id']] = $g;
             }
             return $list;
         });
     }

     public static function clearUserTag()
     {
         Cache::rm('_wechat_tag');
     }

     public static function getUserGroup()
     {
         $tagName = Config::get('system_wechat_tag');
         return Cache::tag($tagName)->remember('_wechat_group',function () use($tagName){
             Cache::tag($tagName,['_wechat_group']);
             $tag = WechatService::userGroupService()->lists()->toArray()['groups']?:array();
             $list = [];
             foreach ($tag as $g){
                 $list[$g['id']] = $g;
             }
             return $list;
         });
     }

     public static function clearUserGroup()
     {
         Cache::rm('_wechat_group');
     }

     /**
      * 获取推广人数
      * @param $uid //用户的uid
      * @param int $spread
      * $spread 0 一级推广人数  1 二级推广人数
      * @return int|string
      */
     public static function getUserSpreadUidCount($uid,$spread = 1){
         $userStair = User::where('spread_uid',$uid)->column('uid','uid');//获取一级推家人
         if($userStair){
             if(!$spread) return count($userStair);//返回一级推人人数
             else return User::where('spread_uid','IN',implode(',',$userStair))->count();//二级推荐人数
         }else return 0;
     }

     /**
      * 获取推广人的订单
      * @param $uid
      * @param int $spread
      * $spread 0 一级推广总订单  1 所有推广总订单
      * @return int|string
      */
     public static function getUserSpreadOrderCount($uid,$spread = 1){
         $userStair = User::where('spread_uid',$uid)->column('uid','uid');//获取一级推家人uid
         if($userStair){
             if(!$spread){
                 return StoreOrder::where('uid','IN',implode(',',$userStair))->where('paid',1)->where('pin_type',1)->where('pin_status>1')->count();//获取一级推广人订单数
             }
             else{
                 $userSecond = User::where('spread_uid','IN',implode(',',$userStair))->column('uid','uid');//二级推广人的uid
                 if($userSecond){
                     return StoreOrder::where('uid','IN',implode(',',$userSecond))->where('paid',1)->where('pin_type',1)->where('pin_status>1')->count();//获取二级推广人订单数
                 }else return 0;
             }
         }else return 0;
     }

     /**
      * 同步微信用户表内的 一级推荐人 二级推荐人 一级推荐人订单 二级推荐人订单
      */

     public static function setWechatUserOrder($page=1,$limit=20,$expire=86400){
         // if(Cache::has('page_data_'.$page)) return false;

         Cache::set('page_data_'.$page,1,$expire);
         $uidAll = self::page($page,$limit)->column('uid','uid');
         $item = [];
         foreach ($uidAll as $k=>$v){
             $item['stair'] = self::getUserSpreadUidCount($v,0);//一级推荐人
             $item['second'] = self::getUserSpreadUidCount($v);//二级推荐人
             $item['order_stair'] = self::getUserSpreadOrderCount($v,0);//一级推荐人订单
             $item['order_second'] = self::getUserSpreadOrderCount($v);//二级推荐人订单
             $item['now_money'] = User::where('uid',$v)->value('now_money');//佣金

             if(!$item['stair'] && !$item['second'] && !$item['order_stair'] && !$item['order_second'] && !$item['now_money']) continue;
             else self::edit($item,$v);
         }
     }



}