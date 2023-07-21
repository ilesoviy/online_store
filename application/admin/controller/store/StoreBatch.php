<?php
/**
 * Created by PhpStorm.
 * User: sugar1569
 * Date: 2018/5/24
 * Time: 10:58
 */

namespace app\admin\controller\store;


use app\admin\controller\AuthController;
use service\JsonService as Json;
use think\db;
use think\Config;
use service\UtilService as Util;
use app\admin\model\store\StoreProduct as ProductModel;
use app\admin\model\store\StoreProductAttr;
use app\admin\model\store\StoreProductAttrValue;
use app\admin\model\store\StoreProductAttrResult;
use app\admin\model\store\StoreProductRelation;
/**
 * 清除默认数据理控制器
 * Class SystemclearData
 * @package app\admin\controller\system
 *
 */
class StoreBatch  extends AuthController
{

    public function index(){
        $post=Util::postMore([
            'sales',
            'ficti',
            'stock'
        ]);
        $product=ProductModel::select();
        $res=0;
        //销量
        if($post['sales'] !=0){
            $sales=$post['sales']/100;
            foreach ($product as $key => $value) {
                ProductModel::where('id',$value['id'])->update(['sales'=>$value['sales']*(1+$sales)]);
            }
            $res=1;
        }
        //虚拟销量
        if($post['ficti'] !=0){
            $ficti=$post['ficti']/100;
            foreach ($product as $key => $value) {
                ProductModel::where('id',$value['id'])->update(['ficti'=>$value['ficti']*(1+$ficti)]);
            }
            $res=1;
        }
        //库存
        if($post['stock'] !=0){
            $stock=$post['stock']/100;
            foreach ($product as $key => $value) {
                ProductModel::where('id',$value['id'])->update(['stock'=>$value['stock']*(1+$stock)]);

             $product_attr_value=Db::name('store_product_attr_value')->where('product_id='.$product[$key]['id'])->select();
             foreach ($product_attr_value as $keys => $values) {
            Db::name('store_product_attr_value')->where('stock', $product_attr_value[$keys]['stock'])->update(['stock' =>  $product_attr_value[$keys]['stock']*(1+$stock)]);
             }
              $product_attr_result=StoreProductAttrResult::getResult($product[$key]['id']);
              if($product_attr_result&&is_array($product_attr_result)&&is_array($product_attr_result['value'])){
                // dump($product_attr_result);die;
            foreach($product_attr_result['value'] as $k =>$v){
                 $product_attr_result['value'][$k]['sales']=$product_attr_result['value'][$k]['sales']*(1+$stock);
            }
        }    
        Db::name('store_product_attr_result')->where('product_id', $product[$key]['id'])->update(['result' => json_encode($product_attr_result)]);
            }
            $res=1;
        }
        if($res==1){
            return $this->successful('成功');
        }
      return $this->fetch();
    }
    
}