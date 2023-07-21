<?php

namespace wxpay;

use think\Loader;
use think\Validate;

require_once 'lib/WxPayException.php';
Loader::import('wxpay.lib.WxPayApi');

/**
* 支付基础类
*/
class WxPayBase
{
    /**
     * 校检参数
     */
    protected function checkParams($params)
    {
        $validate = new Validate([
            'body'         => 'require',
            'out_trade_no' => 'require|max:32',
            'total_fee'    => 'require|integer|gt:0',
        ]);

        $msg = [
            'body'         => '商品简单描述(body)必须',
            'out_trade_no' => '商户订单号(out_trade_no)必须',
            'total_fee'    => '订单金额(total_fee)单位为分, 必须为正整数',
        ];

        if (!$validate->check($params)) {
            throw new \WxPayException($validate->getError());
        }
    }

    // 结果检测
    protected function checkResult($result)
    {
        if(!(array_key_exists("return_code", $result)
                    && array_key_exists("result_code", $result)
                    && $result["return_code"] == "SUCCESS"
                    && $result["result_code"] == "SUCCESS"))
        {
            if(empty($result['return_msg']) || $result['return_msg'] == 'OK') {
                throw new \WxPayException('微信支付错误: '.$result['err_code']."  原因:".$result['err_code_des']);
            } else {
                throw new \WxPayException('微信支付错误: '.$result['return_msg']);
            }
        }
    }
}
