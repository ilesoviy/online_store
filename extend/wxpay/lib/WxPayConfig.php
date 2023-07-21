<?php
/**
* 	配置账号信息
*/
use app\admin\model\system\SystemConfig;
class WxPayConfig
{
	//=======【基本信息设置】=====================================
	//
	/**
	 * TODO: 修改这里配置为您自己申请的商户信息
	 * 微信公众号信息配置
	 *
	 * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
	 *
	 * MCHID：商户号（必须配置，开户邮件中可查看）
	 *
	 * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
	 * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
	 *
	 * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
	 * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
	 * @var string
	 */
	const APPID     = '';/**wx67e376dc90249f8d**/
	const MCHID     = '';/**1528794781**/
	const KEY       = '';/**cfbcf0a570aa5fa0846440c1df41745b**/
	const APPSECRET = '';/**0e3c9ceb873287147b43b9ad21bf9d15**/

	// ======【开发模式与生产模式】===================================
	/**
	 * 微信支付需要获取用户的真实ip, 本地开发就需要设定一个固定的IP,
	 * 处于生产模式, 请务必将次改为false, 用于获取用户真实IP!!!!
	 */
	const WXPAY_DEBUG   = true;

	//=======【异步通知地址】=====================================
	const NOTIFY_URL = 'http://www.weixin.qq.com/wxpay/pay.php';



	/*********************  以下配置不建议修改   *********************/

	//=======【证书路径设置】=====================================
	/**
	 * TODO：设置商户证书路径
	 * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
	 * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
	 * @var path
	 */
	const SSLCERT_PATH = EXTEND_PATH.'/wxpay/cert/apiclient_cert.pem';
	const SSLKEY_PATH = EXTEND_PATH.'/wxpay/cert/apiclient_key.pem';

	//=======【curl代理设置】===================================
	/**
	 * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
	 * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
	 * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
	 * @var unknown_type
	 */
	const CURL_PROXY_HOST = "0.0.0.0";//"10.152.18.220";
	const CURL_PROXY_PORT = 0;//8080;

	//=======【上报信息配置】===================================
	/**
	 * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
	 * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
	 * 开启错误上报。
	 * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
	 * @var int
	 */
	const REPORT_LEVENL = 1;

	// ======【日志文件目录】===================================
	const WXPAY_LOG     = RUNTIME.'/wxpay';

	public static function getAppid()
    {   
    	$appid = SystemConfig::getValue('pay_weixin_appid');
        return $appid;
    }
    public static function getMchid()
    {   
    	$mchid = SystemConfig::getValue('pay_weixin_mchid');
        return $mchid;
    }
    public static function getKey()
    {   
    	$key = SystemConfig::getValue('pay_weixin_key');
        return $key;
    }
    public static function getAppsecret()
    {   
    	$appsecret = SystemConfig::getValue('pay_weixin_appsecret');
        return $appsecret;
    }
}
