<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\db\Expression;
use shopack\aaa\backend\classes\AAAActiveRecord;

class MessageModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\MessageModelTrait;

	public $sendNow = true;

	public static function tableName()
	{
		return '{{%AAA_Message}}';
	}

  public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'msgCreatedAt',
				'createdByAttribute' => 'msgCreatedBy',
				// 'updatedAtAttribute' => 'msgUpdatedAt',
				// 'updatedByAttribute' => 'msgUpdatedBy',
			],
		];
	}

	public function insert($runValidation = true, $attributes = null)
  {
		$instanceID = Yii::$app->getInstanceID();

		if ($this->sendNow) {
			$this->msgLockedAt = new Expression("NOW()");
			$this->msgLockedBy = $instanceID;
		}

		$ret = parent::insert($runValidation, $attributes);

		if ($this->sendNow) {
			if ($ret) {
				try {
					Yii::$app->messageManager->processQueue(1, $this->msgID);
				} catch (\Throwable $th) {
					//throw $th;
				}
			}
		}

		return $ret;
  }

}
