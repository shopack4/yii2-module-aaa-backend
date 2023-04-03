<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\classes;

use Yii;
use shopack\base\common\base\BaseGateway;
use shopack\aaa\common\enums\enuPaymentGatewayType;
use shopack\base\common\classes\IWebhook;

class BasePaymentGateway extends BaseGateway
{
	const PARAM_GATEWAY_TYPE           = 'gatewaytype';
	const PARAM_MAX_DAILY_TOTAL_AMOUNT = 'max_daily_total_amount';
	const PARAM_MIN_TRANSACTION_AMOUNT = 'min_transaction_amount';
	const PARAM_MAX_TRANSACTION_AMOUNT = 'max_transaction_amount';
	const PARAM_GATEWAY_COMMISSION     = 'gateway_commission';
	const STATE_LAST_TRANSACTION_DATE  = 'last_transaction_date';
	const STATE_TODAY_USED_AMOUNT      = 'today_used_amount';

	public function getParametersSchema()
	{
		return [
			[
				'id' => self::PARAM_GATEWAY_TYPE,
				'type' => 'combo',
				'data' => enuPaymentGatewayType::getList(),
				'label' => 'Payment Type',
				'mandatory' => 1,
			],
			[
				'id' => self::PARAM_MAX_DAILY_TOTAL_AMOUNT,
				'type' => 'number',
				'label' => 'Maximum Daily Total Amount',
				'fieldOptions' => [
					'addon' => [
						'append' => [
							'content' => 'تومان',
						],
					],
				],
			],
			[
				'id' => self::PARAM_MIN_TRANSACTION_AMOUNT,
				'type' => 'number',
				'label' => 'Minimum Transaction Amount',
				'default' => 1000,
				'fieldOptions' => [
					'addon' => [
						'append' => [
							'content' => 'تومان',
						],
					],
				],
			],
			[
				'id' => self::PARAM_MAX_TRANSACTION_AMOUNT,
				'type' => 'number',
				'label' => 'Maximum Transaction Amount',
				'fieldOptions' => [
					'addon' => [
						'append' => [
							'content' => 'تومان',
						],
					],
				],
			],
			[
				'id' => self::PARAM_GATEWAY_COMMISSION,
				'type' => 'number',
				'label' => 'Gateway Commission',
				'fieldOptions' => [
					'addon' => [
						'append' => [
							'content' => 'درصد',
						],
					],
				],
			],
		];
	}

}
