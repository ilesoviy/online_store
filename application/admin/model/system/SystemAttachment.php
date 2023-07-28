<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/13
 */

namespace app\admin\model\system;

use Api\Storage\Qiniu\Qiniu;
use app\core\util\SystemConfigService;
use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 文件检验model
 * Class SystemFile
 * @package app\admin\model\system
 */
class SystemAttachment extends ModelBasic
{
    use ModelTrait;

    /**
     * TODO 添加附件记录
     * @param $name
     * @param $att_size
     * @param $att_type
     * @param $att_dir
     * @param string $satt_dir
     * @param int $pid
     * @param int $imageType
     * @param int $time
     * @return SystemAttachment
     */
    public static function attachmentAdd($name,$att_size,$att_type,$att_dir,$satt_dir='',$pid = 0,$imageType = 1 ,$time = 0 , $module_type=1)
    {
        $data['name'] = $name;
        $data['att_dir'] = $att_dir;
        $data['satt_dir'] = $satt_dir;
        $data['att_size'] = $att_size;
        $data['att_type'] = $att_type;
        $data['image_type'] = $imageType;
        $data['module_type'] = $module_type;
        $data['time'] = $time ? $time : time();
        $data['pid'] = $pid;
        return self::create($data);
    }

    /**
     * TODO 获取分类图
     * @param $id
     * @return array
     */
    public static function getAll($id){
        $model = new self;
        $where['pid'] = $id;
        $where['module_type'] = 1;
        $model->where($where)->order('att_id desc');
        return $model->page($model,$where,'',24);
    }

    /**
     * TODO 获取单条信息
     * @param $value
     * @param string $field
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getInfo($value,$field = 'att_id'){
        $where[$field] = $value;
        $count = self::where($where)->count();
        if(!$count) return false;
        return self::where($where)->find()->toArray();
    }

    /*
     * 清除昨日海报
     * */
    public static function emptyYesterDayAttachment()
    {
        self::whereTime('time','yesterday')->where(['module_type'=>2])->delete();
    }
}