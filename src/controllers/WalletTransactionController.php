<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\controllers;

use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use shopack\base\backend\controller\BaseRestController;
use shopack\base\backend\helpers\PrivHelper;
use shopack\aaa\backend\models\WalletTransactionModel;
use shopack\aaa\backend\models\WalletModel;

class WalletTransactionController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();

		// $behaviors[BaseRestController::BEHAVIOR_AUTHENTICATOR]['except'] = [
		// 	'callback',
		// ];

		return $behaviors;
	}

	protected function findModel($id)
	{
		if (($model = WalletTransactionModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex($justForMe = false)
	{
		$filter = [];
		if ($justForMe || (PrivHelper::hasPriv('aaa/wallet-transaction/crud', '0100') == false)) {
			$filter = ['walOwnerUserID' => Yii::$app->user->id];
		}

		$searchModel = new WalletTransactionModel;
		$query = $searchModel::find()
			->select(WalletTransactionModel::selectableColumns())
			->joinWith('wallet')
			->joinWith('voucher')
			->joinWith('onlinePayment')
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->asArray()
		;

		$searchModel->fillQueryFromRequest($query);

		if (empty($filter) == false)
			$query->andWhere($filter);

		return $this->queryAllToResponse($query);
	}

	public function actionView($id)
	{
		$model = WalletTransactionModel::find()
			->select(WalletTransactionModel::selectableColumns())
			->joinWith('wallet')
			->joinWith('voucher')
			->joinWith('onlinePayment')
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['wtrID' => $id])
			->asArray()
			->one()
		;

		if ((PrivHelper::hasPriv('aaa/wallet-transaction/crud', '0100') == false)
			&& ($model != null)
			&& (($model['wallet']['walOwnerUserID'] ?? null) != Yii::$app->user->id)
		) {
			throw new ForbiddenHttpException('access denied');
		}

		return $this->modelToResponse($model);
	}

	public function actionOptions()
	{
		return 'options';
	}

}
