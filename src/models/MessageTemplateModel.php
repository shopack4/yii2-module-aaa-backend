<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;

class MessageTemplateModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\MessageTemplateModelTrait;

	public static function tableName()
	{
		return '{{%AAA_MessageTemplate}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'mstCreatedAt',
				'createdByAttribute' => 'mstCreatedBy',
				'updatedAtAttribute' => 'mstUpdatedAt',
				'updatedByAttribute' => 'mstUpdatedBy',
			],
		];
	}

}
