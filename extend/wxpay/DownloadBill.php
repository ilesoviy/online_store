<?php

namespace wxpay;

use think\Loader;

require_once 'lib/WxPayException.php';
Loader::import('wxpay.lib.WxPayApi');

/**
* 下载对账单
*
* 用法:
* 调用 \wxpay\DownloadBill::exec($date, $type) 即可
*/
class DownloadBill
{
    /**
     * @param  string $date 日期, 格式: yyyymmdd, 如20140603(当天的无法查询)
     * @param  string $type 账单类型,
     *                      ALL，返回当日所有订单信息，默认值
                            SUCCESS，返回当日成功支付的订单
                            REFUND，返回当日退款订单
                            RECHARGE_REFUND，返回当日充值退款订单（相比其他对账单多一栏“返还手续费”）
     *
     * @return array 账单数据
     */
    public static function exec($date, $type='ALL')
    {
        // 1.校检参数
        self::checkParams($date, $type);

        // 2.组装参数
        $input = new \WxPayDownloadBill();
        $input->SetBill_date($date);
        $input->SetBill_type($type);
        $result = \WxPayApi::downloadBill($input);

        // 3.处理返回结果
        if (empty($result)) {
           throw new \WxPayException('未查询到结果');
        } else {
            return $result;
        }
    }

    /**
     * 校检参数
     */
    private static function checkParams($date, $type)
    {
        // 检测时间格式
        $d = \DateTime::createFromFormat('Ymd', $date);
        if(!($d && $d->format('Ymd') == $date)) {
            throw new \WxPayException('$date格式不正确, 正确格式为: yyyymmdd, 如20170917');
        }

        $typeArr = ['ALL', 'SUCCESS', 'REFUND', 'RECHARGE_REFUND'];
        if (!in_array($type, $typeArr)) {
            throw new \WxPayException('$type参数错误, $type必须为ALL, SUCCESS, REFUND, RECHARGE_REFUND其中之一');
        }
    }
}