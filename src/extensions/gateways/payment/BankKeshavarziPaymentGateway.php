<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\payment;

use Yii;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\classes\IPaymentGateway;

class BankKeshavarziPaymentGateway
	extends AsanPardakhtPaymentGateway
{
	public function getTitle()
	{
		return 'Bank Keshavarzi';
	}

}
