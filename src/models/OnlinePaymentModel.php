<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use Ramsey\Uuid\Uuid;
use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\aaa\common\enums\enuOnlinePaymentStatus;

class OnlinePaymentModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\OnlinePaymentModelTrait;

  use \shopack\base\common\db\SoftDeleteActiveRecordTrait;
  public function initSoftDelete()
  {
    $this->softdelete_RemovedStatus  = enuOnlinePaymentStatus::REMOVED;
    $this->softdelete_StatusField    = 'onpStatus';
    $this->softdelete_RemovedAtField = 'onpRemovedAt';
    $this->softdelete_RemovedByField = 'onpRemovedBy';
	}

	public static function tableName()
	{
		return '{{%AAA_OnlinePayment}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'onpCreatedAt',
				'createdByAttribute' => 'onpCreatedBy',
				'updatedAtAttribute' => 'onpUpdatedAt',
				'updatedByAttribute' => 'onpUpdatedBy',
			],
		];
	}

	public function insert($runValidation = true, $attributes = null)
	{
		if (empty($this->onpKey))
			$this->onpKey = Uuid::uuid4()->toString();
			// $this->onpKey = Yii::$app->security->generateRandomString();

		return parent::insert($runValidation, $attributes);
	}

	// private $_onlinePaymentClass = null;
	// public function getOnlinePaymentClass()
	// {
	// 	if ($this->_onlinePaymentClass == null) {
	// 		$aaaModule = Yii::$app->getModule('aaa');
	// 		$this->_onlinePaymentClass = clone $aaaModule->OnlinePaymentClass($this->onpPluginName);
	// 		$this->_onlinePaymentClass->extensionModel = $this;
	// 	}
	// 	return $this->_onlinePaymentClass;
	// }

}
