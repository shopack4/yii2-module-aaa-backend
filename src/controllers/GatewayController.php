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
use shopack\aaa\backend\models\GatewayModel;

class GatewayController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();

		$behaviors[BaseRestController::BEHAVIOR_AUTHENTICATOR]['except'] = [
			'plugin-list',
			'plugin-params-schema',
			'plugin-restrictions-schema',
			'plugin-usages-schema',
			'plugin-webhooks-schema',
			'webhook',
		];

		return $behaviors;
	}

	protected function findModel($id)
	{
		if (($model = GatewayModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex()
	{
		$filter = [];
		PrivHelper::checkPriv('aaa/gateway/crud', '0100');

		$searchModel = new GatewayModel;
		$query = $searchModel::find()
			->select(GatewayModel::selectableColumns())
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
		PrivHelper::checkPriv('aaa/gateway/crud', '0100');

		$model = GatewayModel::find()
			->select(GatewayModel::selectableColumns())
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['gtwID' => $id])
			->asArray()
			->one()
		;

		return $this->modelToResponse($model);
	}

	public function actionCreate()
	{
		PrivHelper::checkPriv('aaa/gateway/crud', '1000');

		$model = new GatewayModel();
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
				'gtwID' => $model->gtwID,
				'gtwStatus' => $model->gtwStatus,
				'gtwCreatedAt' => $model->gtwCreatedAt,
				'gtwCreatedBy' => $model->gtwCreatedBy,
			// ],
		];
	}

	public function actionUpdate($id)
	{
		PrivHelper::checkPriv('aaa/gateway/crud', '0010');

		$model = $this->findModel($id);

		if ($model->load(Yii::$app->request->getBodyParams(), '') == false)
			throw new NotFoundHttpException("parameters not provided");

		if ($model->save() == false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'updated',
				'gtwID' => $model->gtwID,
				'gtwStatus' => $model->gtwStatus,
				'gtwUpdatedAt' => $model->gtwUpdatedAt,
				'gtwUpdatedBy' => $model->gtwUpdatedBy,
			// ],
		];
	}

	public function actionDelete($id)
	{
		PrivHelper::checkPriv('aaa/gateway/crud', '0001');

		$model = $this->findModel($id);

		if ($model->delete() === false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'deleted',
				'gtwID' => $model->gtwID,
				'gtwStatus' => $model->gtwStatus,
				'gtwRemovedAt' => $model->gtwRemovedAt,
				'gtwRemovedBy' => $model->gtwRemovedBy,
			// ],
		];
	}

	public function actionOptions()
	{
		return 'options';
	}

	public function actionPluginList($type = null)
	{
		return $this->module->GatewayPluginList($type);
	}

	public function actionPluginParamsSchema($key)
	{
		return $this->module->GatewayPluginParamsSchema($key);
	}

	public function actionPluginRestrictionsSchema($key)
	{
		return $this->module->GatewayPluginRestrictionsSchema($key);
	}

	public function actionPluginUsagesSchema($key)
	{
		return $this->module->GatewayPluginUsagesSchema($key);
	}

	public function actionPluginWebhooksSchema($key)
	{
		return $this->module->GatewayPluginWebhooksSchema($key);
	}

	//accepts all http methods
	public function actionWebhook($gtwkey, $command)
  {
		if (($gatewayModel = GatewayModel::findOne(['gtwKey' => $gtwkey])) === null) {
			Yii::error('The requested gateway does not exist.', __METHOD__);
			throw new NotFoundHttpException("The requested gateway does not exist.");
		}

		$gatewayClass = $gatewayModel->getGatewayClass();

		if (!($gatewayClass instanceof \shopack\base\common\classes\IWebhook)) {
			Yii::error('Webhook not supported by this gateway.', __METHOD__);
			throw new UnprocessableEntityHttpException('Webhook not supported by this gateway.');
		}

		//check caller
		if (!YII_ENV_DEV) {
			if (method_exists($gatewayClass, 'validateCaller')) {
				$ret = $gatewayClass->validateCaller();
				if ($ret !== true) {
					Yii::error($ret[1], __METHOD__);
					throw new UnprocessableEntityHttpException($ret[1]);
				}
			}
		}

		$ret = $gatewayClass->callWebhook($command);

		$params = [
			'get' => $_GET,
			'post' => $_POST,
		];

		$gatewayClass->log(
			/* gtwlogMethodName */ 'webhook',
			/* gtwlogRequest    */ $params,
			/* gtwlogResponse   */ $ret
		);

		return $ret;
  }

}
