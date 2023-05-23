<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\db\Expression;
use yii\web\UnprocessableEntityHttpException;
use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\aaa\common\enums\enuVoucherType;
use shopack\aaa\common\enums\enuVoucherStatus;
use shopack\aaa\common\enums\enuVoucherItemStatus;
use shopack\base\common\helpers\HttpHelper;
use shopack\base\common\security\RsaPublic;

class VoucherModel extends AAAActiveRecord
{
	use \shopack\aaa\common\models\VoucherModelTrait;

  use \shopack\base\common\db\SoftDeleteActiveRecordTrait;
  public function initSoftDelete()
  {
    $this->softdelete_RemovedStatus  = enuVoucherStatus::Removed;
    // $this->softdelete_StatusField    = 'vchStatus';
    $this->softdelete_RemovedAtField = 'vchRemovedAt';
    $this->softdelete_RemovedByField = 'vchRemovedBy';
	}

	public static function tableName()
	{
		return '{{%AAA_Voucher}}';
	}

	public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'vchCreatedAt',
				'createdByAttribute' => 'vchCreatedBy',
				'updatedAtAttribute' => 'vchUpdatedAt',
				'updatedByAttribute' => 'vchUpdatedBy',
			],
		];
	}

	public function processVoucher()
	{
		if ($this->vchType != enuVoucherType::Basket)
			return true;

		// if ($this->vchStatus != enuVoucherStatus::Settled)
    //   throw new UnprocessableEntityHttpException('The voucher not settled');

		if ($this->vchAmount != $this->vchTotalPaid ?? 0)
      throw new UnprocessableEntityHttpException('This voucher not paid totaly');

		$errorCount = 0;
		$vchItems = $this->vchItems;
		foreach ($vchItems as $k => $voucherItem) {
			if (empty($vchItems[$k]['status'])
				|| ($vchItems[$k]['status'] != enuVoucherItemStatus::Processed)
			) {
				try {
					$this->processVoucherItem($voucherItem);
					$vchItems[$k]['status'] = enuVoucherItemStatus::Processed;
					unset($vchItems[$k]['error']);

				} catch (\Throwable $th) {
					++$errorCount;
					$vchItems[$k]['status'] = enuVoucherItemStatus::Error;
					$vchItems[$k]['error'] = $th->getMessage();
					//throw $th;
				}
			}
		}

		$this->vchItems = $vchItems;
		$this->vchStatus = ($errorCount > 0 ? enuVoucherStatus::Error : enuVoucherStatus::Finished);
		return $this->save();
	}

	public function processVoucherItem($voucherItem)
	{
		$service = $voucherItem['service'];

		if ($service == 'aaa') {
			//todo: process aaa voucher item
			return true;
		}

		//other services:

		// $key       = $voucherItem['key'];
		// $slbkey    = $voucherItem['slbkey'];
		// $slbid     = $voucherItem['slbid'];
		// $desc      = $voucherItem['desc'];
		// $qty       = $voucherItem['qty'];
		// $unitprice = $voucherItem['unitprice'];

		$data = json_encode($voucherItem);

		if (empty(Yii::$app->controller->module->servicesPublicKeys[$service]))
			$data = base64_encode($data);
		else
			$data = RsaPublic::model(Yii::$app->controller->module->servicesPublicKeys[$service])->encrypt($data);

		list ($resultStatus, $resultData) = HttpHelper::callApi($service . "/service/process-voucher-item",
			HttpHelper::METHOD_POST,
			[],
			[
				'data' => $data,
			]
		);

		if ($resultStatus < 200 || $resultStatus >= 300)
			throw new \yii\web\HttpException($resultStatus, Yii::t('aaa', $resultData['message'], $resultData));
	}

}
