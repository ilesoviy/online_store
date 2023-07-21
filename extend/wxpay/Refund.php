<?php

namespace wxpay;

use think\Loader;

require_once 'lib/WxPayException.php';
Loader::import('wxpay.lib.WxPayApi');

/**
* 退款接口
*
* 调用 \wxpay\Refund::exec($params) 即可
*/
class Refund
{
    // 商户订单号(out_trade_no) or 微信订单号(transaction_id) 二选一, 建议优先使用微信订单号
    const QUERY_TYPE = 'out_trade_no';

    /**
     * 执行退款
     *
     * @param array  $params 订单信息
     * @param string $params['transaction_id'] 微信订单号(微信订单号与商户订单号二选一)
     * @param string $params['out_trade_no'] 商户订单号(微信订单号与商户订单号二选一)
     * @param string $params['out_refund_no'] 商户退款单号, 保证唯一性
     * @param string $params['total_fee'] 订单金额, 单位为分，只能为整数
     * @param string $params['refund_fee'] 退款金额, 单位为分，只能为整数
     *
     * @return array 退款订单信息
     */
    public static function exec($params)
    {
        // 1.检验参数
        self::checkParams($params);

        // 2.组装参数
        $input = new \WxPayRefund();
        if(isset($params['transaction_id'])) {
            $input->SetTransaction_id($params['transaction_id']);
        } elseif ($params['out_trade_no']) {
            $input->SetOut_trade_no($params['out_trade_no']);
        }
        $input->SetTotal_fee($params['total_fee']);
        $input->SetRefund_fee($params['refund_fee']);
        $input->SetOut_refund_no($params['out_refund_no']);
        $input->SetOp_user_id(\WxPayConfig::getMchid());
        $result = \WxPayApi::refund($input);

        // 3.结果检验
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

    /**
     * 校检参数
     */
    private static function checkParams($params)
    {
        $rule = [
            'out_trade_no'   => function($value) {
                if(self::QUERY_TYPE == 'out_trade_no' && empty($value)) {
                    return '前通过商户订单号退款, 商户订单号(out_trade_no)必须';
                }
                return true;
            },
            'transaction_id' => function() {
                if(self::QUERY_TYPE == 'transaction_id' && empty($value)) {
                    return '当前通过微信订单号退款, 微信订单号(transaction_id)必须';
                }
                return true;
            },
            'total_fee'      => 'require|integer|gt:0',
            'refund_fee'     => 'require|integer|gt:0|elt:total_fee',
            'out_refund_no'  => 'require'
        ];

        $msg = [
            'total_fee'      => '订单金额(total_fee)单位为分, 必须为正整数',
            'refund_fee'     => '退款金额(refund_fee)单位为分, 必须为正整数, 且小于等于总金额',
            'out_refund_no'  => '商户退款单号(out_refund_no)必须',
        ];

        $validate = new \think\Validate($rule, $msg);
        if (!$validate->check($params)) {
            throw new \WxPayException($validate->getError());
        }
    }

}