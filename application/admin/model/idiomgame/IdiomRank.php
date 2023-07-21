<?php 
namespace app\admin\model\idiomgame;
use traits\ModelTrait;
use basic\ModelBasic;
use service\UtilService;
class IdiomRank extends ModelBasic
{
    use ModelTrait;
    /*
     * 异步获取分类列表
     * @param $where
     * @return array
     */
    public static function PetList($where){
        $data=($data=self::systemPage($where,true)->page((int)$where['page'],(int)$where['limit'])->select()) && count($data) ? $data->toArray() :[];
        $count=self::systemPage($where,true)->count();
        return compact('count','data');
    }
    /**
     * @param $where
     * @return array
     */
    public static function systemPage($where,$isAjax=false){
        $model = new self;
        if($where['status'] != '')  $model = $model->where('status',$where['status']);
        if($where['name'] != '')  $model = $model->where('name','LIKE',"%$where[name]%");
        if($isAjax===true){
            if(isset($where['order']) && $where['order']!=''){
                $model=$model->order(self::setOrder($where['order']));
            }else{
                $model=$model->order('id desc');
            }
            return $model;
        }
        return self::page($model,function ($item){
            
        },$where);
    }

    /**
     * 获取顶级分类
     * @return array
     */
    public static function getCategory($field = 'id,cate_name')
    {
        return self::where('is_show',1)->column($field);
    }

    /**
     * 分级排序列表
     * @param null $model
     * @return array
     */
    public static function getTierList($model = null)
    {
        if($model === null) $model = new self();
        return $model->order('id desc')->select()->toArray();
    }

    public static function delCategory($id){
        $count = self::where('pid',$id)->count();
        if($count)
            return false;
        else{
            return self::del($id);
        }
    }
}
 ?>