<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;

class GeoTownModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\GeoTownModelTrait;

	public static function tableName()
	{
		return '{{%AAA_GeoTown}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'twnCreatedAt',
				'createdByAttribute' => 'twnCreatedBy',
				'updatedAtAttribute' => 'twnUpdatedAt',
				'updatedByAttribute' => 'twnUpdatedBy',
			],
		];
	}

}
