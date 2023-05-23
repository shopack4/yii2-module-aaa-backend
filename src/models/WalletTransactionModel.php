<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\db\Expression;
use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\aaa\common\enums\enuWalletTransactionStatus;

class WalletTransactionModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\WalletTransactionModelTrait;

  use \shopack\base\common\db\SoftDeleteActiveRecordTrait;
  public function initSoftDelete()
  {
    $this->softdelete_RemovedStatus  = enuWalletTransactionStatus::Removed;
    // $this->softdelete_StatusField    = 'wtrStatus';
    $this->softdelete_RemovedAtField = 'wtrRemovedAt';
    $this->softdelete_RemovedByField = 'wtrRemovedBy';
	}

	public static function tableName()
	{
		return '{{%AAA_WalletTransaction}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'wtrCreatedAt',
				'createdByAttribute' => 'wtrCreatedBy',
				'updatedAtAttribute' => 'wtrUpdatedAt',
				'updatedByAttribute' => 'wtrUpdatedBy',
			],
		];
	}

}
