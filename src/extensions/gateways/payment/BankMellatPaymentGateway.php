<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\payment;

use Yii;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\classes\IPaymentGateway;

class BankMellatPaymentGateway
	extends BasePaymentGateway
	implements IPaymentGateway
{
	const PARAM_TERMINAL_ID = 'terminalID';
	const PARAM_USERNAME = 'userName';
	const PARAM_PASSWORD = 'password';

	public function getTitle()
	{
		return 'Bank Mellat';
	}

	public function getParametersSchema()
	{
		return array_merge(parent::getParametersSchema(), [
			[
				'id' => self::PARAM_TERMINAL_ID,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Terminal ID',
			],
			[
				'id' => self::PARAM_USERNAME,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'User Name',
			],
			[
				'id' => self::PARAM_PASSWORD,
				'type' => 'password',
				'mandatory' => 1,
				'label' => 'Password',
			],
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
