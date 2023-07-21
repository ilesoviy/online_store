<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */
namespace app\admin\controller\user;

use app\admin\controller\AuthController;
use app\admin\model\system\SystemUserLevel;
use service\FormBuilder as Form;
use service\JsonService;
use think\Db;
use traits\CurdControllerTrait;
use service\UtilService;
use service\JsonService as Json;
use think\Request;
use think\Url;
use basic\ModelBasic;
use service\HookService;
use app\admin\model\user\Robot as RobotModel;
use behavior\user\UserBehavior;
use app\admin\model\wechat\WechatMessage;

/**
 * 机器人管理控制器
 * Class User
 * @package app\admin\controller\user
 */
class Robot extends AuthController
{
    use CurdControllerTrait;
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(){
        $this->assign('count_user',RobotModel::getcount());
        return $this->fetch();
    }

    /*
     * 创建form表单
     * */
    public function create($id=0)
    {
        if($id) $vipinfo=RobotModel::get($id);
        $field[]= Form::input('nickname','昵称',isset($vipinfo) ? $vipinfo->nickname : '')->col(Form::col(24));
        $field[]= Form::frameImageOne('avatar','头像',Url::build('admin/widget.images/index',array('fodder'=>'avatar')),isset($vipinfo) ? $vipinfo->avatar : '')->icon('image')->width('100%')->height('500px');
        $form = Form::make_post_form('添加等级设置',$field,Url::build('save',['id'=>$id]),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /*
     * 会员等级添加或者修改
     * @param $id 修改的等级id
     * @return json
     * */
    public function save($id=0)
    {
        $data=UtilService::postMore([
            ['nickname',''],
            ['avatar',''],
        ]);
        if(!$data['nickname']) return JsonService::fail('请输入昵称');
        if(!$data['avatar']) return JsonService::fail('请上传头像');
        RobotModel::beginTrans();
        try{
            //修改
            if($id){
                if(RobotModel::edit($data,$id)){
                    RobotModel::commitTrans();
                    return JsonService::successful('修改成功');
                }else{
                    RobotModel::rollbackTrans();
                    return JsonService::fail('修改失败');
                }
            }else{
                //新增
                $data['add_time']=time();
                if(RobotModel::set($data)){
                    RobotModel::commitTrans();
                    return JsonService::successful('添加成功');
                }else{
                    RobotModel::rollbackTrans();
                    return JsonService::fail('添加失败');
                }
            }
        }catch (\Exception $e){
            RobotModel::rollbackTrans();
            return JsonService::fail($e->getMessage());
        }
    }
    /*
     * 清除
     * @param int $uid
     * @return json
     * */
    public function delete($id=0)
    {
        if(db('robot')->delete($id))
            return JsonService::successful('删除成功');
        else
            return JsonService::fail('删除失败');
    }
    /**
     * 获取user表
     *
     * @return json
     */
    public function get_user_list(){
        $where=UtilService::getMore([
            ['page',1],
            ['limit',20]
           ]);
        // dump(RobotModel::getUserList($where));die;
        return Json::successlayui(RobotModel::getUserList($where));
    }
}
