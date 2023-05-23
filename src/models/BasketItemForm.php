<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\base\Model;
use yii\web\UnauthorizedHttpException;
use yii\web\UnprocessableEntityHttpException;
use Ramsey\Uuid\Uuid;
use shopack\base\common\validators\GroupRequiredValidator;
use shopack\base\backend\helpers\AuthHelper;
use shopack\aaa\common\enums\enuUserStatus;
use shopack\base\common\security\RsaPublic;
use shopack\aaa\backend\models\VoucherModel;
use shopack\aaa\common\enums\enuVoucherType;
use shopack\aaa\common\enums\enuVoucherStatus;
use shopack\aaa\backend\models\BasketForm;
use yii\db\Expression;
use yii\web\NotFoundHttpException;

class BasketItemForm extends Model
{
  public static function addItem()
  {
		$service = $_POST['service'];

		$data = $_POST['data'];

		if (empty(Yii::$app->controller->module->servicesPublicKeys[$service]))
			$data = base64_decode($data);
		else
			$data = RsaPublic::model(Yii::$app->controller->module->servicesPublicKeys[$service])->decrypt($data);

		$data = json_decode($data, true);

		$userid    = $data['userid'];
		$service   = $data['service'];
		$slbkey    = $data['slbkey'];
		$slbid     = $data['slbid'];
		$desc      = $data['desc'];
		$qty       = $data['qty'];
		$maxqty    = $data['maxqty'] ?? null;
		$unitprice = $data['unitprice'];
    //additives
    //discount
    //tax
    //totalprice

    //voucher
    $voucherModel = BasketForm::getCurrentBasket($userid);

    if ($voucherModel == null) {
      $voucherModel = new VoucherModel();
      $voucherModel->vchOwnerUserID = $userid;
      $voucherModel->vchType        = enuVoucherType::Basket;
      $voucherModel->vchAmount      = 0;
    }

    $voucherModel->vchAmount += ($unitprice * $qty);

    $vchItems = $voucherModel->vchItems ?? [];

    //check current items
    if (empty($maxqty) == false) {
      $curqty = 0;
      foreach ($vchItems as $vchItem) {
        if ($vchItem['service'] == $service
          && $vchItem['slbkey'] == $slbkey
          && $vchItem['slbid'] == $slbid
        ) {
          $curqty += $vchItem['qty'];

          if ($curqty >= $maxqty)
            throw new UnprocessableEntityHttpException('Max qty of this item exists in basket');
        }
      }
    }

    $vchItems[] = array_merge($data, [
      'key'       => Uuid::uuid4()->toString(),
      // 'service'   => $service,
      // 'slbkey'    => $slbkey,
      // 'slbid'     => $slbid,
			// 'desc' 			=> $desc,
      // 'qty'       => $qty,
      // 'unitprice' => $unitprice,
    ]);
    $voucherModel->vchItems = $vchItems;

    return $voucherModel->save();
  }

  public static function removeItem($key)
  {
    $voucherModel = BasketForm::getCurrentBasket();

    if ($voucherModel == null)
      throw new NotFoundHttpException('Basket not found');

    $vchItems = $voucherModel->vchItems ?? [];

    //check current items
    foreach ($vchItems as $k => $vchItem) {
      if ($vchItem['key'] == $key) {

        //start transaction
		  	$transaction = Yii::$app->db->beginTransaction();

        try {
          unset($vchItems[$k]);

          $voucherModel->vchAmount -= ($vchItem['unitprice'] * $vchItem['qty']);
          $voucherModel->vchItems = $vchItems;

          if (($voucherModel->vchPaidByWallet ?? 0) > $voucherModel->vchAmount) {
            $walletReturnAmount = $voucherModel->vchPaidByWallet - $voucherModel->vchAmount;

            $walletModel = WalletModel::ensureIHaveDefaultWallet();

            //2.1: create wallet transaction
            $walletTransactionModel = new WalletTransactionModel();
            $walletTransactionModel->wtrWalletID		= $walletModel->walID;
            $walletTransactionModel->wtrVoucherID		= $voucherModel->vchID;
            $walletTransactionModel->wtrAmount			= $walletReturnAmount;
            $walletTransactionModel->save();

            //2.2: increase wallet amount
            $walletTableName = WalletModel::tableName();
            $qry =<<<SQL
				UPDATE	{$walletTableName}
					 SET	walRemainedAmount = walRemainedAmount + {$walletReturnAmount}
				 WHERE	walID = {$walletModel->walID}
SQL;
            $rowsCount = Yii::$app->db->createCommand($qry)->execute();

            //3: save to the voucher
            $voucherModel->vchPaidByWallet = $voucherModel->vchAmount;
            $voucherModel->vchTotalPaid = $voucherModel->vchTotalPaid - $walletReturnAmount;
          }

          if ($voucherModel->vchStatus == enuVoucherStatus::Settled)
            $voucherModel->vchStatus == enuVoucherStatus::New;

          if ($voucherModel->save() !== true)
            throw new UnprocessableEntityHttpException('Error in updating voucher');

          //commit
	        $transaction->commit();

          return true;

        } catch (\Exception $e) {
          if (isset($transaction))
            $transaction->rollBack();
          throw $e;
        } catch (\Throwable $e) {
          if (isset($transaction))
            $transaction->rollBack();
          throw $e;
        }
      }
    }

    throw new NotFoundHttpException('Basket item not found');
  }

}
