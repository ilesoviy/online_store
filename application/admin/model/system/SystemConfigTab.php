<?php

/**

 *

 * @author: xaboy<365615158@qq.com>

 * @day: 2017/11/02

 */

namespace app\admin\model\system;

use traits\ModelTrait;
use basic\ModelBasic;
use think\Db;

/**
 * 配置分类model
 *
 * Class SystemConfigTab
 * @package app\admin\model\system
 */
class SystemConfigTab extends ModelBasic {

    use ModelTrait;
    /**
     * 获取单选按钮或者多选按钮的显示值
     * */
    public static function getRadioOrCheckboxValueInfo($menu_name,$value){
        $parameter = array();
        $option = array();
        $config_one = SystemConfig::getOneConfig('menu_name',$menu_name);
        $parameter = explode("\n",$config_one['parameter']);
        foreach ($parameter as $k=>$v){
            if(isset($v) && strlen($v)>0){
                $data = explode('=>',$v);
                $option[$data[0]] = $data[1];
            }
        }
        $str = '';
        if(is_array($value)){
            foreach ($value as $v){
                $str .= $option[$v].',';
            }
        }else{
            $str .= !empty($value)?$option[$value]:$option[0];
        }
        return $str;
    }
    /**
     * 插入数据到数据库
     * */
    public static function set($data)
    {
        return self::create($data);
    }
    /**
     * 获取全部
     * */
    public static function getAll($type = 0){
        $where['status'] = 1;
        if($type>-1)$where['type'] = $type;
        return Db::name('SystemConfigTab')->where($where)->select();
    }

    /**
     * 获取配置分类
     * */
    public static function getSystemConfigTabPage($where = array())

    {
        $model = new self;
        if($where['title'] != '') $model = $model->where('title','LIKE',"%$where[title]%");
        if($where['status'] != '') $model = $model->where('status',$where['status']);
        return self::page($model,$where);

    }

    public static function edit($data,$id,$field='id')

    {

        return self::update($data,[$field=>$id]);

    }

    /**
     * 更新数据
     * @access public
     * @param array      $data  数据数组
     * @param array      $where 更新条件
     * @param array|true $field 允许字段
     * @return $this
     */
    public static function update($data = [], $where = [], $field = null)
    {
        $model = new static();
        if (!empty($field)) {
            $model->allowField($field);
        }
        $result = $model->isUpdate(true)->save($data, $where);
        return $model;
    }
}