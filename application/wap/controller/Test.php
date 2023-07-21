<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/11
 */

namespace app\wap\controller;


use think\Url;
use think\Request;
use service\UtilService;
use service\JsonService;

// class Index extends AuthController
// class Index extends WapBasic
class Test extends IndexController
{ 
    //mobile
    public function index(Request $request)
    {   
        if ($request->isPost()){
            $data1= UtilService::postMore(['id'], $request);
            $data1['id']='post'.$data1['id'];
             return JsonService::successful($data1);
        }
        if ($request->isGet()){
            $data2= UtilService::getMore(['id'], $request);
            $data2['id']='get'.$data2['id'];
            return JsonService::successful($data2);
        }
    }
}