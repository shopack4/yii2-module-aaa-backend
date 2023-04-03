<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\sms;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;
// use shopack\base\helpers\Url;
// use shopack\base\common\helpers\Html;
// use shopack\base\helpers\ArrayHelper;
use shopack\aaa\backend\classes\BaseSmsGateway;
use shopack\aaa\backend\classes\SmsSendResult;
use shopack\aaa\backend\classes\ISmsGateway;
use shopack\base\common\classes\IWebhook;
use shopack\base\common\classes\WebhookTrait;
use shopack\base\common\helpers\HttpHelper;

//https://aws.asanak.ir/wiki
//Webhook
//http://.../app/gateway/webhook?key=3bf746f1-7dc6-463b-8710-05faa2740046&action=receivesms

class AsanakSmsGateway
	extends BaseSmsGateway
	implements ISmsGateway, IWebhook
{
	use WebhookTrait;

	const WEBHOOK_RECEIVE_SMS			= 'receivesms';

	//const URL_WEBSERVICE_SENDSMS = "http://www.asanak.ir/webservice/v1rest/sendsms";
	// const URL_WEBSERVICE_SENDSMS	= "http://panel.asanak.ir/webservice/v1rest/sendsms";
	const URL_WEBSERVICE_SENDSMS	= "https://panel.asanak.com/webservice/v1rest/sendsms";
	const PARAM_USERNAME					= 'username';
	const PARAM_PASSWORD					= 'password';
	const PARAM_LINENUMBER				= 'number';
	const VERB_SEND_SMS						= 'sendsms';

	public function getTitle()
	{
		return 'آسانک Rest: ارسال سیستمی، دریافت وب هوک';
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
		], parent::getParametersSchema(), $this->webhookTraitParameters());
	}

	public function getLineNumber()
	{
	}

	public function send(
		$message,
		$to,
		$from = null //null => use default in gtwPluginParameters
	) : SmsSendResult {
		if ($from == null)
			$from = $this->extensionModel->gtwPluginParameters[self::PARAM_LINENUMBER] ?? null;

		try
		{
			$params = [
				'Username'		=> $this->extensionModel->gtwPluginParameters[self::PARAM_USERNAME],
				'Password'		=> $this->extensionModel->gtwPluginParameters[self::PARAM_PASSWORD],
				'Source'      => $from,
				'Destination' => $to,
				'message'     => urlencode(trim($message)),
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
				]
			);

			if ($resultStatus < 200 || $resultStatus >= 300
					|| empty($resultData['refID']))
				return new SmsSendResult(false, $resultData['message'] ?? null);

			// $result = json_decode($result, true);

			return new SmsSendResult(true, $result['status'] ?? null, $result['refID'] ?? null);

		} catch(\Exception $exp) {
			Yii::error($exp, __METHOD__);
			return new SmsSendResult(false, $exp->getMessage());
		}

	}

	public function receive()
	{
		return [];
	}

	public function getWebhookCommands()
	{
		return [
			self::WEBHOOK_RECEIVE_SMS => [
				// 'id' => self::WEBHOOK_RECEIVE_SMS,
				// 'command' => self::WEBHOOK_RECEIVE_SMS,
				'title' => 'Receive Sms',
				// 'desc' => 'دریافت پیامک',
			],
		];
	}

	public function callWebhook($command)
	{
		if ($command === null) {
			Yii::error('Command not provided.', __METHOD__);
			throw new UnprocessableEntityHttpException('Command not provided.');
		}

		switch ($command) {
			case self::WEBHOOK_RECEIVE_SMS:
				$from = Yii::$app->request->GetOrPost(['from', 'Source']);
				if (empty($from))
					throw new UnprocessableEntityHttpException('from is not provided.');

				$to = Yii::$app->request->GetOrPost(['to', 'Destination']);

				$body = Yii::$app->request->GetOrPost(['body', 'MsgBody']);
				if (empty($body))
					throw new UnprocessableEntityHttpException('body is not provided.');
				$body = urldecode($body);

				$date = null; //Yii::$app->request->GetOrPost(['date', 'time', 'ReceiveTime']);
				return Yii::$app->shopack->messaging->newInboundMessage($from, $to, $body, $date);

				//Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
				return ['result' => true];
		}

		Yii::error('Command not found.', __METHOD__);
		throw new NotFoundHttpException('Command not found.');
	}

}
