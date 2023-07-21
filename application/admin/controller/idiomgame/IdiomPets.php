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
use app\admin\model\idiomgame\IdiomPets as IdiomPetModel;
/**
 * 游戏宠物管理控制器
 * Class IdiomPet
 * @package app\admin\controller\system
 */
class IdiomPets extends AuthController
{
    //显示资源列表
    public function index(){
        $this->assign('cate',IdiomPetModel::getTierList());
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
        return JsonService::successlayui(IdiomPetModel::PetList($where));
    }
     /**
     * 设置单个产品上架|下架
     *
     * @return json
     */
    public function set_show($status='',$id=''){
        ($status=='' || $id=='') && JsonService::fail('缺少参数');
        $res=IdiomPetModel::where(['id'=>$id])->update(['status'=>(int)$status]);
        if($res){
            return JsonService::successful($status==1 ? '显示成功':'隐藏成功');
        }else{
            return JsonService::fail($status==1 ? '显示失败':'隐藏失败');
        }
    }
    //显示创建资源表单页
    public function create(){
         $field = [
            Form::input('pet_name','种子名称'),
            Form::frameImageOne('pet_simg','种子图片',Url::build('admin/widget.images/index',array('fodder'=>'pet_simg')))->icon('image'),
            Form::frameImageOne('pet_img','果实图片',Url::build('admin/widget.images/index',array('fodder'=>'pet_img')))->icon('image'),
            Form::number('need_integral','兑换积分'),
            
            Form::number('add_energy','可换积分'),
            // Form::number('add_max_energy','增加上线体力值'),
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
            'pet_name',
            'add_energy',
            // 'add_max_energy',
            'need_integral',
            ['pet_img',[]],
            ['pet_simg',[]],
            ['status',0]
        ],$request);
        if(!$data['pet_name']) return Json::fail('请输入名称');
        if(!$data['need_integral']) return Json::fail('请输入积分');
        if(count($data['pet_img'])<1) return Json::fail('请上传果实图片');
        $data['pet_img'] = $data['pet_img'][0];
        if(count($data['pet_simg'])<1) return Json::fail('请上传种子图片');
        $data['pet_simg'] = $data['pet_simg'][0];
        $data['add_time'] = time();
        IdiomPetModel::set($data);
        return Json::successful('添加种子成功!');
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
        $c = IdiomPetModel::get($id);
        if(!$c) return Json::fail('数据不存在!');
        $field = [
            Form::input('pet_name','种子名称',$c->getData('pet_name')),
            
            Form::frameImageOne('pet_simg','种子图片',Url::build('admin/widget.images/index',array('fodder'=>'pet_simg')),$c->getData('pet_simg'))->icon('image'),
            Form::frameImageOne('pet_img','果实图片',Url::build('admin/widget.images/index',array('fodder'=>'pet_img')),$c->getData('pet_img'))->icon('image'),
            Form::number('need_integral','兑换积分',$c->getData('need_integral')),
            Form::number('add_energy','可换积分',$c->getData('add_energy')),
            // Form::number('add_max_energy','增加上线体力值',$c->getData('add_max_energy')),
            
            
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
            'pet_name',
            'add_energy',
            // 'add_max_energy',
            'need_integral',
            ['pet_img',[]],
            ['pet_simg',[]],
            ['status',0]
        ],$request);
       if(!$data['pet_name']) return Json::fail('请输入名称');
        if(!$data['need_integral']) return Json::fail('请输入积分');
        if(count($data['pet_img'])<1) return Json::fail('请上传果实图片');
        $data['pet_img'] = $data['pet_img'][0];
        if(count($data['pet_simg'])<1) return Json::fail('请上传种子图片');
        $data['pet_simg'] = $data['pet_simg'][0];
        IdiomPetModel::edit($data,$id);
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
            return Json::fail(IdiomPetModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }
}
 ?>