<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\payment;

use Yii;
use yii\web\UnprocessableEntityHttpException;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\classes\IPaymentGateway;
use shopack\aaa\common\enums\enuPaymentGatewayType;

//https://github.com/ecdco/docs/blob/master/IPG.md
//https://github.com/ecd-ipg/PurePHPSample

class ECDamavandPaymentGateway
	extends BasePaymentGateway
	implements IPaymentGateway
{
	const URL_PayRequest				= "https://ecd.shaparak.ir/ipg_ecd/PayRequest";
	const URL_PayStart					= "https://ecd.shaparak.ir/ipg_ecd/PayStart";
	const URL_PayConfirmation		= "https://ecd.shaparak.ir/ipg_ecd/PayConfirmation";

	const PARAM_TERMINAL_ID			= 'terminalID';
	const PARAM_KEY							= 'key';

	public function getTitle()
	{
		return 'الکترونیک کارت دماوند';
	}

	public function getPaymentGatewayType()
	{
		return enuPaymentGatewayType::IranBank;
	}

	public function getParametersSchema()
	{
		return array_merge(parent::getParametersSchema(), [
			[
				'id' => self::PARAM_TERMINAL_ID,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Terminal ID',
			],
			[
				'id' => self::PARAM_KEY,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Key',
			],
		]);
	}

	public function prepare(&$gatewayModel, $onlinePaymentModel, $callbackUrl)
	{
		$terminal_id = $this->extensionModel->gtwPluginParameters[self::PARAM_TERMINAL_ID];
		$key = $this->extensionModel->gtwPluginParameters[self::PARAM_KEY];

		$price = $onlinePaymentModel->onpAmount * 10; //toman -> rial
		$callBackUrl = urlencode($callbackUrl);

		$date = date("Y-m-d");
		$time = date("h:i");

		try {
			$params_string = $terminal_id
										 . $onlinePaymentModel->onpID
										 . $price
										 . $date
										 . $time
										 . $callBackUrl
										 . $key
			;

			$check_sum = sha1($params_string);

			$params = [
				'TerminalNumber'	=> $terminal_id,
				'BuyID'						=> $onlinePaymentModel->onpID,
				'Amount'					=> $price,
				'date'						=> $date,
				'time'						=> $time,
				'RedirectURL'			=> $callbackUrl,
				'Language'				=> 'fa',
				'CheckSum'				=> $check_sum,
			];

			$data_string = json_encode($params);

			$curl = curl_init();

			curl_setopt_array($curl, [
				CURLOPT_URL							=> self::URL_PayRequest,
				CURLOPT_RETURNTRANSFER	=> 1,
				CURLOPT_POST						=> 1,
				CURLOPT_POSTFIELDS			=> $data_string,
				CURLOPT_HTTPHEADER			=> [
					"cache-control: no-cache",
					"content-type: application/json",
				]
			]);

			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);

			if ($err)
				throw new \Exception('cURL Error #:' . $err);

			//{"State":1,"Res":"C5B2E37396DCC3B654D226FC937BC01F628EC968","ErrorDescription":"","ErrorCode":""}
			//{"State":0,"Res":"","ErrorDescription":"فرمت داده های ورودی نامعتبر است","ErrorCode":"101"}
			//{"State":0,"Res":"","ErrorDescription":"آدرس صفحه بازگشت معتبر نمی باشد","ErrorCode":"106"}
			//{"State":0,"Res":"","ErrorDescription":"آی پی پذیرنده اشتباه می باشد","ErrorCode":"118"}
			//{"State":0,"Res":"","ErrorDescription":"","ErrorCode":"124"}

			$response = $this->throwIfFailed($response);
			// $response = json_decode($response, true);

			// $res_State						= $response['State'] ?? 0;
			$res_Res							= $response['Res'] ?? '';
			// $res_ErrorCode				= $response['ErrorCode'] ?? '';
			// $res_ErrorDescription	= $response['ErrorDescription'] ?? '';

			// if ($res_State != 1) {
			// 	$err = $this->formatErrorInfo($res_ErrorCode, $res_ErrorDescription);
			// 	throw new \Exception('Error: ' . $err);
			// }

			return [
				/* $response   */ 'ok',
				/* $trackID    */ $res_Res,
				/* $paymentUrl */ [
					'post',
					self::URL_PayStart,
					'Token' => $res_Res,
				],
			];

		} catch (\Exception $exp) {
			throw new UnprocessableEntityHttpException('Error in prepare payment (' . $exp->getMessage() . ')');
		}
	}

	public function verify(&$gatewayModel, $onlinePaymentModel, $pgwResponse)
	{
		try
		{
			/*
			$pgwResponse: {
				'State'							: "1",
				'Amount'						: "10000",
				'ErrorCode'					: "",
				'ErrorDescription'	: "",
				'ReferenceNumber'		: "567890123456",
				'TrackingNumber'		: "678765",
				'BuyID'							: "9897676",
				'Token'							: "C5B2E37396DCC3B654D226FC937BC01F628EC968",
			}
			*/

			$pgwResponse = $this->throwIfFailed($pgwResponse);

			// $res_State						= $pgwResponse['State'] ?? 0;
			$res_Amount						= $pgwResponse['Amount'] ?? '';
			// $res_ErrorCode				= $pgwResponse['ErrorCode'] ?? '';
			// $res_ErrorDescription	= $pgwResponse['ErrorDescription'] ?? '';
			$res_ReferenceNumber	= $pgwResponse['ReferenceNumber'] ?? '';
			$res_TrackingNumber		= $pgwResponse['TrackingNumber'] ?? '';
			$res_BuyID						= $pgwResponse['BuyID'] ?? '';
			$res_Token						= $pgwResponse['Token'] ?? '';

			// if ($res_State != 1) {
			// 	$err = $this->formatErrorInfo($res_ErrorCode, $res_ErrorDescription);
			// 	throw new \Exception('Error: ' . $err);
			// }

			$price = $onlinePaymentModel->onpAmount * 10; //toman -> rial
			if ($res_Amount != $price)
				throw new \Exception('Invalid amounts');

			if ($res_BuyID != $onlinePaymentModel->onpID)
				throw new \Exception('Invalid payment id');

			//confirm payment
			$params = [
				'Token'	=> $res_Token,
			];

			$data_string = json_encode($params);

			$curl = curl_init();

			curl_setopt_array($curl, [
				CURLOPT_URL							=> self::URL_PayRequest,
				CURLOPT_RETURNTRANSFER	=> 1,
				CURLOPT_POST						=> 1,
				CURLOPT_POSTFIELDS			=> $data_string,
				CURLOPT_HTTPHEADER			=> [
					"cache-control: no-cache",
					"content-type: application/json",
				]
			]);

			$response = curl_exec($curl);
			$err = curl_error($curl);
			curl_close($curl);

			if ($err)
				throw new \Exception('cURL Error #:' . $err);

				$response = $this->throwIfFailed($response);
			// $response = json_decode($response, true);

			// $res_State						= $response['State'] ?? 0;
			// $res_Res							= $response['Res'] ?? '';
			// $res_ErrorCode				= $response['ErrorCode'] ?? '';
			// $res_ErrorDescription	= $response['ErrorDescription'] ?? '';

			// if ($res_State != 1) {
			// 	$err = $this->formatErrorInfo($res_ErrorCode, $res_ErrorDescription);
			// 	throw new \Exception('Error: ' . $err);
			// }

			//------------------
			return [
				'ok',
				$res_ReferenceNumber,
				// 'referenceNo'		=> $res_ReferenceNumber,
				// 'transactionId'	=> $res_TrackingNumber,
			];

		} catch (\Exception $exp) {
			throw new UnprocessableEntityHttpException('Error in verify payment (' . $exp->getMessage() . ')');
		}
	}

	protected function throwIfFailed($response)
	{
		if (empty($response))
			return [];

		if (is_array($response) == false)
			$response = json_decode($response, true);

		$res_State						= $response['State'] ?? 0;
		// $res_Res							= $response['Res'] ?? '';
		$res_ErrorCode				= $response['ErrorCode'] ?? '';
		$res_ErrorDescription	= $response['ErrorDescription'] ?? '';

		if ($res_State != 1) {
			$err = $this->formatErrorInfo($res_ErrorCode, $res_ErrorDescription);
			throw new \Exception('Error: ' . $err);
		}

		return $response;
	}

	protected function formatErrorInfo($ErrorCode, $ErrorDescription)
	{
		$err = '';

		if (empty($ErrorCode) == false)
			$err = '(' . $ErrorCode . ')';

		if (empty($ErrorDescription) == false) {
			if (empty($err) == false)
				$err .= ' ';

			$err .= $ErrorDescription;
		}

		if (empty($err))
			$err = 'unknown error';

		return $err;
	}

}
