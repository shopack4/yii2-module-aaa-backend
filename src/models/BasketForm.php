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
	public static function getCurrentBasket($userid = null)
	{
    return VoucherModel::find()
      ->andWhere(['vchOwnerUserID' => $userid ?? Yii::$app->user->id])
      ->andWhere(['vchType' => enuVoucherType::Basket])
      ->andWhere(['vchStatus' => enuVoucherStatus::New])
      ->andWhere(['vchRemovedAt' => 0])
      ->one();
	}

}
