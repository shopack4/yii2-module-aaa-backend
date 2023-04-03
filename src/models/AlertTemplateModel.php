<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;

class AlertTemplateModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\AlertTemplateModelTrait;

	public static function tableName()
	{
		return '{{%AAA_AlertTemplate}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'altCreatedAt',
				'createdByAttribute' => 'altCreatedBy',
				'updatedAtAttribute' => 'altUpdatedAt',
				'updatedByAttribute' => 'altUpdatedBy',
			],
		];
	}

}
