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
use shopack\aaa\backend\models\GeoTownModel;

class GeoTownController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		return $behaviors;
	}

	protected function findModel($id)
	{
		if (($model = GeoTownModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex()
	{
		$filter = [];
		PrivHelper::checkPriv('aaa/geo-town/crud', '0100');

		$searchModel = new GeoTownModel;
		$query = $searchModel::find()
			->select(GeoTownModel::selectableColumns())
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->asArray()
		;

		$searchModel->fillQueryFromRequest($query);

		if (empty($filter) == false)
			$query->andWhere($filter);

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
		]);

		if (Yii::$app->request->getMethod() == 'HEAD') {
			// $totalCount = $query->count();
			$totalCount = $dataProvider->getTotalCount();
			Yii::$app->response->headers->add('X-Pagination-Total-Count', $totalCount);
			return [
				'totalCount' => $totalCount,
			];
		}

		return [
			'data' => $dataProvider->getModels(),
			// 'pagination' => [
			// 	'totalCount' => $totalCount,
			// ],
		];
	}

	public function actionView($id)
	{
		PrivHelper::checkPriv('aaa/geo-town/crud', '0100');

		$model = GeoTownModel::find()
			->select(GeoTownModel::selectableColumns())
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['twnID' => $id])
			->asArray()
			->one()
		;

		if ($model !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');

		// return RESTfulHelper::modelToResponse($this->findModel($id));
	}

	public function actionCreate()
	{
		PrivHelper::checkPriv('aaa/geo-town/crud', '1000');

		$model = new GeoTownModel();
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
				'twnID' => $model->twnID,
				// 'twnStatus' => $model->twnStatus,
				'twnCreatedAt' => $model->twnCreatedAt,
				'twnCreatedBy' => $model->twnCreatedBy,
			// ],
		];
	}

	public function actionUpdate($id)
	{
		PrivHelper::checkPriv('aaa/geo-town/crud', '0010');

		$model = $this->findModel($id);
		if ($model->load(Yii::$app->request->getBodyParams(), '') == false)
			throw new NotFoundHttpException("parameters not provided");

		if ($model->save() == false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'updated',
				'twnID' => $model->twnID,
				// 'twnStatus' => $model->twnStatus,
				'twnUpdatedAt' => $model->twnUpdatedAt,
				'twnUpdatedBy' => $model->twnUpdatedBy,
			// ],
		];
	}

	public function actionDelete($id)
	{
		PrivHelper::checkPriv('aaa/geo-town/crud', '0001');

		$model = $this->findModel($id);
		if ($model->delete() == false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'deleted',
				'twnID' => $model->twnID,
				// 'twnStatus' => $model->twnStatus,
				'twnRemovedAt' => $model->twnRemovedAt,
				'twnRemovedBy' => $model->twnRemovedBy,
			// ],
		];
	}

	public function actionOptions()
	{
		return 'options';
	}

}
