<?php 
namespace app\admin\controller\idiomgame;
use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\JsonService;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use think\Url;
use app\admin\model\system\SystemAttachment;
use app\admin\model\idiomgame\IdiomRank as IdiomRankModel;
/**
 * 游戏宠物管理控制器
 * Class IdiomPet
 * @package app\admin\controller\system
 */
class IdiomRank extends AuthController
{
    //显示资源列表
    public function index(){
        $this->assign('cate',IdiomRankModel::getTierList());
        return $this->fetch();
    }
     /*
     *  异步获取分类列表
     *  @return json
     */
    public function pet_list(){
        $where = Util::getMore([
            ['status',''],
            ['name',''],
            ['page',1],
            ['limit',20],
            ['order','']
        ]);
        return JsonService::successlayui(IdiomRankModel::PetList($where));
    }
     /**
     * 设置单个产品上架|下架
     *
     * @return json
     */
    public function set_show($status='',$id=''){
        ($status=='' || $id=='') && JsonService::fail('缺少参数');
        $res=IdiomRankModel::where(['id'=>$id])->update(['status'=>(int)$status]);
        if($res){
            return JsonService::successful($status==1 ? '显示成功':'隐藏成功');
        }else{
            return JsonService::fail($status==1 ? '显示失败':'隐藏失败');
        }
    }
    //显示创建资源表单页
    public function create(){
         $field = [
            Form::input('name','宠物名称'),
            // Form::frameImageOne('pet_img','宠物图片',Url::build('admin/widget.images/index',array('fodder'=>'pet_img')))->icon('image'),
            // Form::frameImageOne('pet_simg','宠物缩略图',Url::build('admin/widget.images/index',array('fodder'=>'pet_simg')))->icon('image'),
            Form::number('level','第几关卡生效'),
            // Form::number('add_max_energy','增加上线体力值'),
            // Form::number('need_integral','所需积分'),
            Form::radio('status','状态',1)->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])
        ];
        $form = Form::make_post_form('添加分类',$field,Url::build('save'),2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }
    //保存新建的资源
    public function save(Request $request)
    {
        $data = Util::postMore([
            'name',
            'level',
            // 'add_max_energy',
            // 'need_integral',
            // ['pet_img',[]],
            // ['pet_simg',[]],
            ['status',0]
        ],$request);
        if(!$data['name']) return Json::fail('请输入名称');
        // if(!$data['level']) return Json::fail('请输入关卡数');
        if($data['level']>101 || $data['level']<0) return Json::fail('请输入有效关卡数');

        // if(count($data['pet_img'])<1) return Json::fail('请上传宠物图片');
        // $data['pet_img'] = $data['pet_img'][0];
        // if(count($data['pet_simg'])<1) return Json::fail('请上传宠物缩略图');
        // $data['pet_simg'] = $data['pet_simg'][0];
        $data['add_time'] = time();
        IdiomRankModel::set($data);
        return Json::successful('添加头衔成功!');
    }
    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function upload()
    {
        $res = Upload::image('file','idiom/idiompet'.date('Ymd'));
        if(is_array($res)){
            SystemAttachment::attachmentAdd($res['name'],$res['size'],$res['type'],$res['dir'],$res['thumb_path'],1,$res['image_type'],$res['time']);
            return Json::successful('图片上传成功!',['name'=>$res['name'],'url'=>Upload::pathToUrl($res['thumb_path'])]);
        }else
            return Json::fail($res);
    }
    //显示编辑资源表单页
   public function edit($id)
    {
        $c = IdiomRankModel::get($id);
        if(!$c) return Json::fail('数据不存在!');
        $field = [
            Form::input('name','宠物名称',$c->getData('name')),
            // Form::frameImageOne('pet_img','宠物图片',Url::build('admin/widget.images/index',array('fodder'=>'pet_img')),$c->getData('pet_img'))->icon('image'),
            // Form::frameImageOne('pet_simg','宠物缩略图',Url::build('admin/widget.images/index',array('fodder'=>'pet_simg')),$c->getData('pet_simg'))->icon('image'),
            Form::number('level','请输入关卡数',$c->getData('level')),
            // Form::number('add_max_energy','增加上线体力值',$c->getData('add_max_energy')),
            // Form::number('need_integral','所需积分',$c->getData('need_integral')),
            
            Form::radio('status','状态',$c->getData('status'))->options([['label'=>'显示','value'=>1],['label'=>'隐藏','value'=>0]])
        ];
        $form = Form::make_post_form('编辑',$field,Url::build('update',array('id'=>$id)),2);

        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }
     /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            'name',
            'level',
            // 'add_max_energy',
            // 'need_integral',
            // ['pet_img',[]],
            // ['pet_simg',[]],
            ['status',0]
        ],$request);
       if(!$data['name']) return Json::fail('请输入名称');
        // if(!$data['level']) return Json::fail('请输入关卡数');
        if($data['level']>101 || $data['level']<0) return Json::fail('请输入有效关卡数');
        // if(count($data['pet_img'])<1) return Json::fail('请上传宠物图片');
        // $data['pet_img'] = $data['pet_img'][0];
        // if(count($data['pet_simg'])<1) return Json::fail('请上传宠物缩略图');
        // $data['pet_simg'] = $data['pet_simg'][0];
        IdiomRankModel::edit($data,$id);
        return Json::successful('修改成功!');
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if(!CategoryModel::delCategory($id))
            return Json::fail(IdiomRankModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }
}
 ?>