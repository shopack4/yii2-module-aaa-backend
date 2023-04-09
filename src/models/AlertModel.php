<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\db\Expression;
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

	public function insert($runValidation = true, $attributes = null)
  {
		$instanceID = Yii::$app->getInstanceID();

		$this->alrLockedAt = new Expression("NOW()");
		$this->alrLockedBy = $instanceID;

		$ret = parent::insert($runValidation, $attributes);

		if ($ret) {
			try {
				Yii::$app->alertManager->processQueue(1, $this->alrID);
			} catch (\Throwable $th) {
				//throw $th;
			}
		}

		return $ret;
  }

}
