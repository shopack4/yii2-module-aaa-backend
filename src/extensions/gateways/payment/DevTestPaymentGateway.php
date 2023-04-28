<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\payment;

use Yii;
use shopack\base\common\helpers\Url;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\classes\IPaymentGateway;
use shopack\aaa\common\enums\enuPaymentGatewayType;

class DevTestPaymentGateway
	extends BasePaymentGateway
	implements IPaymentGateway
{
	//must be 127.0.0.1 for running
	const PARAM_KEY = 'key';

	public function getTitle()
	{
		return 'Dev Test';
	}

	public function getPaymentGatewayType()
	{
		return enuPaymentGatewayType::DevTest;
	}

	public function getParametersSchema()
	{
		return array_merge(parent::getParametersSchema(), [
			[
				'id' => self::PARAM_KEY,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Key',
			],
		]);
	}

	//list ($response, $trackID, $paymentUrl)
	public function prepare(&$gatewayModel, $onlinePaymentModel, $callbackUrl)
	{
		return [
			'ok',
			'track-' . $onlinePaymentModel->onpUUID,
			Url::to([
				'/aaa/online-payment/devtestpaymentpage',
				'paymentkey' => $onlinePaymentModel->onpUUID,
				'callback' => $callbackUrl,
			], true),
		];
	}

	public function run($controller, &$gatewayModel, $callbackUrl)
	{
	}

	public function verify(&$gatewayModel, $onlinePaymentModel)
	{
		return [
			// 'ref-' . $onlinePaymentModel->onpUUID,
			'this is response',
		];
	}

}
