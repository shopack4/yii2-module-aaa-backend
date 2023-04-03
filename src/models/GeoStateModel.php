<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;

class GeoStateModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\GeoStateModelTrait;

	public static function tableName()
	{
		return '{{%AAA_GeoState}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'sttCreatedAt',
				'createdByAttribute' => 'sttCreatedBy',
				'updatedAtAttribute' => 'sttUpdatedAt',
				'updatedByAttribute' => 'sttUpdatedBy',
			],
		];
	}

}
