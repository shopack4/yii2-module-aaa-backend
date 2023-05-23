<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\components;

use Yii;
use yii\db\Expression;
use yii\base\Component;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnprocessableEntityHttpException;
use Ramsey\Uuid\Uuid;
use shopack\base\common\helpers\Url;
use shopack\aaa\backend\models\GatewayModel;
use shopack\aaa\backend\models\WalletModel;
use shopack\aaa\backend\models\VoucherModel;
use shopack\aaa\common\enums\enuGatewayStatus;
use shopack\aaa\backend\classes\BasePaymentGateway;
use shopack\aaa\backend\models\OnlinePaymentModel;
use shopack\aaa\backend\models\WalletTransactionModel;
use shopack\aaa\common\enums\enuOnlinePaymentStatus;
use shopack\aaa\common\enums\enuVoucherType;
use shopack\aaa\common\enums\enuVoucherStatus;

class PaymentManager extends Component
{
	/**
	 * return [onpkey, paymentUrl]
	 */
	public function createOnlinePayment(
		$voucherModel,
		$gatewayType,
		$callbackUrl,
		$walletID = null
	) {
		if (empty($walletID)) {
			$walletModel = WalletModel::ensureIHaveDefaultWallet();
			$walletID = $walletModel->walID;
		}

		$payAmount = $voucherModel->vchAmount - ($voucherModel->vchTotalPaid ?? 0);

		//1: find gateway
		$gatewayModel = $this->findBestPaymentGateway($gatewayType, $payAmount);
		if ($gatewayModel == null)
			throw new NotFoundHttpException('Payment gateway not found');

		//2: create online payment
		$onlinePaymentModel = new OnlinePaymentModel;
		// $onlinePaymentModel->onpID
		$onlinePaymentModel->onpUUID					= Uuid::uuid4()->toString();
		$onlinePaymentModel->onpGatewayID			= $gatewayModel->gtwID;
		$onlinePaymentModel->onpVoucherID			= $voucherModel->vchID;
		$onlinePaymentModel->onpAmount				= $payAmount;
		$onlinePaymentModel->onpCallbackUrl		= $callbackUrl;
		$onlinePaymentModel->onpWalletID			= $walletID;
		if ($onlinePaymentModel->save() == false)
			throw new ServerErrorHttpException('It is not possible to create an online payment');

		//3: prepare gateway
		$backendCallback = Url::to([
			'/aaa/online-payment/callback',
			'paymentkey' => $onlinePaymentModel->onpUUID,
		], true);

		$gatewayClass = $gatewayModel->getGatewayClass();
		list ($response, $trackID, $paymentUrl) = $gatewayClass->prepare(
			$gatewayModel,
			$onlinePaymentModel,
			$backendCallback
		);

		//4: save to onp
		$onlinePaymentModel->onpTrackNumber	= $trackID;
		$onlinePaymentModel->onpResult			= (array)$response;
		$onlinePaymentModel->onpStatus			= enuOnlinePaymentStatus::Pending;
		if ($onlinePaymentModel->save() == false)
			throw new ServerErrorHttpException('It is not possible to update online payment');

		//5: update gateway usage
		$fnGetConst = function($value) { return $value; };
		$gatewayTableName = GatewayModel::tableName();

		$qry =<<<SQL
			UPDATE	{$gatewayTableName}
				 SET	gtwUsages = JSON_MERGE_PATCH(
								COALESCE(JSON_REMOVE(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_LAST_TRANSACTION_DATE)}', '$.{$fnGetConst(BasePaymentGateway::USAGE_TODAY_USED_AMOUNT)}'), '{}'),
								JSON_OBJECT(
									'{$fnGetConst(BasePaymentGateway::USAGE_LAST_TRANSACTION_DATE)}', CURDATE(),
									'{$fnGetConst(BasePaymentGateway::USAGE_TODAY_USED_AMOUNT)}', IF(
										JSON_EXTRACT(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_LAST_TRANSACTION_DATE)}') IS NOT NULL
											AND JSON_UNQUOTE(JSON_EXTRACT(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_LAST_TRANSACTION_DATE)}')) = CURDATE()
											AND JSON_CONTAINS_PATH(gtwUsages, 'one', '$.{$fnGetConst(BasePaymentGateway::USAGE_TODAY_USED_AMOUNT)}')
										, CAST(JSON_EXTRACT(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_TODAY_USED_AMOUNT)}') AS UNSIGNED) + {$payAmount}
										,{$payAmount}
									)
								)
							)
			 WHERE	gtwID = {$gatewayModel->gtwID}
SQL;
		Yii::$app->db->createCommand($qry)->execute();

