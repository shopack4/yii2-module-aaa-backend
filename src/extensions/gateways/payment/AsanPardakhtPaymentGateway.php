<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\payment;

use Yii;
use yii\web\UnprocessableEntityHttpException;
use GuzzleHttp\Client;
use shopack\aaa\common\enums\enuPaymentGatewayType;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\classes\IPaymentGateway;

//https://github.com/shetabit/multipay/blob/master/src/Drivers/Asanpardakht/Asanpardakht.php

class AsanPardakhtPaymentGateway
	extends BasePaymentGateway
	implements IPaymentGateway
{
	const URL_APISERVER		= "https://ipgrest.asanpardakht.ir/v1/";
	const URL_PAYMENT			= "https://asan.shaparak.ir";

	const URLToken				= 'Token';
	const URLTime					= 'Time';
	const URLTranResult		= 'TranResult';
	const URLCardHash			= 'CardHash';
	const URLSettlement		= 'Settlement';
	const URLVerify				= 'Verify';
	const URLCancel				= 'Cancel';
	const URLReverse			= 'Reverse';

	const PARAM_USERNAME = 'userName';
	const PARAM_PASSWORD = 'password';
	const PARAM_MERCHANT_ID = 'merchantID';

	public function getTitle()
	{
		return 'آسان پرداخت';
	}

	public function getPaymentGatewayType()
	{
		return enuPaymentGatewayType::IranBank;
	}

	public function getParametersSchema()
	{
		return array_merge(parent::getParametersSchema(), [
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
			[
				'id' => self::PARAM_MERCHANT_ID,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Merchant ID',
			],
		]);
	}

	protected function callApi($method, $url, $data = []): array
	{
		$username = $this->extensionModel->gtwPluginParameters[self::PARAM_USERNAME];
		$password = $this->extensionModel->gtwPluginParameters[self::PARAM_PASSWORD];

		$client = new Client(['base_uri' => self::URL_APISERVER]);
		$response = $client->request($method, $url, [
			"json" => $data,
			"headers" => [
				'Content-Type' => 'application/json',
				'usr' => $username,
				'pwd' => $password
			],
			"http_errors" => false,
		]);

		return [
			'status_code' => $response->getStatusCode(),
			'content' => json_decode($response->getBody()->getContents(), true),
		];
	}

	public function prepare(&$gatewayModel, $onlinePaymentModel, $callbackUrl)
	{
		$merchant_id = $this->extensionModel->gtwPluginParameters[self::PARAM_MERCHANT_ID];

		$price = $onlinePaymentModel->onpAmount * 10; //toman -> rial
		$callBackUrl = urlencode($callbackUrl);

		$serverTime = $this->callApi('GET', self::URLTime)['content'];

		try {
			//--token
			$token = $this->callApi('POST', self::URLToken, [
				'serviceTypeId'							=> 1,
				'merchantConfigurationId'		=> $merchant_id,
				'localInvoiceId'						=> $onlinePaymentModel->onpID,
				'amountInRials'							=> $price,
				'localDate'									=> $serverTime,
				'callbackURL'								=> $callBackUrl,
				'paymentId'									=> "0",
				'additionalData'						=> '',
			]);
		} catch (\Exception $exp) {
			// echo "<div class=\"error\">{$E}</div>";
			throw new UnprocessableEntityHttpException('Error in prepare payment (' . $exp->getMessage() . ')');
		}

		if (!isset($token['status_code']) || $token['status_code'] != 200) {
			$this->throwFailed($token['status_code']);
		}

		$token = $token['content'];

		return [
			/* $response   */ 'ok',
			/* $trackID    */ $token,
			/* $paymentUrl */ [
				'post',
				self::URL_PAYMENT,
				'RefId' => $token,
				// 'mobileap' => , //set mobileap for get user cards
			],
		];
	}

	public function verify(&$gatewayModel, $onlinePaymentModel, $pgwResponse)
	{
		$merchant_id = $this->extensionModel->gtwPluginParameters[self::PARAM_MERCHANT_ID];

		$result = $this->callApi('GET', self::URLTranResult
			. '?merchantConfigurationId=' . $merchant_id
			. '&localInvoiceId=' . $onlinePaymentModel->onpID,
		);

		if (!isset($result['status_code']) || $result['status_code'] != 200) {
			$this->throwFailed($result['status_code']);
		}

		$payGateTransactionId = $result['content']['payGateTranID'];

		//step1: verify
		$verify_result = $this->callApi('POST', self::URLVerify, [
			'merchantConfigurationId' => (int)$merchant_id,
			'payGateTranId' => (int)$payGateTransactionId,
		]);

		if (!isset($verify_result['status_code']) or $verify_result['status_code'] != 200) {
			$this->throwFailed($verify_result['status_code']);
		}

		//step2: settlement
		$this->callApi('POST', self::URLSettlement, [
			'merchantConfigurationId' => (int)$merchant_id,
			'payGateTranId' => (int)$payGateTransactionId,
		]);

		//
		return [
			'ok',
			$result['content']['rrn'],
			// 'traceNo'				=> $payGateTransactionId,
			// 'referenceNo'		=> $result['content']['rrn'],
			// 'transactionId'	=> $result['content']['refID'],
			// 'cardNo'				=> $result['content']['cardNumber'],
		];
	}

	protected function throwFailed($status)
	{
		$translations = [
			400 => "bad request",
			401 => "unauthorized. probably wrong or unsent header(s)",
			471 => "identity not trusted to proceed",
			472 => "no records found",
			473 => "invalid merchant username or password",
			474 => "invalid incoming request machine ip. check response body to see your actual public IP address",
			475 => "invoice identifier is not a number",
			476 => "request amount is not a number",
			477 => "request local date length is invalid",
			478 => "request local date is not in valid format",
			479 => "invalid service type id",
			480 => "invalid payer id",
			481 => "incorrect settlement description format",
			482 => "settlement slices does not match total amount",
			483 => "unregistered iban",
			484 => "internal error for other reasons",
			485 => "invalid local date",
			486 => "amount not in range",
			487 => "service not found or not available for merchant",
			488 => "invalid default callback",
			489 => "duplicate local invoice id",
			490 => "merchant disabled or misconfigured",
			491 => "too many settlement destinations",
			492 => "unprocessable request",
			493 => "error processing special request for other reasons like business restrictions",
			494 => "invalid payment_id for governmental payment",
			495 => "invalid referenceId in additionalData",
			496 => "invalid json in additionalData",
			497 => "invalid payment_id location",
			571 => "misconfiguration OR not yet processed",
			572 => "misconfiguration OR transaction status undetermined",
			573 => "misconfiguraed valid ips for configuration OR unable to request for verification due to an internal error",
			574 => "internal error in uthorization",
			575 => "no valid ibans found for merchant",
			576 => "internal error",
			577 => "internal error",
			578 => "no default sharing is defined for merchant",
			579 => "cant submit ibans with default sharing endpoint",
			580 => "error processing special request"
		];

		if (array_key_exists($status, $translations))
			throw new UnprocessableEntityHttpException("Error ({$status}:{$translations[$status]}) in payment transaction");

		throw new UnprocessableEntityHttpException("Error ({$status}) in payment transaction");
	}

}
