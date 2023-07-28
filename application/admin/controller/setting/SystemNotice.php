<?php
namespace app\admin\controller\setting;
use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use app\admin\model\system\SystemAdmin;
use app\admin\model\system\SystemNotice as NoticeModel;
use service\JsonService;
use service\UtilService;
use think\Request;
use think\Url;
/**
 * 管理员消息通知 控制器
 * Class SystemNotice
 * @package app\admin\controller\system
 */
class SystemNotice extends AuthController
{
    public function index(){
        $this->assign(NoticeModel::page(function($notice){
            $notice->push_admin_name = !empty($notice->push_admin) ? implode(',',SystemAdmin::where('id','IN',$notice->push_admin)->column('real_name')) : '';
        }));
        return $this->fetch();
    }

     public function create()
     {
         $f = array();
         $f[] = Form::input('title','通知标题');
         $f[] = Form::input('type','通知类型');
         $f[] = Form::frameInputOne('icon','图标',Url::build('admin/widget.widgets/icon',array('fodder'=>'icon')))->icon('ionic');
         $f[] = Form::input('template','通知模板');
         $f[] = Form::input('table_title','通知数据')->type('textarea')->placeholder('数据1-key1,数据2-key2');
         $f[] = Form::select('push_admin','通知管理员')->setOptions(function(){
             $list = SystemAdmin::getOrdAdmin('real_name,id')?:[];
             $options = [];
             foreach ($list as $admin){
                 $options[] = ['label'=>$admin['real_name'],'value'=>$admin['id']];
             }
             return $options;
         })->multiple(1);
         $f[] = Form::radio('status','状态',1)->options([['label'=>'开启','value'=>1],['label'=>'关闭','value'=>0]]);
         $form = Form::make_post_form('添加通知模板',$f,Url::build('save'));
         $this->assign(compact('form'));
         return $this->fetch('public/form-builder');
     }
     public function save(Request $request)
     {
         $data = UtilService::postMore([
             'title', 'type', 'icon', 'template','table_title',
             ['push_admin', []], ['status', 0]
         ], $request);
         $data['push_admin'] = array_unique(array_filter($data['push_admin']));
         if (!$data['template']) return $this->failed('请填写通知模板');
         if (!$data['title']) return $this->failed('请输入模板标题');
         if (!$data['type']) return $this->failed('请输入模板类型');
         if (NoticeModel::set($data))
             return $this->successful('添加通知成功');
         else
             return $this->failed('添加失败!');
     }

    /**编辑通知模板
     * @param $id
     * @return mixed|void
     */
     public function edit($id)
     {
         $data = NoticeModel::get($id);
         if(!$data) return JsonService::fail('数据不存在!');
         $data->tableTitle = implode(',',array_map(function($value){
             return $value['title'].'-'.$value['key'];
         },$data->table_title));
         $data->tableTitleStr = implode(',',array_map(function($value){
             return $value['title'].'-'.$value['key'];
         },$data->table_title));
         $f = array();
         $f[] = Form::input('title','通知标题',$data->title);
         $f[] = Form::input('type','通知类型',$data->type);
         $f[] = Form::frameInputOne('icon','图标',Url::build('admin/widget.widgets/icon',array('fodder'=>'icon')),$data->icon)->icon('ionic');
         $f[] = Form::input('template','通知模板',$data->template);
         $f[] = Form::input('table_title','通知数据',$data->tableTitleStr)->type('textarea')->placeholder('数据1-key1,数据2-key2');
         $f[] = Form::select('push_admin','通知管理员',$data->push_admin)->setOptions(function(){
             $list = SystemAdmin::getOrdAdmin('real_name,id')?:[];
             $options = [];
             foreach ($list as $admin){
                 $options[] = ['label'=>$admin['real_name'],'value'=>$admin['id']];
             }
             return $options;
         })->multiple(1);
         $f[] = Form::radio('status','状态',$data->status)->options([['label'=>'开启','value'=>1],['label'=>'关闭','value'=>0]]);
         $form = Form::make_post_form('编辑通知模板',$f,Url::build('update',array('id'=>$id)));
         $this->assign(compact('form'));
         return $this->fetch('public/form-builder');
     }
    public function update(Request $request, $id)
    {
        $data = UtilService::postMore([
            'title','type','icon','template','table_title',
            ['push_admin',[]],['status',0]
        ],$request);
        $data['push_admin'] = array_unique(array_filter($data['push_admin']));
        if(!$data['template']) return $this->failed('请填写通知模板');
        if(!$data['title']) return $this->failed('请输入模板标题');
        if(!$data['type']) return $this->failed('请输入模板类型');
        NoticeModel::edit($data,$id);
        return $this->successful('修改成功!');
    }
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $res = NoticeModel::del($id);
        if(!$res)
            return $this->failed(('删除失败,请稍候再试!'));
        else
            return $this->successful('删除成功!');
    }
     public function message($type = 'all')
     {
        return $this->fetch();
     }
}
