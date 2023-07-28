<?php

namespace app\admin\controller\system;

use app\admin\controller\AuthController;
use app\admin\model\system\SystemAdmin;
use app\admin\model\system\SystemLog as LogModel;
use service\UtilService as Util;

/**
 * 管理员操作记录表控制器
 * Class SystemLog
 * @package app\admin\controller\system
 */
class SystemLog extends AuthController

{

    /**
     * 显示操作记录
     */
    public function index(){
        LogModel::deleteLog();
        $where = Util::getMore([
            ['pages',''],
            ['path',''],
            ['ip',''],
            ['admin_id',''],
            ['data',''],
        ],$this->request);
        $where['level'] = $this->adminInfo['level'];
        $this->assign('where',$where);
        $this->assign('admin',SystemAdmin::getOrdAdmin('id,real_name',$this->adminInfo['level']));
        $this->assign(LogModel::systemPage($where));
        return $this->fetch();
    }




}

