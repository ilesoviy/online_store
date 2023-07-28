<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */

namespace app\admin\model\order;

use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 订单操作纪律model
 * Class StoreOrderStatus
 * @package app\admin\model\store
 */
class StoreOrderStatus extends ModelBasic
{
    use ModelTrait;

    /**
     * @param $oid
     * @param $type
     * @param $message
     */
   public static function setStatus($oid,$type,$message){
       $data['oid'] = (int)$oid;
       $data['change_type'] = $type;
       $data['change_message'] = $message;
       $data['change_time'] = time();
       self::set($data);
   }

    /**
     * @param $where
     * @return array
     */
    public static function systemPage($oid){
        $model = new self;
        $model = $model->where('oid',$oid);
        $model = $model->order('change_time asc');
        return self::page($model);
    }
    /**
     * @param $where
     * @return array
     */
    public static function systemPageMer($oid){
        $model = new self;
        $model = $model->where('oid',$oid);
//        $model = $model->where('change_type','LIKE','mer_%');
        $model = $model->order('change_time asc');
        return self::page($model);
    }
}