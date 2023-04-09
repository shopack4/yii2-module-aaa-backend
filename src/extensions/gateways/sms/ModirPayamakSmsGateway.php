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

//https://modirpayamak.com
class ModirPayamakSmsGateway
	extends BaseSmsGateway
	implements ISmsGateway
{
	const URL_WEBSERVICE_SENDSMS	= "https://ippanel.com/services.jspd";
	const PARAM_USERNAME					= 'username';
	const PARAM_PASSWORD					= 'password';
	const PARAM_LINENUMBER				= 'number';

	public function getTitle()
	{
		return 'مدیر پیامک';
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

		if ($from == null)
			$from = $this->getLineNumber();

		try {
			$params = [
				'uname'		=> $this->extensionModel->gtwPluginParameters[self::PARAM_USERNAME],
				'pass'		=> $this->extensionModel->gtwPluginParameters[self::PARAM_PASSWORD],
				'from'		=> $from,
				'to'			=> json_encode([$to]),
				'message'	=> urlencode(trim($message)),
				'op'			=>'send',
			];

			/******************************************************/
			list ($resultStatus, $resultData) = HttpHelper::callApi(
				self::URL_WEBSERVICE_SENDSMS,
				HttpHelper::METHOD_POST,
				[],
				$params,
				[],
				[
					CURLOPT_HTTPHEADER => ['Accept: application/json'],
					CURLOPT_HEADER => 0,
					// CURLOPT_TIMEOUT => 30,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_RETURNTRANSFER => true,
				]
			);

			if ($resultStatus < 200 || $resultStatus >= 300 || is_array($resultData))
				return new SmsSendResult(false, $resultData[1] ?? null, $resultData[0] ?? null);

			// $result = json_decode($result, true);

			return new SmsSendResult(true, null, $resultData);

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
