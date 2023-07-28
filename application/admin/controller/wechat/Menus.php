<?php
namespace app\admin\controller\wechat;

use app\admin\controller\AuthController;
use app\core\util\WechatService;
use think\Cache;
use think\Db;
use think\Request;

/**
 * 微信菜单  控制器
 * Class Menus
 * @package app\admin\controller\wechat
 */
class Menus extends AuthController
{

    public function index()
    {
        $menus = Db::name('cache')->where('key','wechat_menus')->value('result');
        $menus = $menus ? : '[]';
        $this->assign('menus',$menus);
        return $this->fetch();
    }

    public function save(Request $request)
    {
        $buttons = $request->post('button/a',[]);
        if(!count($buttons)) return $this->failed('请添加至少一个按钮');
        try{
            WechatService::menuService()->add($buttons);
            Db::name('cache')->insert(['key'=>'wechat_menus','result'=>json_encode($buttons),'add_time'=>time()],true);
            return $this->successful('修改成功!');
        }catch (\Exception $e){
            return $this->failed($e->getMessage());
        }
    }
}