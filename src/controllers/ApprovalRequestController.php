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
use shopack\aaa\backend\models\ApprovalRequestModel;

class ApprovalRequestController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		return $behaviors;
	}

	protected function findModel($id)
	{
		if (($model = ApprovalRequestModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex()
	{
		$filter = [];
		if (PrivHelper::hasPriv('aaa/approval-request/crud', '0100') == false)
			$filter = ['aprUserID' => Yii::$app->user->identity->usrID];

		$searchModel = new ApprovalRequestModel;
		$query = $searchModel::find()
			// ->select(ApprovalRequestModel::selectableColumns())
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
		if (PrivHelper::hasPriv('aaa/approval-request/crud', '0100') == false) {
			if (Yii::$app->user->identity->usrID != $id)
				throw new ForbiddenHttpException('access denied');
		}

		$model = ApprovalRequestModel::find()
			// ->select(ApprovalRequestModel::selectableColumns())
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['aprUserID' => $id])
			->asArray()
			->one()
		;

		if ($model !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');

		// return RESTfulHelper::modelToResponse($this->findModel($id));
	}

	public function actionOptions()
	{
		return 'options';
	}

}
