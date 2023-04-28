<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\db\Expression;
use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\aaa\common\enums\enuVoucherStatus;

class VoucherModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\VoucherModelTrait;

  use \shopack\base\common\db\SoftDeleteActiveRecordTrait;
  public function initSoftDelete()
  {
    $this->softdelete_RemovedStatus  = enuVoucherStatus::Removed;
    $this->softdelete_StatusField    = 'vchStatus';
    $this->softdelete_RemovedAtField = 'vchRemovedAt';
    $this->softdelete_RemovedByField = 'vchRemovedBy';
	}

	public static function tableName()
	{
		return '{{%AAA_Voucher}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'vchCreatedAt',
				'createdByAttribute' => 'vchCreatedBy',
				'updatedAtAttribute' => 'vchUpdatedAt',
				'updatedByAttribute' => 'vchUpdatedBy',
			],
		];
	}

}
