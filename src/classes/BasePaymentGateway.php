<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\classes;

use Yii;
use shopack\base\common\base\BaseGateway;
use shopack\aaa\common\enums\enuPaymentGatewayType;
use shopack\base\common\classes\IWebhook;

abstract class BasePaymentGateway extends BaseGateway
{
	// const PARAM_GATEWAY_TYPE           = 'gatewaytype';
	const PARAM_GATEWAY_COMMISSION_TYPE = 'gateway_commission_type'; // '%' | '$'
	const PARAM_GATEWAY_COMMISSION			= 'gateway_commission';

	const RESTRICTION_MIN_TRANSACTION_AMOUNT	= 'min_transaction_amount';
	const RESTRICTION_MAX_TRANSACTION_AMOUNT	= 'max_transaction_amount';
	const RESTRICTION_MAX_DAILY_TOTAL_AMOUNT	= 'max_daily_total_amount';

	const USAGE_LAST_TRANSACTION_DATE		= 'last_transaction_date';
	const USAGE_TODAY_USED_AMOUNT				= 'today_used_amount';

	abstract public function getPaymentGatewayType();

	public function getParametersSchema()
	{
		return [
			// [
			// 	'id' => self::PARAM_GATEWAY_TYPE,
			// 	'type' => 'combo',
			// 	'data' => enuPaymentGatewayType::getList(),
			// 	'label' => 'Payment Type',
			// 	'mandatory' => 1,
			// ],
			[
				'id' => self::PARAM_GATEWAY_COMMISSION_TYPE,
				'label' => 'Gateway Commission Type',
				'type' => 'combo',
				'data' => [
					"%" => "Percent",
					"$" => "Amount",
				],
				'default' => "%",
			],
			[
				'id' => self::PARAM_GATEWAY_COMMISSION,
				'type' => 'number',
				'label' => 'Gateway Commission',
			],
		];
	}

	public function getRestrictionsSchema()
	{
		return array_merge([
			[
				'id' => self::RESTRICTION_MIN_TRANSACTION_AMOUNT,
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
				'id' => self::RESTRICTION_MAX_TRANSACTION_AMOUNT,
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
				'id' => self::RESTRICTION_MAX_DAILY_TOTAL_AMOUNT,
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
		], parent::getRestrictionsSchema());
	}

	public function getUsagesSchema()
	{
		return array_merge([
			[
				'id' => self::USAGE_LAST_TRANSACTION_DATE,
				'type' => 'string',
				'label' => 'Last Transaction Date',
			],
			[
				'id' => self::USAGE_TODAY_USED_AMOUNT,
				'type' => 'string',
				'label' => 'Today Used Amount',
			],
		], parent::getUsagesSchema());
	}

}
