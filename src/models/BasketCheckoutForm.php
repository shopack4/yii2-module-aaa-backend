<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\base\Model;
use yii\web\NotFoundHttpException;
use yii\web\UnauthorizedHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\UnprocessableEntityHttpException;
use shopack\aaa\common\enums\enuVoucherStatus;
use shopack\aaa\common\enums\enuWalletStatus;
use shopack\aaa\backend\models\WalletTransactionModel;

class BasketCheckoutForm extends Model
{
	public $walletID;
	public $gatewayType;
  public $callbackUrl;

	public function rules()
	{
		return [
			[[
				'walletID',
				'gatewayType',
        'callbackUrl',
			], 'string'],

			[[
				// 'gatewayType',
        'callbackUrl',
			], 'required'],
		];

	}

	public function checkout()
	{
		$voucherModel = BasketForm::getCurrentBasket();
		if ($voucherModel == null)
			throw new NotFoundHttpException('Basket not found');

		if (empty($voucherModel->vchItems))
			throw new UnprocessableEntityHttpException('Basket is empty');

		if ($voucherModel->vchOwnerUserID != Yii::$app->user->id)
			throw new ForbiddenHttpException('Basket is not yours');

		// $totalAmount = 0;
		// $vchItems = $voucherModel->vchItems;

		// foreach ($vchItems as $item) {
		// 	//todo: use ['totalprice'] that computed by discount and tax in own micro service
		// 	$totalAmount += $item['unitprice'] * $item['qty'];
		// }

		//--
    if ($this->validate() == false)
      throw new UnprocessableEntityHttpException(implode("\n", $this->getFirstErrors()));

		$remainedAmount = $voucherModel->vchAmount - $voucherModel->vchTotalPaid;

		if (empty($this->walletID) && empty($this->gatewayType) && ($remainedAmount > 0))
			throw new UnprocessableEntityHttpException('One of the wallet or payment type must be selected');

		$walletAmount = 0;
		if ($remainedAmount > 0 && $this->walletID >= 0) {
			$walletModel = WalletModel::find()
				->andWhere(['walOwnerUserID' => Yii::$app->user->id])
				->andWhere(['!=', 'walStatus', enuWalletStatus::Removed]);

			if ($this->walletID == 0)
				$walletModel->andWhere(['walIsDefault' => true]);
			else
				$walletModel->andWhere(['walID' => $this->walletID]);

			$walletModel = $walletModel->one();
			if ($walletModel == null)
				throw new NotFoundHttpException('Wallet not found');

			$this->walletID = $walletModel->walID;

			if ($walletModel->walRemainedAmount > $remainedAmount) {
				$walletAmount = $remainedAmount;
			} else {
				$walletAmount = $walletModel->walRemainedAmount;
			}
		}

		$remainedAmount -= $walletAmount;
		if (($remainedAmount > 0) && empty($this->gatewayType))
			throw new UnprocessableEntityHttpException('Payment type not provided');

    //start transaction
		if ($walletAmount > 0 || $remainedAmount > 0)
			$transaction = Yii::$app->db->beginTransaction();

		$walletTableName = WalletModel::tableName();
		$voucherTableName = VoucherModel::tableName();

		try {
			if ($walletAmount > 0) {
				//2.1: create wallet transaction
				$walletTransactionModel = new WalletTransactionModel();
				$walletTransactionModel->wtrWalletID		= $this->walletID;
				$walletTransactionModel->wtrVoucherID		= $voucherModel->vchID;
				$walletTransactionModel->wtrAmount			= (-1) * $walletAmount;
				$walletTransactionModel->save();

				//2.2: decrease wallet amount
				$qry =<<<SQL
				UPDATE	{$walletTableName}
					 SET	walRemainedAmount = walRemainedAmount - {$walletAmount}
				 WHERE	walID = {$walletTransactionModel->wtrWalletID}
SQL;
				$rowsCount = Yii::$app->db->createCommand($qry)->execute();

				//3: save to the voucher
				$qry =<<<SQL
				UPDATE	{$voucherTableName}
					 SET	vchPaidByWallet = IFNULL(vchPaidByWallet, 0) + {$walletAmount}
					 	 ,	vchTotalPaid = IFNULL(vchTotalPaid, 0) + {$walletAmount}
				 WHERE	vchID = {$voucherModel->vchID}
SQL;
				$rowsCount = Yii::$app->db->createCommand($qry)->execute();

				$voucherModel->refresh();
			}

			// if ($remainedAmount > 0) {
			// 	//create online payment
			// 	$onpResult = Yii::$app->paymentManager->createOnlinePayment(
			// 		$voucherModel,
			// 		$this->gatewayType,
			// 		$this->callbackUrl,
			// 		null, //$this->walletID
			// 	);

			// 	//
			// 	if ($onpResult instanceof \Throwable) {
			// 		throw $onpResult;
			// 	}

			// 	//commit
			// 	if (isset($transaction))
			// 		$transaction->commit();

			// 	list ($onpUUID, $paymentUrl) = $onpResult;
			// 	return [
			// 		'onpkey' => $onpUUID,
			// 		'paymentUrl' => $paymentUrl,
			// 	];
			// }

			//------------------------
			if ($remainedAmount == 0) {
				//------------------------
				$voucherModel->vchStatus = enuVoucherStatus::Settled;
				$voucherModel->save();

				//commit
				if (isset($transaction))
					$transaction->commit();

				//
				return $voucherModel->processVoucher();
			}
			// else : create online payment out of transaction

			//commit
			if (isset($transaction))
				$transaction->commit();

    } catch (\Exception $e) {
			if (isset($transaction))
	      $transaction->rollBack();
      throw $e;
		} catch (\Throwable $e) {
			if (isset($transaction))
				$transaction->rollBack();
      throw $e;
    }

		if ($remainedAmount > 0) {
			//create online payment
			$onpResult = Yii::$app->paymentManager->createOnlinePayment(
				$voucherModel,
				$this->gatewayType,
				$this->callbackUrl,
				null, //$this->walletID
			);

			//
			if ($onpResult instanceof \Throwable) {
				throw $onpResult;
			}

			list ($onpUUID, $paymentUrl) = $onpResult;
			return [
				'onpkey' => $onpUUID,
				'paymentUrl' => $paymentUrl,
			];
		}

  }

}
