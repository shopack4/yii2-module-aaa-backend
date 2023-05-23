<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\classes;

interface IPaymentGateway
{
	public function prepare(&$gatewayModel, $onlinePaymentModel, $callbackUrl);
	public function verify(&$gatewayModel, $onlinePaymentModel, $pgwResponse);
}
