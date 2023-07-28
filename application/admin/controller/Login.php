<?php

namespace app\admin\controller;


use app\admin\model\system\SystemAdmin;
use app\admin\model\system\SystemConfig;
use service\CacheService;
use service\UtilService;
use think\Request;
use think\Response;
use think\Session;
use think\Url;

/**
 * 登录验证控制器
 * Class Login
 * @package app\admin\controller
 */
class Login extends SystemBasic
{
    public function index()
    { $sitename=json_decode(SystemConfig::where(['id'=>1])->value('value'),true);
        $site_url=json_decode(SystemConfig::where(['id'=>2])->value('value'),true);
         $site_logo = SystemConfig::getOneConfig('menu_name','site_logo')->toArray();
$this->assign(compact('sitename')); 
$this->assign(compact('site_url')); 
$this->assign('site_logo',json_decode($site_logo['value'],true)); 
        return $this->fetch();
    }

    /**
     * 登录验证 + 验证码验证
     */
    public function verify(Request $request)
    {
        if(!$request->isPost()) return $this->failed('请登陆!');
        list($account,$pwd,$verify) = UtilService::postMore([
            'account','pwd','verify'
        ],$request,true);
        //检验验证码
        if(!captcha_check($verify)) return $this->failed('验证码错误，请重新输入');
        $error  = Session::get('login_error')?:['num'=>0,'time'=>time()];
        $error['num'] = 0;
        if($error['num'] >=5 && $error['time'] > strtotime('- 5 minutes'))
            return $this->failed('错误次数过多,请稍候再试!');
        //检验帐号密码
        $res = SystemAdmin::login($account,$pwd);
        if($res){
            Session::set('login_error',null);
            return $this->redirect(Url::build('Index/index'));
        }else{
            $error['num'] += 1;
            $error['time'] = time();
            Session::set('login_error',$error);
            return $this->failed(SystemAdmin::getErrorInfo('用户名错误，请重新输入'));
        }
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

    /**
     * 退出登陆
     */
    public function logout()
    {
        SystemAdmin::clearLoginInfo();
        $this->redirect('Login/index');
    }
}