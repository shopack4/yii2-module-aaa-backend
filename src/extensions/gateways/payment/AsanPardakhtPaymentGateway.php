<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\payment;

use Yii;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\classes\IPaymentGateway;
use shopack\aaa\common\enums\enuPaymentGatewayType;

class AsanPardakhtPaymentGateway
	extends BasePaymentGateway
	implements IPaymentGateway
{
	const URL_WEBSERVICE = "https://services.asanpardakht.net/paygate/merchantservices.asmx?WSDL";

	const PARAM_KEY = 'key';
	const PARAM_IV = 'iv';
	const PARAM_USERNAME = 'userName';
	const PARAM_PASSWORD = 'password';
	const PARAM_MERCHANT_ID = 'merchantID';

	public function getTitle()
	{
		return 'Asan Pardakht';
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
	}

	public function run($controller, &$gatewayModel, $callbackUrl)
	{
	}

	public function verify(&$gatewayModel, $onlinePaymentModel)
	{
	}

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
