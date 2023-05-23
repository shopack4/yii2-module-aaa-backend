<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\aaa\common\enums\enuUploadFileStatus;

class UploadFileModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\UploadFileModelTrait;

	use \shopack\base\common\db\SoftDeleteActiveRecordTrait;
  public function initSoftDelete()
  {
    $this->softdelete_RemovedStatus  = enuUploadFileStatus::Removed;
    // $this->softdelete_StatusField    = 'uflStatus';
    $this->softdelete_RemovedAtField = 'uflRemovedAt';
    $this->softdelete_RemovedByField = 'uflRemovedBy';
  }

	public static function tableName()
	{
		return '{{%AAA_UploadFile}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'uflCreatedAt',
				'createdByAttribute' => 'uflCreatedBy',
				'updatedAtAttribute' => 'uflUpdatedAt',
				'updatedByAttribute' => 'uflUpdatedBy',
			],
		];
	}

  public static function find($_addFileUrl = true)
  {
    $query = parent::find();

		if ($_addFileUrl) {
			$query
				->select(self::selectableColumns())
				->addFileUrl('fullFileUrl')
			;
		}

    return $query;
  }

}
