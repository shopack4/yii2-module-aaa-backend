<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;

class AlertModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\AlertModelTrait;

	public static function tableName()
	{
		return '{{%AAA_Alert}}';
	}

  public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'alrCreatedAt',
				'createdByAttribute' => 'alrCreatedBy',
				// 'updatedAtAttribute' => 'alrUpdatedAt',
				// 'updatedByAttribute' => 'alrUpdatedBy',
			],
		];
	}

}
