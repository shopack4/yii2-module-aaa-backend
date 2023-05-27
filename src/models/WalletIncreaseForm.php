<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\base\Model;
use yii\web\UnauthorizedHttpException;
use yii\web\UnprocessableEntityHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use shopack\base\common\helpers\Url;
use shopack\base\common\helpers\HttpHelper;
use shopack\aaa\backend\models\VoucherModel;
use shopack\aaa\common\enums\enuVoucherType;
use shopack\aaa\common\enums\enuVoucherStatus;

class WalletIncreaseForm extends Model
{
	public $walletID;
  public $amount;
  public $gatewayType;
  public $callbackUrl;

  public function rules()
  {
    return [
      ['amount', 'integer'],

			[[
        'walletID',
        'amount',
        'gatewayType',
        'callbackUrl',
      ], 'required'],
    ];
  }

  public function process()
  {
    if (Yii::$app->user->isGuest)
      throw new UnauthorizedHttpException("This process is not for guest.");

    if ($this->validate() == false)
      throw new UnauthorizedHttpException(implode("\n", $this->getFirstErrors()));

    //start transaction
    $transaction = Yii::$app->db->beginTransaction();

    try {
      //1- create voucher
      $voucherModel = new VoucherModel;
      $voucherModel->vchOwnerUserID = Yii::$app->user->id;
      $voucherModel->vchType        = enuVoucherType::Credit;
      $voucherModel->vchAmount      = $this->amount;
      $voucherModel->vchItems       = [
        'inc-wallet-id' => $this->walletID,
      ];
      if ($voucherModel->save() == false)
        throw new ServerErrorHttpException('It is not possible to create a voucher');

      //2- create online payment
      $onpResult = Yii::$app->paymentManager->createOnlinePayment(
        $voucherModel,
        $this->gatewayType,
        $this->callbackUrl,
        $this->walletID
      );

      //commit
      $transaction->commit();

      //
      if ($onpResult instanceof \Throwable) {
        $voucherModel->vchStatus = enuVoucherStatus::Error;
        if ($voucherModel->save() == false)
          throw new ServerErrorHttpException('It is not possible to update voucher');

        throw $onpResult;
      }

      //3- return [onpkey, paymentUrl]
      list ($onpUUID, $paymentUrl) = $onpResult;
      return [
        'onpkey' => $onpUUID,
        'paymentUrl' => $paymentUrl,
      ];

    } catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    } catch (\Throwable $e) {
      $transaction->rollBack();
      throw $e;
    }

  }

}
