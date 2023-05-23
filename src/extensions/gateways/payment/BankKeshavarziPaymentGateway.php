<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\payment;

use Yii;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\classes\IPaymentGateway;
use shopack\aaa\common\enums\enuPaymentGatewayType;

class BankKeshavarziPaymentGateway
	extends AsanPardakhtPaymentGateway
{
	public function getTitle()
	{
		return 'بانک کشاورزی';
	}

	public function getPaymentGatewayType()
	{
		return enuPaymentGatewayType::IranBank;
	}

}
