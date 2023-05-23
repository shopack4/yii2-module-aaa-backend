<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\aaa\common\enums\enuUploadQueueStatus;

class UploadQueueModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\UploadQueueModelTrait;

	use \shopack\base\common\db\SoftDeleteActiveRecordTrait;
  public function initSoftDelete()
  {
    $this->softdelete_RemovedStatus  = enuUploadQueueStatus::Removed;
    // $this->softdelete_StatusField    = 'uquStatus';
    $this->softdelete_RemovedAtField = 'uquRemovedAt';
    $this->softdelete_RemovedByField = 'uquRemovedBy';
  }

	public static function tableName()
	{
		return '{{%AAA_UploadQueue}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'uquCreatedAt',
				'createdByAttribute' => 'uquCreatedBy',
				'updatedAtAttribute' => 'uquUpdatedAt',
				'updatedByAttribute' => 'uquUpdatedBy',
			],
		];
	}

}
