<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */

namespace app\ebapi\model\store;

use app\core\behavior\GoodsBehavior;
use service\HookService;
use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 点赞收藏model
 * Class StoreProductRelation
 * @package app\ebapi\model\store
 */
class StoreProductRelation extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取用户点赞所有产品的个数
     * @param $uid
     * @return int|string
     */
    public static function getUserIdLike($uid = 0){
        $count = self::where('uid',$uid)->where('type','like')->count();
        return $count;
    }

    /**
     * 获取用户收藏所有产品的个数
     * @param $uid
     * @return int|string
     */
    public static function getUserIdCollect($uid = 0){
        $count = self::where('uid',$uid)->where('type','collect')->count();
        return $count;
    }

    /**
     * 添加点赞 收藏
     * @param $productId
     * @param $uid
     * @param $relationType
     * @param string $category
     * @return bool
     */
    public static function productRelation($productId,$uid,$relationType,$category = 'product')
    {
        if(!$productId) return self::setErrorInfo('产品不存在!');
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        $data = ['uid'=>$uid,'product_id'=>$productId,'type'=>$relationType,'category'=>$category];
        if(self::be($data)) return true;
        $data['add_time'] = time();
        self::set($data);
        HookService::afterListen('store_'.$category.'_'.$relationType,$productId,$uid,false,GoodsBehavior::class);
        return true;
    }

    /**
     * 批量 添加点赞 收藏
     * @param $productIdS
     * @param $uid
     * @param $relationType
     * @param string $category
     * @return bool
     */
    public static function productRelationAll($productIdS,$uid,$relationType,$category = 'product'){
        $res = true;
        if(is_array($productIdS)){
            self::beginTrans();
            foreach ($productIdS as $productId){
                $res = $res && self::productRelation($productId,$uid,$relationType,$category);
            }
            self::checkTrans($res);
            return $res;
        }
        return $res;
    }

    /**
     * 取消 点赞 收藏
     * @param $productId
     * @param $uid
     * @param $relationType
     * @param string $category
     * @return bool
     */
    public static function unProductRelation($productId,$uid,$relationType,$category = 'product')
    {
        if(!$productId) return self::setErrorInfo('产品不存在!');
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        self::where(['uid'=>$uid,'product_id'=>$productId,'type'=>$relationType,'category'=>$category])->delete();
        HookService::afterListen('store_'.$category.'_un_'.$relationType,$productId,$uid,false,GoodsBehavior::class);
        return true;
    }

    public static function productRelationNum($productId,$relationType,$category = 'product')
    {
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        return self::where('type',$relationType)->where('product_id',$productId)->where('category',$category)->count();
    }

    public static function isProductRelation($product_id,$uid,$relationType,$category = 'product')
    {
        $type = strtolower($relationType);
        $category = strtolower($category);
        return self::be(compact('product_id','uid','type','category'));
    }

    /*
     * 获取某个用户收藏产品
     * @param int uid 用户id
     * @param int $first 行数
     * @param int $limit 展示行数
     * @return array
     * */
    public static function getUserCollectProduct($uid,$page,$limit)
    {
        $list = self::where('A.uid',$uid)
            ->field('B.id pid,B.store_name,B.price,B.ot_price,B.sales,B.image,B.is_del,B.is_show')->alias('A')
            ->where('A.type','collect')->where('A.category','product')
            ->order('A.add_time DESC')->join('__STORE_PRODUCT__ B','A.product_id = B.id')
            ->page((int)$page,(int)$limit)->select()->toArray();
        foreach ($list as $k=>$product){
            if($product['pid']){
                $list[$k]['is_fail'] = $product['is_del'] && $product['is_show'];
            }else{
                unset($list[$k]);
            }
        }
        return $list;
    }

}