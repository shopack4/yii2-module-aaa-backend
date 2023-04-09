<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\sms;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;
use shopack\aaa\backend\classes\BaseSmsGateway;
use shopack\aaa\backend\classes\SmsSendResult;
use shopack\aaa\backend\classes\ISmsGateway;
use shopack\base\common\helpers\HttpHelper;

//https://github.com/ippanel/php-rest-sdk
//https://stackoverflow.com/questions/28858351/php-ssl-certificate-error-unable-to-get-local-issuer-certificate
class IPPanelSmsGateway
	extends BaseSmsGateway
	implements ISmsGateway
{
	const PARAM_APIKEY			= 'apikey';
	const PARAM_LINENUMBER	= 'number';

	public function getTitle()
	{
		return 'IP Panel';
	}

	public function getParametersSchema()
	{
		return array_merge([
			[
				'id' => self::PARAM_APIKEY,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'API Key',
				'style' => 'direction:ltr',
			],
			[
				'id' => self::PARAM_LINENUMBER,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Line Number',
				'style' => 'direction:ltr',
			],
		], parent::getParametersSchema());
	}

	public function getLineNumber()
	{
		return $this->extensionModel->gtwPluginParameters[self::PARAM_LINENUMBER] ?? null;
	}

	public function send(
		$message,
		$to,
		$from = null //null => use default in gtwPluginParameters
	) : SmsSendResult {
		try {

			// $message = urlencode(trim($message));
			$message = trim($message);

			if ($from == null)
				$from = $this->getLineNumber();

			$apiKey = $this->extensionModel->gtwPluginParameters[self::PARAM_APIKEY];

			$client = new \IPPanel\Client($apiKey);
			$messageId = $client->send(
				$from,    // originator
				[$to],    // recipients
				$message, // message
				"log"     // is logged
			);

			// if ($resultStatus < 200 || $resultStatus >= 300 || is_array($resultData))
			// 	return new SmsSendResult(false, $resultData[1] ?? null, $resultData[0] ?? null);

			// $result = json_decode($result, true);

			return new SmsSendResult(true, null, $messageId);

		} catch(\Exception $exp) {
			Yii::error($exp, __METHOD__);
			return new SmsSendResult(false, $exp->getMessage(), $exp->getCode());
		}

	}

	public function receive()
	{
		return [];
	}

}
