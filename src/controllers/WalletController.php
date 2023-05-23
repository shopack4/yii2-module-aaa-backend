<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\controllers;

use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;
use yii\data\ActiveDataProvider;
use shopack\base\backend\controller\BaseRestController;
use shopack\base\backend\helpers\PrivHelper;
use shopack\aaa\backend\models\WalletModel;
use shopack\aaa\common\enums\enuWalletStatus;
use shopack\aaa\backend\models\WalletIncreaseForm;
use shopack\aaa\common\enums\enuPaymentGatewayType;

class WalletController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();

		// $behaviors[BaseRestController::BEHAVIOR_AUTHENTICATOR]['except'] = [
		// 	'callback',
		// ];

		return $behaviors;
	}

	public function beforeAction($action)
  {
		if ($action->id != 'ensure-i-have-default-wallet')
	    WalletModel::ensureIHaveDefaultWallet();

    return parent::beforeAction($action);
  }

	protected function findModel($id)
	{
		if (($model = WalletModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex($justForMe = false)
	{
		WalletModel::ensureIHaveDefaultWallet();

		$filter = [];
		if ($justForMe || (PrivHelper::hasPriv('aaa/wallet/crud', '0100') == false)) {
			$filter = ['walOwnerUserID' => Yii::$app->user->id];
		}

		$searchModel = new WalletModel;
		$query = $searchModel::find()
			->select(WalletModel::selectableColumns())
			->joinWith('owner')
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
		$model = WalletModel::find()
			->select(WalletModel::selectableColumns())
			->joinWith('owner')
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['walID' => $id])
			->asArray()
			->one()
		;

		if ((PrivHelper::hasPriv('aaa/wallet/crud', '0100') == false)
			&& ($model != null)
			&& ($model['walOwnerUserID'] != Yii::$app->user->id)
		) {
			throw new ForbiddenHttpException('access denied');
		}

		return $this->modelToResponse($model);
	}

	/*
	public function actionCreate()
	{
		PrivHelper::checkPriv('aaa/wallet/crud', '1000');

		$model = new WalletModel();
		if ($model->load(Yii::$app->request->getBodyParams(), '') == false)
			throw new NotFoundHttpException("parameters not provided");

		try {
			if ($model->save() == false)
				throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));
		} catch(\Exception $exp) {
			$msg = $exp->getMessage();
			if (stripos($msg, 'duplicate entry') !== false)
				$msg = 'DUPLICATE';
			throw new UnprocessableEntityHttpException($msg);
		}

		return [
			// 'result' => [
				// 'message' => 'created',
				'walID' => $model->walID,
				'walStatus' => $model->walStatus,
				'walCreatedAt' => $model->walCreatedAt,
				'walCreatedBy' => $model->walCreatedBy,
			// ],
		];
	}

	public function actionUpdate($id)
	{
		if (PrivHelper::hasPriv('aaa/wallet/crud', '0010') == false) {
			if (Yii::$app->user->id != $id)
				throw new ForbiddenHttpException('access denied');
		}

		$model = $this->findModel($id);

		if ($model->load(Yii::$app->request->getBodyParams(), '') == false)
			throw new NotFoundHttpException("parameters not provided");

		if ($model->save() == false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'updated',
				'walID' => $model->walID,
				'walStatus' => $model->walStatus,
				'walUpdatedAt' => $model->walUpdatedAt,
				'walUpdatedBy' => $model->walUpdatedBy,
			// ],
		];
	}

	public function actionDelete($id)
	{
		if (PrivHelper::hasPriv('aaa/wallet/crud', '0001') == false) {
			if (Yii::$app->user->id != $id)
				throw new ForbiddenHttpException('access denied');
		}

		$model = $this->findModel($id);

		if ($model->delete() === false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'deleted',
				'walID' => $model->walID,
				'walStatus' => $model->walStatus,
				'walRemovedAt' => $model->walRemovedAt,
				'walRemovedBy' => $model->walRemovedBy,
			// ],
		];
	}
	*/

	public function actionOptions()
	{
		return 'options';
	}

	public function actionEnsureIHaveDefaultWallet()
	{
    return $this->modelToResponse(WalletModel::ensureIHaveDefaultWallet());
	}

	public function actionIncrease($id)
	{
		$model = new WalletIncreaseForm();
		$model->walletID = $id;

		if ($model->load(Yii::$app->request->getBodyParams(), '') == false)
			throw new NotFoundHttpException("parameters not provided");

		try {
			$result = $model->process();
			if ($result == false)
				throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

			return $result;

		} catch(\Exception $exp) {
			$msg = $exp->getMessage();
			if (stripos($msg, 'duplicate entry') !== false)
				$msg = 'DUPLICATE';

			throw new UnprocessableEntityHttpException($msg);
		}
	}

}
