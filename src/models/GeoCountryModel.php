<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use shopack\aaa\backend\classes\AAAActiveRecord;

class GeoCountryModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\GeoCountryModelTrait;

	public static function tableName()
	{
		return '{{%AAA_GeoCountry}}';
	}

  // public function rules()
  // {
  //   return [
  //     ['cntrID', 'integer'],
	// 		['cntrName', 'string', 'max' => 64],
	// 		['cntrParentID', 'integer'],
	// 		['cntrPrivs', JsonValidator::class],

  //     ['cntrCreatedAt', 'safe'],
  //     ['cntrCreatedBy', 'integer'],
  //     ['cntrUpdatedAt', 'safe'],
  //     ['cntrUpdatedBy', 'integer'],
  //   ];
  // }

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'cntrCreatedAt',
				'createdByAttribute' => 'cntrCreatedBy',
				'updatedAtAttribute' => 'cntrUpdatedAt',
				'updatedByAttribute' => 'cntrUpdatedBy',
			],
		];
	}

}