		//
		return [$onlinePaymentModel->onpUUID, $paymentUrl];
	}

	public function findBestPaymentGateway(
		$gatewayType,
		$amount
	) {
		$gatewayNames = [];
		$extensions = Yii::$app->controller->module->GatewayList('payment');
		foreach ($extensions as $pluginName => $extension) {
			$gtwclass = Yii::$app->controller->module->GatewayClass($pluginName);
			if ($gtwclass->getPaymentGatewayType() == $gatewayType) {
				$gatewayNames[] = $pluginName;
			}
		}

		if (empty($gatewayNames))
			return null;

		$fnGetConst = function($value) { return $value; };

		$gatewayTableName = GatewayModel::tableName();

		$gatewayModel = GatewayModel::find()
			->select([
				"{$gatewayTableName}.*",
				'tmptbl_inner.inner_pgwSumTodayPaidAmount',
				'tmptbl_inner.inner_pgwTransactionFeeAmount',
			])
			->innerJoin([
				'tmptbl_inner' => GatewayModel::find()
					->select([
						'gtwID',

						"IF(JSON_EXTRACT(gtwPluginParameters, '$.{$fnGetConst(BasePaymentGateway::PARAM_GATEWAY_COMMISSION_TYPE)}') = '%'

							, JSON_UNQUOTE(JSON_EXTRACT(gtwPluginParameters, '$.{$fnGetConst(BasePaymentGateway::PARAM_GATEWAY_COMMISSION)}')) * {$amount} / 100

							, JSON_UNQUOTE(JSON_EXTRACT(gtwPluginParameters, '$.{$fnGetConst(BasePaymentGateway::PARAM_GATEWAY_COMMISSION)}'))
						) AS `inner_pgwTransactionFeeAmount`",

						"IF(JSON_EXTRACT(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_LAST_TRANSACTION_DATE)}') IS NULL
							OR JSON_UNQUOTE(JSON_EXTRACT(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_LAST_TRANSACTION_DATE)}')) < CURDATE()

							, 0

							, JSON_UNQUOTE(JSON_EXTRACT(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_TODAY_USED_AMOUNT)}'))
						) AS `inner_pgwSumTodayPaidAmount`",

						// "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(tblPaymentGatewayTypesI18N.i18nData, '$.pgtName.'fa')), tblPaymentGatewayTypes.pgtName) AS `pgtName`",
					])
					// LEFT JOIN tblPaymentGatewayTypes
					// 		 ON tblPaymentGatewayTypes.pgtType = {$gatewayTableName}.pgwType
					// LEFT JOIN tblPaymentGatewayTypesI18N
					// 		 ON tblPaymentGatewayTypesI18N.i18nPID = tblPaymentGatewayTypes.pgtID
					->andWhere("gtwStatus != '{$fnGetConst(enuGatewayStatus::Removed)}'")
					->andWhere(['IN', 'gtwPluginName', $gatewayNames])
					->andWhere(['OR',
						"JSON_EXTRACT(gtwRestrictions, '$.{$fnGetConst(BasePaymentGateway::RESTRICTION_MIN_TRANSACTION_AMOUNT)}') IS NULL",

						"JSON_UNQUOTE(JSON_EXTRACT(gtwRestrictions, '$.{$fnGetConst(BasePaymentGateway::RESTRICTION_MIN_TRANSACTION_AMOUNT)}')) <= {$amount}"
					])
					->andWhere(['OR',
						"JSON_EXTRACT(gtwRestrictions, '$.{$fnGetConst(BasePaymentGateway::RESTRICTION_MAX_TRANSACTION_AMOUNT)}') IS NULL",

						"JSON_UNQUOTE(JSON_EXTRACT(gtwRestrictions, '$.{$fnGetConst(BasePaymentGateway::RESTRICTION_MAX_TRANSACTION_AMOUNT)}')) >= {$amount}"
					])
					->andWhere(['OR',
						"JSON_EXTRACT(gtwRestrictions, '$.{$fnGetConst(BasePaymentGateway::RESTRICTION_MAX_DAILY_TOTAL_AMOUNT)}') IS NULL",

						"JSON_EXTRACT(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_LAST_TRANSACTION_DATE)}') IS NULL",

						"JSON_UNQUOTE(JSON_EXTRACT(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_LAST_TRANSACTION_DATE)}')) < CURDATE()",

						"JSON_UNQUOTE(JSON_EXTRACT(gtwUsages, '$.{$fnGetConst(BasePaymentGateway::USAGE_TODAY_USED_AMOUNT)}')) <= JSON_UNQUOTE(JSON_EXTRACT(gtwRestrictions, '$.{$fnGetConst(BasePaymentGateway::RESTRICTION_MAX_DAILY_TOTAL_AMOUNT)}')) - {$amount}",
					])
				],
				"tmptbl_inner.gtwID = {$gatewayTableName}.gtwID"
			)
			->andWhere(['gtwStatus' => enuGatewayStatus::Active])
			// ->andWhere(['IN', "{$gatewayTableName}gtwPluginName", $gatewayNames])
			// ->andWhere("LOWER({$gatewayTableName}.pgwAllowedDomainName) = 'dev.test'")
			->orderBy([
				'tmptbl_inner.inner_pgwTransactionFeeAmount' => SORT_ASC,
				'tmptbl_inner.inner_pgwSumTodayPaidAmount' => SORT_ASC,
				'RAND()' => SORT_ASC,
			])
			->one();

		return $gatewayModel;
	}

	/**
	 * $pgwResponse: array|null response data back from payment gateway
	 */
	public function approveOnlinePayment($paymentkey, $pgwResponse) : OnlinePaymentModel
	{
		$onlinePaymentModel = OnlinePaymentModel::find()
			->with('gateway')
			->with('voucher')
			->andWhere(['onpUUID' => $paymentkey])
			->one();

		if ($onlinePaymentModel == null) {
			Yii::error('The requested online payment does not exist.', __METHOD__);
			throw new NotFoundHttpException('The requested online payment does not exist.');
		}

		if ($onlinePaymentModel->onpStatus != enuOnlinePaymentStatus::Pending)
			throw new UnprocessableEntityHttpException('This payment is not in pending state.');

		//1: verify and settle via gateway
		try {
			$this->verifyOnlinePayment($onlinePaymentModel, $pgwResponse);

		} catch (\Throwable $th) {
			if ($onlinePaymentModel->voucher->vchType != enuVoucherType::Basket) {
				$fnGetConst = function($value) { return $value; };
				$voucherTableName = VoucherModel::tableName();

				$qry =<<<SQL
					UPDATE	{$voucherTableName}
						 SET	vchStatus = '{$fnGetConst(enuVoucherStatus::Error)}'
					 WHERE	vchID = {$onlinePaymentModel->onpVoucherID}
SQL;
				$rowsCount = Yii::$app->db->createCommand($qry)->execute();
			}

			return $onlinePaymentModel;
		}

    //start transaction
    $transaction = Yii::$app->db->beginTransaction();

		$walletTableName = WalletModel::tableName();
		$voucherTableName = VoucherModel::tableName();

		try {
			//2.1: create wallet transaction
			$walletTransactionModel = new WalletTransactionModel();
			$walletTransactionModel->wtrWalletID				= $onlinePaymentModel->onpWalletID;
			$walletTransactionModel->wtrVoucherID				= $onlinePaymentModel->onpVoucherID;
			$walletTransactionModel->wtrOnlinePaymentID	= $onlinePaymentModel->onpID;
			$walletTransactionModel->wtrAmount					= $onlinePaymentModel->onpAmount;
			$walletTransactionModel->save();

			//2.2: add to the wallet amount
			$qry =<<<SQL
				UPDATE	{$walletTableName}
					 SET	walRemainedAmount = walRemainedAmount + {$onlinePaymentModel->onpAmount}
				 WHERE	walID = {$walletTransactionModel->wtrWalletID}
SQL;
			$rowsCount = Yii::$app->db->createCommand($qry)->execute();

			//save to the voucher
			if ($onlinePaymentModel->voucher->vchType == enuVoucherType::Basket) {
				//2.1: create decrease wallet transaction
				$walletTransactionModel = new WalletTransactionModel();
				$walletTransactionModel->wtrWalletID	= $onlinePaymentModel->onpWalletID;
				$walletTransactionModel->wtrVoucherID	= $onlinePaymentModel->onpVoucherID;
				$walletTransactionModel->wtrAmount		= (-1) * $onlinePaymentModel->onpAmount;
				$walletTransactionModel->save();

				//2.2: decrease wallet amount
				$qry =<<<SQL
				UPDATE	{$walletTableName}
					 SET	walRemainedAmount = walRemainedAmount - {$onlinePaymentModel->onpAmount}
				 WHERE	walID = {$walletTransactionModel->wtrWalletID}
SQL;
				$rowsCount = Yii::$app->db->createCommand($qry)->execute();

				$field = 'vchPaidByWallet';
			} else {
				$field = 'vchOnlinePaid';
			}

			$qry =<<<SQL
			UPDATE	{$voucherTableName}
					SET	{$field} = IFNULL({$field}, 0) + {$onlinePaymentModel->onpAmount}
						,	vchTotalPaid = IFNULL(vchTotalPaid, 0) + {$onlinePaymentModel->onpAmount}
				WHERE	vchID = {$onlinePaymentModel->onpVoucherID}
SQL;
			$rowsCount = Yii::$app->db->createCommand($qry)->execute();
			$onlinePaymentModel->voucher->refresh();

			if ($onlinePaymentModel->voucher->vchType == enuVoucherType::Basket
				&& $onlinePaymentModel->voucher->vchAmount == $onlinePaymentModel->voucher->vchTotalPaid ?? 0
			) {
				$onlinePaymentModel->voucher->vchStatus = enuVoucherStatus::Settled;
				$onlinePaymentModel->voucher->save();
			}

      //commit
      $transaction->commit();

			return $onlinePaymentModel;

    } catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
		} catch (\Throwable $e) {
      $transaction->rollBack();
      throw $e;
    }

	}

	//verify and settle online payment
	private function verifyOnlinePayment($onlinePaymentModel, $pgwResponse)
	{
		$gatewayClass = $onlinePaymentModel->gateway->getGatewayClass();

		try {
			list ($result, $rrn) = $gatewayClass->verify($onlinePaymentModel->gateway, $onlinePaymentModel, $pgwResponse);

			$onlinePaymentModel->onpRRN = $rrn;
			$onlinePaymentModel->onpResult = $result;
			$onlinePaymentModel->onpStatus = enuOnlinePaymentStatus::Paid;
			if ($onlinePaymentModel->save() == false) {
				//todo: ???
			}
		} catch (\Throwable $th) {
			$onlinePaymentModel->onpResult = [
				'error' => $th->getMessage(),
			];
			$onlinePaymentModel->onpStatus = enuOnlinePaymentStatus::Error;
			if ($onlinePaymentModel->save() == false) {
				//todo: ???
			}

			throw $th;
		}
	}

}
