<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\payment;

use soapclient;
use SoapFault;
use Yii;
use yii\web\UnprocessableEntityHttpException;
use shopack\base\common\helpers\Url;
use shopack\aaa\common\enums\enuPaymentGatewayType;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\classes\IPaymentGateway;

//https://github.com/shetabit/multipay/blob/master/src/Drivers/Asanpardakht/Asanpardakht.php

class AsanPardakhtSoapPaymentGateway
	extends BasePaymentGateway
	implements IPaymentGateway
{
	// const URL_WEBSERVICE = "https://services.asanpardakht.net/paygate/merchantservices.asmx?WSDL";
	const URL_WEBSERVICE = "https://ipgsoap.asanpardakht.ir/paygate/merchantservices.asmx?WSDL";
	const URL_PAYMENT = "https://asan.shaparak.ir";

	const PARAM_KEY = 'key';
	const PARAM_IV = 'iv';
	const PARAM_USERNAME = 'userName';
	const PARAM_PASSWORD = 'password';
	const PARAM_MERCHANT_ID = 'merchantID';

	public function getTitle()
	{
		return 'Asan Pardakht (Soap)';
	}

	public function getPaymentGatewayType()
	{
		return enuPaymentGatewayType::IranBank;
	}

	public function getParametersSchema()
	{
		return array_merge(parent::getParametersSchema(), [
			[
				'id' => self::PARAM_KEY,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Key',
			],
			[
				'id' => self::PARAM_IV,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'IV',
			],
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

	public function prepare(&$gatewayModel, $onlinePaymentModel, $callbackUrl)
	{
		$username = $this->extensionModel->gtwPluginParameters[self::PARAM_USERNAME];
		$password = $this->extensionModel->gtwPluginParameters[self::PARAM_PASSWORD];
		$merchant_id = $this->extensionModel->gtwPluginParameters[self::PARAM_MERCHANT_ID];

		$orderId = $onlinePaymentModel->onpID;
		$price = $onlinePaymentModel->onpAmount * 10; //toman -> rial
		$localDate = date("Ymd His");
		$additionalData = "";
		$callBackUrl = urlencode($callbackUrl);
		$req = "1,{$username},{$password},{$orderId},{$price},{$localDate},{$additionalData},{$callBackUrl},0";
			//اگر قصد واريز پول به چند شبا را داريد، خط زير را به رشته بالايی اضافه کنيد
			// ,Shaba1,Mablagh1,Shaba2,Mablagh2,Shaba3,Mablagh3
			//حداکثر تا 7 شبا می‌توانيد به رشته خود اضافه کنيد
		$encryptedRequest = $this->encrypt($req);

		try {
			$opts = [
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
				],
			];

			$params = [
				'stream_context' => stream_context_create($opts),
			];

			$client = @new soapclient(self::URL_WEBSERVICE, $params);

		} catch (SoapFault $exp) {
			// echo "<div class=\"error\">{$E}</div>";
			throw new UnprocessableEntityHttpException('Error in prepare payment (' . $exp->faultstring . ')');
		}

		$params = array(
			'merchantConfigurationID' => $merchant_id,
			'encryptedRequest' => $encryptedRequest
		);

		$result = $client->RequestOperation($params);
		if (!$result)
			throw new UnprocessableEntityHttpException('Error in request transaction');
			// or die("<div class=\"error\">خطای فراخوانی متد درخواست تراکنش.</div>");

		$result = $result->RequestOperationResult;
		$resultParts = explode(',', $result);
		if ($resultParts[0] == '0') {
			unset($resultParts[0]);
			$RefId = implode(',', $resultParts);
			return [
				/* $response   */ 'ok',
				/* $trackID    */ $RefId,
				/* $paymentUrl */ ['post', self::URL_PAYMENT, 'RefId' => $RefId],
			];

			// echo "<script language='javascript' type='text/javascript'>RedirctToIPG('" . substr($result,2) . "');</script>";
		}

		throw new UnprocessableEntityHttpException("Error ($result) in request transaction");
		// echo "<div class=\"error\">خطای شماره: {$result}</div>";
	}

	public function verify(&$gatewayModel, $onlinePaymentModel, $pgwResponse)
	{
		$username = $this->extensionModel->gtwPluginParameters[self::PARAM_USERNAME];
		$password = $this->extensionModel->gtwPluginParameters[self::PARAM_PASSWORD];
		$merchant_id = $this->extensionModel->gtwPluginParameters[self::PARAM_MERCHANT_ID];

		// $ReturningParams = $_POST['ReturningParams'];
		$ReturningParams = $pgwResponse['ReturningParams'];
		$ReturningParams = $this->decrypt($ReturningParams);
		$RetArr = explode(",", $ReturningParams);
		$Amount = $RetArr[0];
		$SaleOrderId = $RetArr[1];
		$RefId = $RetArr[2];
		$ResCode = $RetArr[3];
		$ResMessage = $RetArr[4];
		$PayGateTranID = $RetArr[5];
		$RRN = $RetArr[6];
		$LastFourDigitOfPAN = $RetArr[7];

		if ($ResCode != '0' && $ResCode != '00') {
			throw new UnprocessableEntityHttpException('payment failed ($ResCode)');
			// //echo 'تراکنش ناموفق<br>خطای شماره: '.$ResCode;
			// echo '<div class="error-bank">تراکنش شما ناموفق میباشد</div>';
			// exit();
		}

		try {
			$opts = [
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
				],
			];

			$params = [
				'stream_context' => stream_context_create($opts),
			];

			$client = @new soapclient(self::URL_WEBSERVICE, $params);

		} catch (SoapFault $exp) {
			throw new UnprocessableEntityHttpException('Error in verify payment (' . $exp->faultstring . ')');
			// echo $E->faultstring;
			//echo "خطا در فراخوانی وب‌سرويس.";
			// echo '<div class="error-bank">خطا در فراخوانی وب‌سرويس.</div>';
			// exit();
		}

		$encryptedCredintials = $this->encrypt("{$username},{$password}");
		$params = [
			'merchantConfigurationID' => $merchant_id,
			'encryptedCredentials' => $encryptedCredintials,
			'payGateTranID' => $PayGateTranID
		];

		//Verify
		$result = $client->RequestVerification($params);
		if (!$result)
			throw new UnprocessableEntityHttpException('Error in calling verify');
		// or die("خطای فراخوانی متد وريفای.");

		$result = $result->RequestVerificationResult;
		if ($result != '500') {
			throw new UnprocessableEntityHttpException("Error ($result) in verify");
			// echo ('خطای شماره: ' . $result . ' در هنگام Verify');
			// exit();
		}
		//echo('<div class="success-banl">تراکنش با موفقيت تایید شد.</div>');

		//Settlement
		$result = $client->RequestReconciliation($params);
		if (!$result)
			throw new UnprocessableEntityHttpException('Error in calling settlement');
			// or die("خطای فراخوانی متد تسويه.");

		$result = $result->RequestReconciliationResult;
		if ($result != '600') {
			throw new UnprocessableEntityHttpException("Error ($result) in settlement");
			// echo ('خطای شماره: ' . $result . ' در هنگام Settlement');
			// exit();
		}
		//echo('<div style="width:250px; margin:100px auto; direction:rtl; font:bold 14px Tahoma">تراکنش با موفقيت Settlement شد.</div>');

		return [
			'ok',
			$RRN,
		];
	}

	//-----------------------------
	function encrypt($string)
	{
		$key = $this->extensionModel->gtwPluginParameters[self::PARAM_KEY];
		$key = base64_decode($key);
		$key = hash('sha256', $key);

		$iv = $this->extensionModel->gtwPluginParameters[self::PARAM_IV];
		$iv = base64_decode($iv);
		$iv = substr(hash('sha256', $iv), 0, 16);

		return base64_encode(openssl_encrypt(
			$string,
			'aes-256-cbc',
			$key,
			0,
			$iv
		));

		// MCRYPT_RIJNDAEL_256, $key, $this->addpadding($string), MCRYPT_MODE_CBC, $iv));
	}

	function decrypt($string)
	{
		$key = $this->extensionModel->gtwPluginParameters[self::PARAM_KEY];
		$key = base64_decode($key);
		$key = hash('sha256', $key);

		$iv = $this->extensionModel->gtwPluginParameters[self::PARAM_IV];
		$iv = base64_decode($iv);
		$iv = substr(hash('sha256', $iv), 0, 16);

		$string = base64_decode($string);

		return openssl_decrypt(
			$string,
			'aes-256-cbc',
			$key,
			0,
			$iv
		);

		// MCRYPT_RIJNDAEL_256, $key, $this->addpadding($string), MCRYPT_MODE_CBC, $iv));
	}

	/*
	function addpadding($string, $blocksize = 32)
	{
		$len = strlen($string);
		$pad = $blocksize - ($len % $blocksize);
		$string .= str_repeat(chr($pad), $pad);
		return $string;
	}

	function strippadding($string)
	{
		$slast = ord(substr($string, -1));
		$slastc = chr($slast);
		$pcheck = substr($string, -$slast);

		if (preg_match("/$slastc{".$slast."}/", $string)) {
			$string = substr($string, 0, strlen($string)-$slast);
			return $string;
		}

		return false;
	}

	function encrypt($string = "")
	{
		$KEY = $this->extensionModel->gtwPluginParameters[self::PARAM_KEY];
		$IV = $this->extensionModel->gtwPluginParameters[self::PARAM_IV];

		if (PHP_MAJOR_VERSION <= 5) {
			$key = base64_decode($KEY);
			$iv = base64_decode($IV);
			return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $this->addpadding($string), MCRYPT_MODE_CBC, $iv));
		}

		return $this->EncryptWS($string);
	}

	function decrypt($string = "")
	{
		$KEY = $this->extensionModel->gtwPluginParameters[self::PARAM_KEY];
		$IV = $this->extensionModel->gtwPluginParameters[self::PARAM_IV];

		if (PHP_MAJOR_VERSION <= 5) {
			$key = base64_decode($KEY);
			$iv = base64_decode($IV);
			$string = base64_decode($string);
			return $this->strippadding(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CBC, $iv));
		}

		return $this->DecryptWS($string);
	}

	function EncryptWS($string = "")
	{
		$KEY = $this->extensionModel->gtwPluginParameters[self::PARAM_KEY];
		$IV = $this->extensionModel->gtwPluginParameters[self::PARAM_IV];

		try {
			$opts = [
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
				],
			];
			$params = [
				'stream_context' => stream_context_create($opts),
			];
			$client = @new soapclient("https://services.asanpardakht.net/paygate/internalutils.asmx?WSDL", $params);
		} catch (SoapFault $exp) {
			// echo "<div class=\"error\">{$E->faultstring}</div>";
			// echo "<div class=\"error\">خطا در فراخوانی وب‌سرويس رمزنگاری.</div>";
			// exit();
			throw new UnprocessableEntityHttpException('Error in calling crypto service (' . $exp->faultstring . ')');
		}
		$params = [
			'aesKey' => $KEY,
			'aesVector' => $IV,
			'toBeEncrypted' => $string,
		];
		$result = $client->EncryptInAES($params);
		if (!$result)
			throw new UnprocessableEntityHttpException('Error in calling crypto service method');
			// or die("<div class=\"error\">خطای فراخوانی متد رمزنگاری.</div>");

		return $result->EncryptInAESResult;
	}

	function DecryptWS($string = "")
	{
		$KEY = $this->extensionModel->gtwPluginParameters[self::PARAM_KEY];
		$IV = $this->extensionModel->gtwPluginParameters[self::PARAM_IV];

		try {
			$opts = [
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
				],
			];
			$params = [
				'stream_context' => stream_context_create($opts),
			];
			$client = @new soapclient("https://services.asanpardakht.net/paygate/internalutils.asmx?WSDL", $params);
		} catch (SoapFault $exp) {
			// echo "<div class=\"error\">{$E->faultstring}</div>";
			// echo "<div class=\"error\">خطا در فراخوانی وب‌سرويس رمزنگاری.</div>";
			// exit();
			throw new UnprocessableEntityHttpException('Error in calling crypto service (' . $exp->faultstring . ')');
		}

		$params = [
			'aesKey' => $KEY,
			'aesVector' => $IV,
			'toBeDecrypted' => $string,
		];
		$result = $client->DecryptInAES($params);
		if (!$result)
			throw new UnprocessableEntityHttpException('Error in calling crypto service method');
			// or die("<div class=\"error\">خطای فراخوانی متد رمزنگاری.</div>");

		return $result->DecryptInAESResult;
	}
	*/








	/*
	public function paymentinvoice()
	{
		if (isset($id)) {
			$result_invoice = Query("SELECT * From tbl_invoice Where tbl_invoice_id='$id'");
			$read_invoice = mysqli_fetch_array($result_invoice);
			$code = $read_invoice['tbl_invoice_usercode'];
			$price = $read_invoice['tbl_invoice_price'];
			$_SESSION['invoiceid'] = $id;
			$orderId = rand();
			$localDate = date("Ymd His");
			$additionalData = "";
			$callBackUrl = $site_url . 'profile/successinvoice/';

			$req = "1,{$username},{$password},{$orderId},{$price},{$localDate},{$additionalData},{$callBackUrl},0";
			$encryptedRequest = encrypt($req);

			try {
					$opts = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false));
					$params = array('stream_context' => stream_context_create($opts));
					$client = @new soapclient($WebServiceUrl, $params);
			} catch (SoapFault $E) {
					// echo "<div class=\"error\">{$E->faultstring}</div>";
					echo "<div class=\"error\">خطا در فراخوانی وب‌سرويس.</div>";
					exit();
			}
			$params = array(
					'merchantConfigurationID' => $merchantConfigurationID,
					'encryptedRequest' => $encryptedRequest
			);
			$result = $client->RequestOperation($params)
					or die("<div class=\"error\">خطای فراخوانی متد درخواست تراکنش.</div>");

			$result = $result->RequestOperationResult;
			if ($result{
					0} == '0') {
					echo "<script language='javascript' type='text/javascript'>RedirctToIPG('" . substr($result, 2) . "');</script>";
			} else {
					echo "<div class=\"error\">خطای شماره: {$result}</div>";
			}
	}
}

public function successinvoice()
{
	$ReturningParams = $_POST['ReturningParams'];
	$ReturningParams = decrypt($ReturningParams);
	$RetArr = explode(",", $ReturningParams);
	$Amount = $RetArr[0];
	$SaleOrderId = $RetArr[1];
	$RefId = $RetArr[2];
	$ResCode = $RetArr[3];
	$ResMessage = $RetArr[4];
	$PayGateTranID = $RetArr[5];
	$RRN = $RetArr[6];
	$LastFourDigitOfPAN = $RetArr[7];
	if ($ResCode != '0' && $ResCode != '00') {
			//echo 'تراکنش ناموفق<br>خطای شماره: '.$ResCode;
			echo '<div class="error-bank">تراکنش شما ناموفق میباشد</div>';
			exit();
	}
	try {
			$opts = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false));
			$params = array('stream_context' => stream_context_create($opts));
			$client = @new soapclient($WebServiceUrl, $params);
	} catch (SoapFault $E) {
			// echo $E->faultstring;
			//echo "خطا در فراخوانی وب‌سرويس.";
			echo '<div class="error-bank">خطا در فراخوانی وب‌سرويس.</div>';
			exit();
	}

	$encryptedCredintials = encrypt("{$username},{$password}");
	$params = array(
			'merchantConfigurationID' => $merchantConfigurationID,
			'encryptedCredentials' => $encryptedCredintials,
			'payGateTranID' => $PayGateTranID
	);

	//Verify
	$result = $client->RequestVerification($params)
			or die("خطای فراخوانی متد وريفای.");
	$result = $result->RequestVerificationResult;
	if ($result != '500') {
			echo ('خطای شماره: ' . $result . ' در هنگام Verify');
			exit();
	} else {
			//echo('<div class="success-banl">تراکنش با موفقيت تایید شد.</div>');
	}

	//Settlment
	$result = $client->RequestReconciliation($params)
			or die("خطای فراخوانی متد تسويه.");
	$result = $result->RequestReconciliationResult;
	if ($result != '600') {
			echo ('خطای شماره: ' . $result . ' در هنگام Settlement');
			exit();
	} else {
			//echo('<div style="width:250px; margin:100px auto; direction:rtl; font:bold 14px Tahoma">تراکنش با موفقيت Settlement شد.</div>');
	}
	$date = jdate("Y/m/d : A:h:s");
	$date_day = jdate("Y/m/d");
	$invoice_id = $_SESSION['invoiceid'];
	Query("update tbl_invoice set tbl_invoice_datepay='$date',tbl_invoice_resnum='$RRN',tbl_invoice_dateday='$date_day' where tbl_invoice_id='$invoice_id' ");
	//echo('<div class="success-bank">پرداخت با موفقيت انجام پذيرفت.</div>');
	echo ('<div class="success-bank">
پرداخت با موفقيت انجام پذيرفت.
<br>
کدپیگیری : ' . $RRN . '</div>');

	unset($_SESSION['invoiceid']);
}

public function gotobank()
{
	if (isset($_POST['payment'])) {
			$code = $read_profile['tbl_profile_code'];
			$price = $membership;
			$orderId = rand();
			$localDate = date("Ymd His");
			$additionalData = "";
			$callBackUrl = $site_url . 'profile/success/';

			$req = "1,{$username},{$password},{$orderId},{$price},{$localDate},{$additionalData},{$callBackUrl},0";
			$encryptedRequest = encrypt($req);

			try {
					$opts = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false));
					$params = array('stream_context' => stream_context_create($opts));
					$client = @new soapclient($WebServiceUrl, $params);
			} catch (SoapFault $E) {
					// echo "<div class=\"error\">{$E->faultstring}</div>";
					echo "<div class=\"error\">خطا در فراخوانی وب‌سرويس.</div>";
					exit();
			}
			$params = array(
					'merchantConfigurationID' => $merchantConfigurationID,
					'encryptedRequest' => $encryptedRequest
			);
			$result = $client->RequestOperation($params)
					or die("<div class=\"error\">خطای فراخوانی متد درخواست تراکنش.</div>");

			$result = $result->RequestOperationResult;
			if ($result{
					0} == '0') {
					echo "<script language='javascript' type='text/javascript'>RedirctToIPG('" . substr($result, 2) . "');</script>";
			} else {
					echo "<div class=\"error\">خطای شماره: {$result}</div>";
			}
	}
}

public function success()
{
	$ReturningParams = $_POST['ReturningParams'];
	$ReturningParams = decrypt($ReturningParams);
	$RetArr = explode(",", $ReturningParams);
	$Amount = $RetArr[0];
	$SaleOrderId = $RetArr[1];
	$RefId = $RetArr[2];
	$ResCode = $RetArr[3];
	$ResMessage = $RetArr[4];
	$PayGateTranID = $RetArr[5];
	$RRN = $RetArr[6];
	$LastFourDigitOfPAN = $RetArr[7];
	if ($ResCode != '0' && $ResCode != '00') {
			//echo 'تراکنش ناموفق<br>خطای شماره: '.$ResCode;
			echo '<div class="error-bank">تراکنش شما ناموفق میباشد</div>';
			exit();
	}
	try {
			$opts = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false));
			$params = array('stream_context' => stream_context_create($opts));
			$client = @new soapclient($WebServiceUrl, $params);
	} catch (SoapFault $E) {
			// echo $E->faultstring;
			//echo "خطا در فراخوانی وب‌سرويس.";
			echo '<div class="error-bank">خطا در فراخوانی وب‌سرويس.</div>';
			exit();
	}

	$encryptedCredintials = encrypt("{$username},{$password}");
	$params = array(
			'merchantConfigurationID' => $merchantConfigurationID,
			'encryptedCredentials' => $encryptedCredintials,
			'payGateTranID' => $PayGateTranID
	);

	//Verify
	$result = $client->RequestVerification($params)
			or die("خطای فراخوانی متد وريفای.");
	$result = $result->RequestVerificationResult;
	if ($result != '500') {
			echo ('خطای شماره: ' . $result . ' در هنگام Verify');
			exit();
	} else {
			//echo('<div class="success-banl">تراکنش با موفقيت تایید شد.</div>');
	}

	//Settlment
	$result = $client->RequestReconciliation($params)
			or die("خطای فراخوانی متد تسويه.");
	$result = $result->RequestReconciliationResult;
	if ($result != '600') {
			echo ('خطای شماره: ' . $result . ' در هنگام Settlement');
			exit();
	} else {
			//echo('<div style="width:250px; margin:100px auto; direction:rtl; font:bold 14px Tahoma">تراکنش با موفقيت Settlement شد.</div>');
	}
	$date = jdate("Y/m/d : A:h:s");
	$date_day = jdate("Y/m/d");
	Query("insert into tbl_onlinebank (tbl_onlinebank_date,tbl_onlinebank_rrn,tbl_onlinebank_user,tbl_onlinebank_price,tbl_onlinebank_status,tbl_onlinebank_dateday) values('$date','$RRN','$systemcode','$Amount','0','$date_day')");
	echo ('<div class="success-bank">
پرداخت با موفقيت انجام پذيرفت.
<br>
کدپیگیری : ' . $RRN . '</div>');
}
*/

}
