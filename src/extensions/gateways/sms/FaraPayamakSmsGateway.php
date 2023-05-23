<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\sms;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;
use Farapayamak\Rest_Client;
use shopack\aaa\backend\classes\BaseSmsGateway;
use shopack\aaa\backend\classes\SmsSendResult;
use shopack\aaa\backend\classes\ISmsGateway;
use shopack\base\common\helpers\HttpHelper;

//https://farapayamak.ir
class FaraPayamakSmsGateway
	extends BaseSmsGateway
	implements ISmsGateway
{
	const PARAM_USERNAME		= 'username';
	const PARAM_PASSWORD		= 'password';
	// const PARAM_LINENUMBER	= 'number';
	const PARAM_BODY_ID			= 'bodyid';

	public function getTitle()
	{
		return 'فرا پیامک';
	}

	public function getParametersSchema()
	{
		return array_merge([
			[
				'id' => self::PARAM_USERNAME,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'User Name',
				'style' => 'direction:ltr',
			],
			[
				'id' => self::PARAM_PASSWORD,
				'type' => 'password',
				'mandatory' => 1,
				'label' => 'Password',
				'style' => 'direction:ltr',
			],
			[
				'id' => self::PARAM_BODY_ID,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Body ID',
				'style' => 'direction:ltr',
			],
			// [
			// 	'id' => self::PARAM_LINENUMBER,
			// 	'type' => 'string',
			// 	'mandatory' => 1,
			// 	'label' => 'Line Number',
			// 	'style' => 'direction:ltr',
			// ],
		], parent::getParametersSchema());
	}

	public function send(
		$message,
		$to,
		$from = null //null => use default in gtwPluginParameters
	) : SmsSendResult {
		$username = $this->extensionModel->gtwPluginParameters[self::PARAM_USERNAME];
		$password = $this->extensionModel->gtwPluginParameters[self::PARAM_PASSWORD];
		$bodyid = $this->extensionModel->gtwPluginParameters[self::PARAM_BODY_ID];

		try {
			$restClient = new Rest_Client($username, $password);
			$result = $restClient->BaseServiceNumber($message, $to, $bodyid);

			// $result["Value"] 0
			// $result["RetStatus"] 35
			// $result["StrRetStatus"] "InvalidData"

			if ($result["RetStatus"] != 1)
				return new SmsSendResult(false, $result["StrRetStatus"], $result["RetStatus"]);

			// $result = json_decode($result, true);

			return new SmsSendResult(true, null, $result["Value"]);

		} catch(\Exception $exp) {
			Yii::error($exp, __METHOD__);
			return new SmsSendResult(false, $exp->getMessage());
		}

	}

	public function receive()
	{
		return [];
	}

}
