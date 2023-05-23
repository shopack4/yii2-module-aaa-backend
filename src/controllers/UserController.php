<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\controllers;

use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UnprocessableEntityHttpException;
use shopack\base\backend\controller\BaseRestController;
use shopack\base\backend\helpers\PrivHelper;
use shopack\aaa\backend\models\UserModel;
use shopack\aaa\backend\models\EmailChangeForm;
use shopack\aaa\backend\models\UpdateImageForm;

class UserController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		return $behaviors;
	}

	// 'GET,HEAD  users'      => 'user/index'   : return a list/overview/options of users
	// 'GET,HEAD  users/<id>' => 'user/view'    : return the details/overview/options of a user
	// 'POST      users'      => 'user/create'  : create a new user
	// 'PUT,PATCH users/<id>' => 'user/update'  : update a user
	// 'DELETE    users/<id>' => 'user/delete'  : delete a user
	// '          users/<id>' => 'user/options' : process all unhandled verbs of a user
	// '          users'      => 'user/options' : process all unhandled verbs of user collection

	protected function findModel($id)
	{
		if (($model = UserModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex()
	{
		$filter = [];
		if (PrivHelper::hasPriv('aaa/user/crud', '0100') == false)
			$filter = ['usrID' => Yii::$app->user->id];

		$searchModel = new UserModel;
		$query = $searchModel::find()
			// ->select(UserModel::selectableColumns())
			->joinWith('role')
			->joinWith('country')
			->joinWith('state')
			->joinWith('cityOrVillage')
			->joinWith('town')
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
		if (PrivHelper::hasPriv('aaa/user/crud', '0100') == false) {
			if (Yii::$app->user->id != $id)
				throw new ForbiddenHttpException('access denied');
		}

		$model = UserModel::find()
			// ->select(UserModel::selectableColumns())
			->joinWith('role')
			->joinWith('country')
			->joinWith('state')
			->joinWith('cityOrVillage')
			->joinWith('town')
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['usrID' => $id])
			->asArray()
			->one()
		;

		return $this->modelToResponse($model);
	}

	public function actionCreate()
	{
		PrivHelper::checkPriv('aaa/user/crud', '1000');

		$model = new UserModel();
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
				'usrID' => $model->usrID,
				'usrStatus' => $model->usrStatus,
				'usrCreatedAt' => $model->usrCreatedAt,
				'usrCreatedBy' => $model->usrCreatedBy,
			// ],
		];
	}

	public function actionUpdate($id)
	{
		if (PrivHelper::hasPriv('aaa/user/crud', '0010') == false) {
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
				'usrID' => $model->usrID,
				'usrStatus' => $model->usrStatus,
				'usrUpdatedAt' => $model->usrUpdatedAt,
				'usrUpdatedBy' => $model->usrUpdatedBy,
			// ],
		];
	}

	public function actionDelete($id)
	{
		if (PrivHelper::hasPriv('aaa/user/crud', '0001') == false) {
			if (Yii::$app->user->id != $id)
				throw new ForbiddenHttpException('access denied');
		}

		$model = $this->findModel($id);
		if ($model->delete() === false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'deleted',
				'usrID' => $model->usrID,
				'usrStatus' => $model->usrStatus,
				'usrRemovedAt' => $model->usrRemovedAt,
				'usrRemovedBy' => $model->usrRemovedBy,
			// ],
		];
	}

	public function actionOptions()
	{
		return 'options';
	}

	public function actionWhoAmI()
	{
		return [
			Yii::$app->user->identity,
			Yii::$app->user->accessToken->claims()->all(),
			Yii::$app->user->accessToken->toString(),
		];
	}

	public function actionEmailChange()
	{
		$model = new EmailChangeForm();

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

	public function actionUpdateImage($id)
	{
		if (PrivHelper::hasPriv('aaa/user/crud', '0010') == false) {
			if (Yii::$app->user->id != $id)
				throw new ForbiddenHttpException('access denied');
		}

		$model = new UpdateImageForm();
		$model->userID = $id;

		// if ($model->load(Yii::$app->request->getBodyParams(), '') == false)
		// 	throw new NotFoundHttpException("parameters not provided");

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
