<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/13
 */

namespace app\wap\model\store;


use basic\ModelBasic;
use think\Db;
use traits\ModelTrait;
use think\Session;

class StoreProductAttr extends ModelBasic
{

    use ModelTrait;

    protected function getAttrValuesAttr($value)
    {
        return explode(',',$value);
    }

    public static function storeProductAttrValueDb()
    {
        return Db::name('StoreProductAttrValue');
    }


    /**
     * 获取商品属性数据
     * @param $productId
     * @return array
     */
    public static function getProductAttrDetail($productId)
    {

        $attr = self::where('product_id',$productId)->select()->toArray()?:[];
        if(count($attr)>0){
            foreach ($attr as $key => $value) {
                $attr_name=explode('~', $value['attr_name']);
                if(count($attr_name)<2){
                    $attr_name[1]='';
                }

                 if (!Session::has('lang')) {
                //en
                $attr[$key]['attr_name']=$attr_name[1];
                }else{
                  //cn 
                  $attr[$key]['attr_name']=$attr_name[0];
                }

                foreach ($value['attr_values'] as $k => $v) {
                    $value_name=explode('~', $v);
                    if(count($value_name)<2){
                    $value_name[1]='';
                    }
                    if (!Session::has('lang')) {
                    //en
                    $attr[$key]['attr_values'][$k]=$value_name[1];
                }else{
                  //cn 
                     $attr[$key]['attr_values'][$k]=$value_name[0];
                }
                }

            }
        }
        $_values = self::storeProductAttrValueDb()->where('product_id',$productId)->select();
        $values = [];
        foreach ($_values as $value){
            $suk=explode(',', $value['suk']);
            foreach ($suk as $k => $v) {
               $value_name=explode('~', $v);
                    if(count($value_name)<2){
                    $value_name[1]='';
                    }
                    if (!Session::has('lang')) {
                    //en
                    $suk[$k]=$value_name[1];
                }else{
                  //cn 
                    $suk[$k]=$value_name[0];
                }
            }
            $value['suk']=implode(',', $suk);
            $values[$value['suk']] = $value;
        }
        return [$attr,$values];
    }

    public static function uniqueByStock($unique)
    {
        return self::storeProductAttrValueDb()->where('unique',$unique)->value('stock')?:0;
    }

    public static function uniqueByAttrInfo($unique, $field = '*')
    {
         $result=self::storeProductAttrValueDb()->field($field)->where('unique',$unique)->find();
           $suk=explode(',', $result['suk']);
            foreach ($suk as $k => $v) {
               $value_name=explode('~', $v);
                    if(count($value_name)<2){
                    $value_name[1]='';
                    }
                    if (!Session::has('lang')) {
                    //en
                    $suk[$k]=$value_name[1];
                }else{
                  //cn 
                    $suk[$k]=$value_name[0];
                }
            }
            $result['suk']=implode(',', $suk);
         return $result;
    }

    public static function issetProductUnique($productId,$unique)
    {
        $res = self::be(['product_id'=>$productId]);
        if($unique){
            return $res && self::storeProductAttrValueDb()->where('product_id',$productId)->where('unique',$unique)->count() > 0;
        }else{
            return !$res;
        }
    }

}