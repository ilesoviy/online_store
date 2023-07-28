<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2018/3/3
 */

namespace app\ebapi\model\user;


use app\core\model\user\UserBill;
use basic\ModelBasic;
use app\core\util\SystemConfigService;
use app\core\util\Template;
use traits\ModelTrait;


/** 用户提现
 * Class UserExtract
 * @package app\ebapi\model\user
 */
class UserExtract extends ModelBasic
{
    use ModelTrait;

    //审核中
    const AUDIT_STATUS = 0;
    //未通过
    const FAIL_STATUS = -1;
    //已提现
    const SUCCESS_STATUS = 1;

    protected static $extractType = ['alipay','bank','weixin'];

    protected static $extractTypeMsg = ['alipay'=>'支付宝','bank'=>'银行卡','weixin'=>'微信'];

    protected static $status = array(
        -1=>'未通过',
        0 =>'审核中',
        1 =>'已提现'
    );

    /*
     * 用户自主提现记录提现记录,后台执行审核
     * @param array $userInfo 用户个人信息
     * @param array $data 提现详细信息
     * @return boolean
     * */
    public static function userExtract($userInfo,$data){
        if(!in_array($data['extract_type'],self::$extractType))
            return self::setErrorInfo('提现方式不存在');
        if($data['extract_type']=='weixin') return self::setErrorInfo('微信提现暂时关闭！');
        $userInfo = User::get($userInfo['uid']);
        $brokerage = UserBill::getBrokerage($userInfo['uid']);//获取总佣金
        $extractTotalPrice = self::userExtractTotalPrice($userInfo['uid']);//累计提现
        $extractPrice = (float)bcsub($brokerage,$extractTotalPrice,2);//减去已提现金额
        $extractPrice = (float)bcsub($extractPrice,self::userExtractTotalPrice($userInfo['uid'],0),2);//减去提现申请中的金额
        if($extractPrice < 0) return self::setErrorInfo('提现佣金不足'.$data['money']);
        if($data['money'] > $extractPrice) return self::setErrorInfo('提现佣金不足'.$data['money']);
        $balance = bcsub($userInfo['now_money'],$data['money'],2);
        
        if($balance < 0) $balance=0;
        $insertData = [
            'uid'=>$userInfo['uid'],
            'real_name'=>$data['real_name'],
            'extract_type'=>$data['extract_type'],
            'extract_price'=>($data['extract_price']),
            'add_time'=>time(),
            'balance'=>$balance,
            'status'=>self::AUDIT_STATUS
        ];
        if(isset($data['name'])) $insertData['real_name'] = $data['name'];
        else $insertData['real_name'] = $userInfo['nickname'];
        if(isset($data['cardnum'])) $insertData['bank_code'] = $data['cardnum'];
        else $insertData['bank_code'] = '';
        if(isset($data['bankname'])) $insertData['bank_address']=$data['bankname'];
        else $insertData['bank_address']='';
        if(isset($data['weixin'])) $insertData['wechat'] = $data['weixin'];
        else $insertData['wechat'] = $userInfo['nickname'];
        if($data['extract_type'] == 'alipay'){
            if(!$data['alipay_code']) return self::setErrorInfo('请输入支付宝账号');
            $insertData['alipay_code'] = $data['alipay_code'];
            $mark = '使用支付宝提现'.$insertData['extract_price'].'元';
        }elseif($data['extract_type'] == 'bank'){
            if(!$data['cardnum']) return self::setErrorInfo('请输入银行卡账号');
            if(!$data['bankname']) return self::setErrorInfo('请输入开户行信息');
            $mark = '使用银联卡'.$insertData['bank_code'].'提现'.$insertData['extract_price'].'元';
        }elseif($data['extract_type'] == 'weixin') $mark = '使用微信提现'.$insertData['extract_price'].'元';
        self::beginTrans();
        try{
            $res1 = self::set($insertData);
            if(!$res1) return self::setErrorInfo('提现失败');
            $res2 = User::edit(['now_money'=>$balance],$userInfo['uid'],'uid');
            $res3 = UserBill::expend('余额提现',$userInfo['uid'],'now_money','extract',$data['money'],$res1['id'],$balance,$mark);
            $res = $res2 && $res3;
            if($res){
                self::commitTrans();
                //发送模板消息
                return true;
            }else return self::setErrorInfo('提现失败!');
        }catch (\Exception $e){
            self::rollbackTrans();
            return self::setErrorInfo('提现失败!');
        }
    }

    /**
     * 获得用户最后一次提现信息
     * @param $openid
     * @return mixed
     */
    public static function userLastInfo($uid)
    {
        return self::where(compact('uid'))->order('add_time DESC')->find();
    }

    /**
     * 获得用户提现总金额
     * @param $uid
     * @return mixed
     */
    public static function userExtractTotalPrice($uid,$status=self::SUCCESS_STATUS)
    {
        return self::where('uid',$uid)->where('status',$status)->value('SUM(extract_price)')?:0;
    }

    /*
     * 用户提现记录列表
     * @param int $uid 用户uid
     * @param int $first 截取行数
     * @param int $limit 截取数
     * @return array
     * */
    public static function extractList($uid,$first = 0,$limit = 8)
    {
        $list=UserExtract::where('uid',$uid)->order('add_time desc')->limit($first,$limit)->select();
        foreach($list as &$v){
            $v['add_time']=date('Y/m/d',$v['add_time']);
        }
        return $list;
    }

    /*
   * 获取累计已提现佣金
   * @param int $uid
   * @return float
   * */
    public static function extractSum($uid)
    {
        return self::where('uid',$uid)->where('status',1)->sum('extract_price');
    }

}