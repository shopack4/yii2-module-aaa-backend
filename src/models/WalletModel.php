<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\db\Expression;
use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\aaa\common\enums\enuWalletStatus;

class WalletModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\WalletModelTrait;

  use \shopack\base\common\db\SoftDeleteActiveRecordTrait;
  public function initSoftDelete()
  {
    $this->softdelete_RemovedStatus  = enuWalletStatus::Removed;
    // $this->softdelete_StatusField    = 'walStatus';
    $this->softdelete_RemovedAtField = 'walRemovedAt';
    $this->softdelete_RemovedByField = 'walRemovedBy';
	}

	public static function tableName()
	{
		return '{{%AAA_Wallet}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'walCreatedAt',
				'createdByAttribute' => 'walCreatedBy',
				'updatedAtAttribute' => 'walUpdatedAt',
				'updatedByAttribute' => 'walUpdatedBy',
			],
		];
	}

	public static function ensureIHaveDefaultWallet()
	{
		if (Yii::$app->user->isGuest || empty($_GET['justForMe']))
			return false;

		//todo: replace logic with `INSERT IGNORE`

		$model = WalletModel::find()
			->andWhere(['walOwnerUserID' => Yii::$app->user->id])
			->andWhere(['walIsDefault' => true])
			->andWhere(['!=', 'walStatus', enuWalletStatus::Removed])
			->one();

		if ($model == null) {
			$model = new WalletModel();

			$model->walOwnerUserID		= Yii::$app->user->id;
			$model->walName						= 'Default';
			$model->walIsDefault			= true;
			$model->walRemainedAmount	= 0;
			$model->walStatus					= enuWalletStatus::Active;

			$model->save();
		}

		return $model;
	}

}
