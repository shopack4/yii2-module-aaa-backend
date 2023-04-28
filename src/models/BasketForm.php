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

class BasketForm extends Model
{
  public function addToBasket()
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
		$unitprice = $data['unitprice'];
		$qty       = $data['qty'];
    //additives
    //discount
    //tax

    //voucher
    $voucherModel = VoucherModel::find()
      ->andWhere(['vchOwnerUserID' => $userid])
      ->andWhere(['vchType' => enuVoucherType::Basket])
      ->andWhere(['vchStatus' => enuVoucherStatus::New])
      ->andWhere(['vchRemovedAt' => 0])
      ->one();

    if ($voucherModel == null) {
      $voucherModel = new VoucherModel();
      $voucherModel->vchOwnerUserID = $userid;
      $voucherModel->vchType        = enuVoucherType::Basket;
      $voucherModel->vchAmount      = 0;
    }

    $voucherModel->vchAmount += ($unitprice * $qty);

    $vchItems = $voucherModel->vchItems ?? [];
    $vchItems[] = [
      'key'       => Uuid::uuid4()->toString(),
      'service'   => $service,
      'slbkey'    => $slbkey,
      'slbid'     => $slbid,
			'desc' 			=> $desc,
      'unitprice' => $unitprice,
      'qty'       => $qty,
    ];
    $voucherModel->vchItems = $vchItems;

    return $voucherModel->save();
  }

}
