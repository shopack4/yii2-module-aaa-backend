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
			$filter = ['aprUserID' => Yii::$app->user->id];

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

		return $this->queryAllToResponse($query);
	}

	public function actionView($id)
	{
		if (PrivHelper::hasPriv('aaa/approval-request/crud', '0100') == false) {
			if (Yii::$app->user->id != $id)
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

		return $this->modelToResponse($model);
	}

	public function actionOptions()
	{
		return 'options';
	}

}
