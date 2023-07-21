<?php

namespace wxpay;

use think\Loader;

Loader::import('wxpay.lib.WxPayJsPay');

/**
*  公众号支付/小程序支付
*
* 公众号支付用法:
* 调用 \wxpay\JsapiPay::getPayParams($params) 即可
*
* 小程序支付用法:
* 调用 \wxpay\JsapiPay::getPayParams($params, $code) 即可
* 或
* 调用 \wxpay\JsapiPay::getParams($params, $openId) 即可
*
*
* ----------------- 求职 ------------------
* 姓名: zhangchaojie      邮箱: zhangchaojie_php@qq.com  应届生
* 期望职位: PHP初级工程师   地点: 深圳(其他城市亦可)
* 能力:
*     1.熟悉小程序开发, 前后端皆可
*     2.后端, PHP基础知识扎实, 熟悉ThinkPHP5框架, 用TP5做过CMS, 商城, API接口
*     3.MySQL, Linux都在进行进一步学习
*/
class JsapiPay extends WxPayBase
{
    /**
     * 获取支付参数
     * @param  array $params 订单数组
     * @param  string $code  登录凭证(公众号支付无需, 小程序支付需要)
     *
     * 小程序支付, 如果已将openId存入数据库, 可以直接调用 getParams($params, $openId)进行支付, 无需去服务器请求获取openID
     */
    public static function getPayParams($params, $code='')
    {
        $tools = new \WxPayJsPay();
        $openId = $tools->GetOpenid($code);
        return self::getParams($params, $openId);
    }

    /**
     * 获取预支付信息
     *
     * @param array  $params 订单信息
     * @param string $params['body'] 商品简单描述
     * @param string $params['out_trade_no'] 商户订单号, 要保证唯一性
     * @param string $params['total_fee'] 标价金额, 请注意, 单位为分!!!!!
     *
     * @param string $openId 用户身份标识
     *
     * @return array 预支付信息
     */
    public static function getParams($params, $openId='')
    {
        // 1.校检参数
        $that = new self();
        $that->checkParams($params);

        // 2.组装参数
        $input = $that->getPostData($params, $openId);

        // 3.获取预支付信息
        $order = \WxPayApi::unifiedOrder($input);

        // 4.进行结果检验
        $that->checkResult($order);

        // 5.组装支付参数
        $tools = new \WxPayJsPay();
        $jsApiParameters = $tools->GetJsApiParameters($order);

        // 6.返回支付参数
        return $jsApiParameters;
    }


    // 组装请求参数
    private function getPostData($params, $openId)
    {
        $input  = new \WxPayUnifiedOrder();
        $input->SetOpenid($openId);
        $input->SetTrade_type("JSAPI");
        // $input->SetGoods_tag("test");
        $input->SetBody($params['body']);
        $input->SetTotal_fee($params['total_fee']);
        $input->SetNotify_url(\WxPayConfig::NOTIFY_URL);
        $input->SetOut_trade_no($params['out_trade_no']);
        return $input;
    }
}
