<?php
namespace app\admin\controller\setting;
use think\Url;
use service\FormBuilder as Form;
use think\Request;
use service\UtilService as Util;
use service\JsonService as Json;
use app\admin\controller\AuthController;
use app\admin\model\system\SystemConfigTab as ConfigTabModel;
use app\admin\model\system\SystemConfig as ConfigModel;
/**
 * 配置分类控制器
 * Class SystemConfigTab
 * @package app\admin\controller\system
 */
class SystemConfigTab extends AuthController
{

    /**
     * 子子段
     * @return mixed|\think\response\Json
     */
    public function sonconfigtab(){
        $tab_id = input('tab_id');
        if(!$tab_id) return Json::fail('参数错误');
        $this->assign('tab_id',$tab_id);
        $list = ConfigModel::getAll($tab_id);
        foreach ($list as $k=>$v){
            $list[$k]['value'] = json_decode($v['value'],true)?:'';
            if($v['type'] == 'radio' || $v['type'] == 'checkbox'){
                $list[$k]['value'] = ConfigTabModel::getRadioOrCheckboxValueInfo($v['menu_name'],$v['value']);
            }
            if($v['type'] == 'upload' && !empty($v['value'])){
//                if($v['upload_type'] == 1 || $v['upload_type'] == 3) $list[$k]['value'] = explode(',',$v['value']);
            }
        }
        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 基础配置
     * @return mixed
     */
   public function index(){
       $where = Util::getMore([
           ['status',''],
           ['title',''],
       ],$this->request);
       $this->assign('where',$where);
       $this->assign(ConfigTabModel::getSystemConfigTabPage($where));
       return $this->fetch();
   }
    /**
     * 添加配置分类
     * @return mixed
     */
   public function create(){
       $form = Form::create(Url::build('save'),[
           Form::input('title','分类昵称'),
           Form::input('eng_title','分类字段'),
           Form::frameInputOne('icon','图标',Url::build('admin/widget.widgets/icon',array('fodder'=>'icon')))->icon('ionic'),
           Form::radio('type','类型',0)->options([['value'=>0,'label'=>'系统'],['value'=>1,'label'=>'公众号'],['value'=>2,'label'=>'小程序'],['value'=>3,'label'=>'其它']]),
           Form::radio('status','状态',1)->options([['value'=>1,'label'=>'显示'],['value'=>2,'label'=>'隐藏']])
       ]);
       $form->setMethod('post')->setTitle('添加分类配置');
       $this->assign(compact('form'));
       return $this->fetch('public/form-builder');
   }
    /**
     * 保存分类名称
     * @param Request $request
     * @return \think\response\Json
     */
   public function save(Request $request){
       $data = Util::postMore([
           'eng_title',
           'status',
           'title',
           'icon',
           'type'],$request);
       if(!$data['title']) return Json::fail('请输入按钮名称');
       ConfigTabModel::set($data);
       return Json::successful('添加菜单成功!');
   }
    /**
     * 修改分类
     * @param $id
     * @return mixed
     */
   public function edit($id){
       $menu = ConfigTabModel::get($id)->getData();
       if(!$menu) return Json::fail('数据不存在!');
       $form = Form::create(Url::build('update',array('id'=>$id)),[
           Form::input('title','分类昵称',$menu['title']),
           Form::input('eng_title','分类字段',$menu['eng_title']),
           Form::frameInputOne('icon','图标',Url::build('admin/widget.widgets/icon',array('fodder'=>'icon')),$menu['icon'])->icon('ionic'),
           Form::radio('type','类型',$menu['type'])->options([['value'=>0,'label'=>'系统'],['value'=>1,'label'=>'公众号'],['value'=>2,'label'=>'小程序'],['value'=>3,'label'=>'其它']]),
           Form::radio('status','状态',$menu['status'])->options([['value'=>1,'label'=>'显示'],['value'=>2,'label'=>'隐藏']])
       ]);
       $form->setMethod('post')->setTitle('添加分类配置');
       $this->assign(compact('form'));
       return $this->fetch('public/form-builder');
   }

    /**
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function update(Request $request, $id)
    {
            $data = Util::postMore(['title','status','eng_title','icon','type'],$request);
        if(!$data['title']) return Json::fail('请输入分类昵称');
        if(!$data['eng_title']) return Json::fail('请输入分类字段');
        if(!ConfigTabModel::get($id)) return Json::fail('编辑的记录不存在!');
        ConfigTabModel::edit($data,$id);
        return Json::successful('修改成功!');
    }
    /**
     * @param $id
     * @return \think\response\Json
     */
    public function delete($id){
        if(!ConfigTabModel::del($id))
            return Json::fail(ConfigTabModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }
}
