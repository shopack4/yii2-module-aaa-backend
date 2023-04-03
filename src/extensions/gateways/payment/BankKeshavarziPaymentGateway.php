<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\payment;

use Yii;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\classes\IPaymentGateway;

class BankKeshavarziPaymentGateway
	extends BasePaymentGateway
	implements IPaymentGateway
{
	public function getTitle()
	{
		return 'Bank Keshavarzi';
	}

	public function getParametersSchema()
	{
		return array_merge(parent::getParametersSchema(), [
		]);
	}

	public function prepare(&$paymentModel, $callbackUrl)
	{
	}

	public function run($controller, &$paymentModel, $callbackUrl)
	{
	}

	public function verify(&$paymentModel)
	{
	}

}
