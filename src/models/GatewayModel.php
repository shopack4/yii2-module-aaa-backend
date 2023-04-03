<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use Ramsey\Uuid\Uuid;
use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\aaa\common\enums\enuGatewayStatus;

class GatewayModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\GatewayModelTrait;

  use \shopack\base\common\db\SoftDeleteActiveRecordTrait;
  public function initSoftDelete()
  {
    $this->softdelete_RemovedStatus  = enuGatewayStatus::REMOVED;
    $this->softdelete_StatusField    = 'gtwStatus';
    $this->softdelete_RemovedAtField = 'gtwRemovedAt';
    $this->softdelete_RemovedByField = 'gtwRemovedBy';
	}

	public static function tableName()
	{
		return '{{%AAA_Gateway}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'gtwCreatedAt',
				'createdByAttribute' => 'gtwCreatedBy',
				'updatedAtAttribute' => 'gtwUpdatedAt',
				'updatedByAttribute' => 'gtwUpdatedBy',
			],
		];
	}

	public function insert($runValidation = true, $attributes = null)
	{
		if (empty($this->gtwKey))
			$this->gtwKey = Uuid::uuid4()->toString();
			// $this->gtwKey = Yii::$app->security->generateRandomString();

		return parent::insert($runValidation, $attributes);
	}

	private $_gatewayClass = null;
	public function getGatewayClass()
	{
		if ($this->_gatewayClass == null) {
			$aaaModule = Yii::$app->getModule('aaa');
			$this->_gatewayClass = clone $aaaModule->GatewayClass($this->gtwPluginName);
			$this->_gatewayClass->extensionModel = $this;
		}
		return $this->_gatewayClass;
	}

}
