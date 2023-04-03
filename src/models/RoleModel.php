<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;

class RoleModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\RoleModelTrait;

	public static function tableName()
	{
		return '{{%AAA_Role}}';
	}

  // public function rules()
  // {
  //   return [
  //     ['rolID', 'integer'],
	// 		['rolName', 'string', 'max' => 64],
	// 		['rolParentID', 'integer'],
	// 		['rolPrivs', JsonValidator::class],

  //     ['rolCreatedAt', 'safe'],
  //     ['rolCreatedBy', 'integer'],
  //     ['rolUpdatedAt', 'safe'],
  //     ['rolUpdatedBy', 'integer'],
  //   ];
  // }

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'rolCreatedAt',
				'createdByAttribute' => 'rolCreatedBy',
				'updatedAtAttribute' => 'rolUpdatedAt',
				'updatedByAttribute' => 'rolUpdatedBy',
			],
		];
	}

}
