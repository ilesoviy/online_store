<?php

namespace wxpay;

use think\Loader;

require_once 'lib/WxPayException.php';
Loader::import('wxpay.lib.WxPayApi');

/**
* 退款查询
*
* 调用 \wxpay\Refund::exec($params) 即可
*/
class RefundQuery
{
    // 商户订单号(out_trade_no) or 微信订单号(transaction_id) or 商户退款单号(out_refund_no) or 微信退款单号(refund_id) 四选一, 建议优先使用微信订单号
    const QUERY_TYPE = 'out_trade_no';

    /**
     * 执行请求
     *
     * @param  string $query_no 查询号
     * @return array           订单信息
     */
    public static function exec($query_no)
    {
        $input = new \WxPayRefundQuery();

        switch (self::QUERY_TYPE) {
            case 'transaction_id':
                $input->SetTransaction_id($query_no);
                break;
            case 'out_trade_no':
                $input->SetOut_trade_no($query_no);
                break;
            case 'out_refund_no':
                $input->SetOut_refund_no($query_no);
                break;
            case 'refund_id':
                $input->SetRefund_id($query_no);
                break;
            default:
                throw new \WxPayException('参数错误, 请在四种查询方式中选择一种');
                break;
        }

        $result = \WxPayApi::refundQuery($input);

        // 3.处理返回结果
        if(!(array_key_exists("return_code", $result)
                    && array_key_exists("result_code", $result)
                    && $result["return_code"] == "SUCCESS"
                    && $result["result_code"] == "SUCCESS"))
        {
            if(empty($result['return_msg']) || $result['return_msg'] == 'OK') {
                throw new \WxPayException('退款错误: '.$result['err_code']."  原因:".$result['err_code_des']);
            } else {
                throw new \WxPayException('退款错误: '.$result['return_msg']);
            }
        } else {
            return $result;
        }
    }

}