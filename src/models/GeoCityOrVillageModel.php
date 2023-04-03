<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;

class GeoCityOrVillageModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\GeoCityOrVillageModelTrait;

	public static function tableName()
	{
		return '{{%AAA_GeoCityOrVillage}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'ctvCreatedAt',
				'createdByAttribute' => 'ctvCreatedBy',
				'updatedAtAttribute' => 'ctvUpdatedAt',
				'updatedByAttribute' => 'ctvUpdatedBy',
			],
		];
	}

}
