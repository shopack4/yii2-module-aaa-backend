<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;
use yii\data\ActiveDataProvider;
use shopack\base\backend\controller\BaseRestController;
use shopack\base\backend\helpers\PrivHelper;
use shopack\aaa\backend\models\GeoCityOrVillageModel;

class GeoCityOrVillageController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		return $behaviors;
	}

	protected function findModel($id)
	{
		if (($model = GeoCityOrVillageModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex()
	{
		$filter = [];
		PrivHelper::checkPriv('aaa/geo-city-or-village/crud', '0100');

		$searchModel = new GeoCityOrVillageModel;
		$query = $searchModel::find()
			->select(GeoCityOrVillageModel::selectableColumns())
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
		PrivHelper::checkPriv('aaa/geo-city-or-village/crud', '0100');

		$model = GeoCityOrVillageModel::find()
			->select(GeoCityOrVillageModel::selectableColumns())
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['ctvID' => $id])
			->asArray()
			->one()
		;

		return $this->modelToResponse($model);
	}

	public function actionCreate()
	{
		PrivHelper::checkPriv('aaa/geo-city-or-village/crud', '1000');

		$model = new GeoCityOrVillageModel();
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
				'ctvID' => $model->ctvID,
				// 'ctvStatus' => $model->ctvStatus,
				'ctvCreatedAt' => $model->ctvCreatedAt,
				'ctvCreatedBy' => $model->ctvCreatedBy,
			// ],
		];
	}

	public function actionUpdate($id)
	{
		PrivHelper::checkPriv('aaa/geo-city-or-village/crud', '0010');

		$model = $this->findModel($id);
		if ($model->load(Yii::$app->request->getBodyParams(), '') == false)
			throw new NotFoundHttpException("parameters not provided");

		if ($model->save() == false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'updated',
				'ctvID' => $model->ctvID,
				// 'ctvStatus' => $model->ctvStatus,
				'ctvUpdatedAt' => $model->ctvUpdatedAt,
				'ctvUpdatedBy' => $model->ctvUpdatedBy,
			// ],
		];
	}

	public function actionDelete($id)
	{
		PrivHelper::checkPriv('aaa/geo-city-or-village/crud', '0001');

		$model = $this->findModel($id);
		if ($model->delete() == false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'deleted',
				'ctvID' => $model->ctvID,
				// 'ctvStatus' => $model->ctvStatus,
				'ctvRemovedAt' => $model->ctvRemovedAt,
				'ctvRemovedBy' => $model->ctvRemovedBy,
			// ],
		];
	}

	public function actionOptions()
	{
		return 'options';
	}

}
