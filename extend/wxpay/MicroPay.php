<?php

namespace wxpay;

use think\Loader;

Loader::import('wxpay.lib.WxPayMicroPay');

/**
* 刷卡支付
*
* 用法:
* 调用 \wxpay\MicroPay::pay($params) 即可
*/
class MicroPay extends WxPayBase
{
    /**
     * @param array  $params 订单信息
     * @param string $params['body'] 商品简单描述
     * @param string $params['out_trade_no'] 商户订单号, 要保证唯一性
     * @param string $params['total_fee'] 标价金额, 请注意, 单位为分!!!!!
     *
     * @param string $auth_code 授权码
     *
     * @return array 支付结果
     */
    public static function pay($params, $auth_code='')
    {
        // 1.校检参数
        $that = new self();
        if(!preg_match('/^1[0||2|3|4|5]\d{16}$/', $auth_code)) {
            throw new \WxPayException('授权码(auth_code)格式错误, 应为18位纯数字，以10、11、12、13、14、15开头');
        }
        $that->checkParams($params);

        // 2.组装参数
        $input = $that->getPostData($params, $auth_code);

        // 3.进行请求
        $microPay = new \MicroPay();
        $result = $microPay->pay($input);

        // 4.进行结果检验
        $that->checkResult($result);

        return $result;
    }

    // 组装请求参数
    private function getPostData($params, $auth_code)
    {
        $input = new \WxPayMicroPay();
        $input->SetAuth_code($auth_code);
        $input->SetBody($params['body']);
        $input->SetOut_trade_no($params['out_trade_no']);
        $input->SetTotal_fee($params['total_fee']);
        return $input;
    }
}