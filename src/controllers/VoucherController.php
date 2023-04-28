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
use shopack\aaa\backend\models\VoucherModel;

class VoucherController extends BaseRestController
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
		if (($model = VoucherModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex()
	{
		$filter = [];
		if (PrivHelper::hasPriv('aaa/voucher/crud', '0100') == false)
			$filter = ['vchOwnerUserID' => Yii::$app->user->identity->usrID];

		$searchModel = new VoucherModel;
		$query = $searchModel::find()
			->select(VoucherModel::selectableColumns())
			->with('owner')
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
		$model = VoucherModel::find()
			->select(VoucherModel::selectableColumns())
			->with('owner')
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['vchID' => $id])
			->asArray()
			->one()
		;

		if ((PrivHelper::hasPriv('aaa/voucher/crud', '0100') == false)
			&& ($model != null)
			&& ($model->vchOwnerUserID != Yii::$app->user->identity->usrID)
		) {
			throw new ForbiddenHttpException('access denied');
		}

		return $this->modelToResponse($model);
	}

	/*
	public function actionCreate()
	{
		PrivHelper::checkPriv('aaa/voucher/crud', '1000');

		$model = new VoucherModel();
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
				'vchID' => $model->vchID,
				'vchStatus' => $model->vchStatus,
				'vchCreatedAt' => $model->vchCreatedAt,
				'vchCreatedBy' => $model->vchCreatedBy,
			// ],
		];
	}

	public function actionUpdate($id)
	{
		if (PrivHelper::hasPriv('aaa/voucher/crud', '0010') == false) {
			if (Yii::$app->user->identity->usrID != $id)
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
				'vchID' => $model->vchID,
				'vchStatus' => $model->vchStatus,
				'vchUpdatedAt' => $model->vchUpdatedAt,
				'vchUpdatedBy' => $model->vchUpdatedBy,
			// ],
		];
	}

	public function actionDelete($id)
	{
		if (PrivHelper::hasPriv('aaa/voucher/crud', '0001') == false) {
			if (Yii::$app->user->identity->usrID != $id)
				throw new ForbiddenHttpException('access denied');
		}

		$model = $this->findModel($id);

		if ($model->delete() === false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'deleted',
				'vchID' => $model->vchID,
				'vchStatus' => $model->vchStatus,
				'vchRemovedAt' => $model->vchRemovedAt,
				'vchRemovedBy' => $model->vchRemovedBy,
			// ],
		];
	}
	*/

	public function actionOptions()
	{
		return 'options';
	}

}
