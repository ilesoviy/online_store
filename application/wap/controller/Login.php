<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2018/01/15
 */

namespace app\wap\controller;

use app\admin\model\system\SystemConfig;
use app\wap\model\user\User;
use app\wap\model\user\WechatUser;
use service\UtilService;
use think\Cookie;
use think\Request;
use think\Session;
use service\JsonService;
use think\Url;
use think\Db;

class Login extends WapBasic
{
    public function index_yuan($ref = '')
    {
        Cookie::set('is_bg',1);
        $ref && $ref=htmlspecialchars_decode(base64_decode($ref));
        if(UtilService::isWechatBrowser()){
            $this->_logout();
            $openid = $this->oauth();
            Cookie::delete('_oen');
            exit($this->redirect(empty($ref) ? Url::build('Index/index') : $ref));
        }
        $this->assign('ref',$ref);
        return $this->fetch();
    }
    //mobile
    public function index($ref = '',$game='')
    {   
        // var_dump(12313);exit();
        Cookie::set('is_bg',1);
        $ref && $ref=htmlspecialchars_decode(base64_decode($ref));
        // if(UtilService::isWechatBrowser()){
        //     $this->_logout();

        //     $openid = $this->oauth();
        //     Cookie::delete('_oen');
        //     exit($this->redirect(empty($ref) ? Url::build('Index/index') : $ref));
        // }
        $logo =SystemConfig::getValue('wechat_avatar');
            if(!empty($game)){
                $ref='http://zsapp.xinyuad.net/wap/gameapi/index.html';
            }
        $this->assign('ref',$ref);
        $this->assign('logo',$logo);
        return $this->fetch();
    }
    public function register(Request $request){
         $logo =SystemConfig::getValue('wechat_avatar');
         // dump(SystemConfig::getValue('initial_invitation_code'));die;
         $this->assign('logo',$logo);
        list($account,$phone,$pwd,$pwd_qr,$verify,$is_code,$age,$birthday,$uname) = UtilService::postMore(['account','phone','pwd','pwd_qr','verify','is_code','age','birthday','uname'],$request,true);
        
         //检验验证码
        if($account){
            $zz_u="/^[a-zA-Z\d_]{1,}$/";
            $zz_p="/^[a-zA-Z\d_]{6,}$/";
            $zz_h="/^0?(13[0-9]|15[012356789]|17[013678]|18[0-9]|14[57])[0-9]{8}$/";
                // preg_match($zz_u, $account, $account);
                preg_match($zz_p, $pwd, $pwd);
                // preg_match($zz_h, $phone, $phone);
          // if(count($account)==0 || count($pwd)==0) return $this->failed('输入的字符不合法');
          if(count($pwd)==0) return $this->failed(L('輸入的字元不合法','Illegal character entered'));
          // if(count($phone)==0) return $this->failed('手机号不正确');
          // if($pwd[0]!=$pwd_qr) return $this->failed('两次密码不一致');
          // if(!captcha_check($verify)) return $this->failed('验证码错误，请重新输入');
          
          if(!Db::name('user')->where(["in_code"=>$is_code])->find() && SystemConfig::getValue('initial_invitation_code') !=$is_code){
                return $this->failed(L('邀請碼錯誤','Invitation code error'));
          }
          if(Db::name('user')->where(["account"=>$account])->find()){
            return $this->failed(L('郵箱被佔用','Mailbox occupied'));
          }
          do{
                $in_code = $this->GetRandStr(6);
            }while(Db::name('user')->where(["in_code"=>$in_code])->find());


            //绑定推荐人
            $spread_uid=0;
            if($is_code != ""){
                $recom=Db::name('user')->where(["in_code"=>$is_code])->find();
                if($recom){
                    $spread_uid=$recom['uid'];
                }else {
                    $spread_uid=0;
                }
               
            }
            // dump($spread_uid);die;
          $data['account']=$account;
          $data['in_code']=$in_code;
          $data['phone']='';
          $data['pwd']=$pwd[0];
          $data['nickname']=$account;
          $data['avatar']=PUBILC_PATH."system/images/head.gif";
          $data['name']=$uname;
          $data['birthday']=$birthday;
          $data['age']=$age;
          // dump($data);die;
          if(User::setRegisterUser($data,$spread_uid)){
            $this->successful(L('注册成功','login was successful'),Url::build('Login/index'));
          }



        }
        
        return $this->fetch();
    }
    /**
     * 随机字符串
     */
    
    public function GetRandStr($length) {
        // $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $len = strlen($str) - 1;
        $randstr = '';
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }
    public function check(Request $request)
    {
        list($account,$pwd,$ref) = UtilService::postMore(['account','pwd','ref'],$request,true);
        // $account='superadmin';
        // $pwd='123456';
        // $account='admin';
        // $pwd='12345678';
        // return $this->failed('暂无法登录');
        if(!$account || !$pwd) return $this->failed(L('請輸入登入帳號','Please enter login account'));
        if(!$pwd) return $this->failed(L('請輸入登入密碼','Please enter the login password'));
        if(!User::be(['account'=>$account])) return $this->failed(L('登入帳號不存在！','Login account does not exist!'));
        $userInfo = User::where('account',$account)->find();
        $errorInfo = Session::get('login_error_info','wap')?:['num'=>0];
        $now = time();
        if($errorInfo['num'] > 5 && $errorInfo['time'] < ($now - 900))
            return $this->failed(L('錯誤次數過多，請稍候再試！','Too many errors, please try again later!'));
        if($userInfo['pwd'] != md5($pwd)){
            Session::set('login_error_info',['num'=>$errorInfo['num']+1,'time'=>$now],'wap');
            return $this->failed(L('帳號或密碼輸入錯誤！','Wrong account or password!'));
        }
        if(!$userInfo['status']) return $this->failed(L('帳號已被鎖定，無法登陸！','Account has been locked, unable to log in!'));
        $this->_logout();
        Session::set('loginUid',$userInfo['uid'],'wap');
        $userInfo['last_time'] = time();
        $userInfo['last_ip'] = $request->ip();
        $userInfo->save();
        Session::delete('login_error_info','wap');
        Cookie::set('is_login',1);
        exit($this->redirect(empty($ref) ? Url::build('Index/index') : $ref));
    }
  public function captcha()
    {
        ob_clean();
        $captcha = new \think\captcha\Captcha([
            'codeSet'=>'0123456789',
            'length'=>4,
            'fontSize'=>30
        ]);
        return $captcha->entry();
    }
    public function logout()
    {
        $this->_logout();
        $this->successful(L('退出登入成功','Exit login succeeded'),Url::build('Index/index'));
    }
    /* 
    *找回密码
    */
   public function psw(Request $request){
         list($account,$pwd,$protection) = UtilService::postMore(['account','pwd','protection'],$request,true);
         if($account){
            if(!User::be(['account'=>$account])) return $this->failed(L('帳號不存在！','Account does not exist!'));
            $userInfo = User::where('account',$account)->where('name|newage|birthday',$protection)->find();
            if(!$userInfo) return $this->failed(L('密保錯誤','Secret protection error'));

            $userInfo['pwd']=md5($pwd);
            $userInfo->save();
             $this->successful(L('密碼修改成功','Password modified successfully'),Url::build('Login/index'));
         }
       return $this->fetch();
   }
    private function _logout()
    {
        $lang=Session::get('lang');
        Session::clear('wap');
        if($lang=='zh'){
            Session::set('lang', 'zh');
        }
        Cookie::delete('is_login');
    }

}