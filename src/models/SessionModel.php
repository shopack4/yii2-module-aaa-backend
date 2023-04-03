<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;

class SessionModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\SessionModelTrait;

	public static function tableName()
	{
		return '{{%AAA_Session}}';
	}

  // public function rules()
  // {
  //   return [
  //     ['ssnID', 'integer'],
  //     ['ssnUserID', 'integer'],
  //     ['ssnJWT', 'string', 'max' => 2048],

  //     ['ssnStatus', 'string', 'max' => 1],
  //     ['ssnStatus', 'default', 'value' => static::STATUS_PENDING],

  //     ['ssnExpireAt', 'safe'],

  //     ['ssnCreatedAt', 'safe'],
  //     // ['ssnCreatedBy', 'integer'],
  //     ['ssnUpdatedAt', 'safe'],
  //     ['ssnUpdatedBy', 'integer'],
  //   ];
  // }

  public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'ssnCreatedAt',
				// 'createdByAttribute' => 'ssnCreatedBy',
				'updatedAtAttribute' => 'ssnUpdatedAt',
				'updatedByAttribute' => 'ssnUpdatedBy',
			],
		];
	}

}
