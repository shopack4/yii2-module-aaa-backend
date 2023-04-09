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
use shopack\aaa\backend\models\GeoCountryModel;

class GeoCountryController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		return $behaviors;
	}

	protected function findModel($id)
	{
		if (($model = GeoCountryModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex()
	{
		$filter = [];
		PrivHelper::checkPriv('aaa/geo-country/crud', '0100');

		$searchModel = new GeoCountryModel;
		$query = $searchModel::find()
			->select(GeoCountryModel::selectableColumns())
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
		PrivHelper::checkPriv('aaa/geo-country/crud', '0100');

		$model = GeoCountryModel::find()
			->select(GeoCountryModel::selectableColumns())
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['cntrID' => $id])
			->asArray()
			->one()
		;

		return $this->modelToResponse($model);
	}

	public function actionCreate()
	{
		PrivHelper::checkPriv('aaa/geo-country/crud', '1000');

		$model = new GeoCountryModel();
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
				'cntrID' => $model->cntrID,
				// 'cntrStatus' => $model->cntrStatus,
				'cntrCreatedAt' => $model->cntrCreatedAt,
				'cntrCreatedBy' => $model->cntrCreatedBy,
			// ],
		];
	}

	public function actionUpdate($id)
	{
		PrivHelper::checkPriv('aaa/geo-country/crud', '0010');

		$model = $this->findModel($id);
		if ($model->load(Yii::$app->request->getBodyParams(), '') == false)
			throw new NotFoundHttpException("parameters not provided");

		if ($model->save() == false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'updated',
				'cntrID' => $model->cntrID,
				// 'cntrStatus' => $model->cntrStatus,
				'cntrUpdatedAt' => $model->cntrUpdatedAt,
				'cntrUpdatedBy' => $model->cntrUpdatedBy,
			// ],
		];
	}

	public function actionDelete($id)
	{
		PrivHelper::checkPriv('aaa/geo-country/crud', '0001');

		$model = $this->findModel($id);
		if ($model->delete() == false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'deleted',
				'cntrID' => $model->cntrID,
				// 'cntrStatus' => $model->cntrStatus,
				'cntrRemovedAt' => $model->cntrRemovedAt,
				'cntrRemovedBy' => $model->cntrRemovedBy,
			// ],
		];
	}

	public function actionOptions()
	{
		return 'options';
	}

}
