<?php

namespace wxpay;

use think\Loader;

require_once 'lib/WxPayException.php';
Loader::import('wxpay.lib.WxPayApi');

/**
* 订单查询
*
* 用法:
* 调用 \wxpay\Query::exec($query_no) 即可
*/
class Query
{
    // 商户订单号(out_trade_no) or 微信订单号(transaction_id) 二选一, 建议优先使用微信订单号
    const QUERY_TYPE = 'out_trade_no';

    /**
     * 执行请求
     *
     * @param  string $query_no 微信订单号或者商户订单号, 二选一
     * @return array           订单信息
     */
    public static function exec($query_no)
    {
        // 1.组装请求数组
        $input = new \WxPayOrderQuery();

        if (self::QUERY_TYPE == 'transaction_id') {
            $input->SetTransaction_id($query_no);
        } else {
            $input->SetOut_trade_no($query_no);
        }

        // 2.进行请求
        $result = \WxPayApi::orderQuery($input);

        // 3.处理返回结果
        if(!(array_key_exists("return_code", $result)
                    && array_key_exists("result_code", $result)
                    && $result["return_code"] == "SUCCESS"
                    && $result["result_code"] == "SUCCESS"))
        {
            if(empty($result['return_msg']) || $result['return_msg'] == 'OK') {
                throw new \WxPayException('订单查询错误: '.$result['err_code']."  原因:".$result['err_code_des']);
            } else {
                throw new \WxPayException('订单查询错误: '.$result['return_msg']);
            }
        } else {
            return $result;
        }
    }
}
