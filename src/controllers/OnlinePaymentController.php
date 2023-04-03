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
use shopack\aaa\backend\models\OnlinePaymentModel;

class OnlinePaymentController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();

		$behaviors[BaseRestController::BEHAVIOR_AUTHENTICATOR]['except'] = [
			'callback',
		];

		return $behaviors;
	}

	protected function findModel($id)
	{
		if (($model = OnlinePaymentModel::findOne($id)) !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');
	}

	public function actionIndex()
	{
		$filter = [];
		if (PrivHelper::hasPriv('aaa/online-payment/crud', '0100') == false)
			$filter = ['onpID' => Yii::$app->user->identity->onpID];

		$searchModel = new OnlinePaymentModel;
		$query = $searchModel::find()
			->select(OnlinePaymentModel::selectableColumns())
			->asArray()
		;

		$searchModel->fillQueryFromRequest($query);

		if (empty($filter) == false)
			$query->andWhere($filter);

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
		]);

		if (Yii::$app->request->getMethod() == 'HEAD') {
			$totalCount = $dataProvider->getTotalCount();
			// $totalCount = $query->count();
			Yii::$app->response->headers->add('X-Pagination-Total-Count', $totalCount);
			return [];
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
		if (PrivHelper::hasPriv('aaa/online-payment/crud', '0100') == false) {
			if (Yii::$app->user->identity->onpID != $id)
				throw new ForbiddenHttpException('access denied');
		}

		$model = OnlinePaymentModel::find()
			->select(OnlinePaymentModel::selectableColumns())
			->where(['onpID' => $id])
			->asArray()
			->one()
		;

		if ($model !== null)
			return $model;

		throw new NotFoundHttpException('The requested item not exist.');

		// return RESTfulHelper::modelToResponse($this->findModel($id));
	}

	/*
	public function actionCreate()
	{
		PrivHelper::checkPriv('aaa/online-payment/crud', '1000');

		$model = new OnlinePaymentModel();
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
				'onpID' => $model->onpID,
				'onpStatus' => $model->onpStatus,
				'onpCreatedAt' => $model->onpCreatedAt,
				'onpCreatedBy' => $model->onpCreatedBy,
			// ],
		];
	}

	public function actionUpdate($id)
	{
		if (PrivHelper::hasPriv('aaa/online-payment/crud', '0010') == false) {
			if (Yii::$app->user->identity->onpID != $id)
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
				'onpID' => $model->onpID,
				'onpStatus' => $model->onpStatus,
				'onpUpdatedAt' => $model->onpUpdatedAt,
				'onpUpdatedBy' => $model->onpUpdatedBy,
			// ],
		];
	}

	public function actionDelete($id)
	{
		if (PrivHelper::hasPriv('aaa/online-payment/crud', '0001') == false) {
			if (Yii::$app->user->identity->onpID != $id)
				throw new ForbiddenHttpException('access denied');
		}

		$model = $this->findModel($id);

		if ($model->delete() === false)
			throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

		return [
			// 'result' => [
				// 'message' => 'deleted',
				'onpID' => $model->onpID,
				'onpStatus' => $model->onpStatus,
				'onpRemovedAt' => $model->onpRemovedAt,
				'onpRemovedBy' => $model->onpRemovedBy,
			// ],
		];
	}
	*/

	public function actionOptions()
	{
		return 'options';
	}

	public function actionPluginList($type = null)
	{
		return $this->module->OnlinePaymentPluginList($type);
	}

	public function actionPluginParamsSchema($key)
	{
		return $this->module->OnlinePaymentPluginParamsSchema($key);
	}

	public function actionPluginRestrictionsSchema($key)
	{
		return $this->module->OnlinePaymentPluginRestrictionsSchema($key);
	}

	public function actionPluginUsagesSchema($key)
	{
		return $this->module->OnlinePaymentPluginUsagesSchema($key);
	}

	public function actionPluginWebhooksSchema($key)
	{
		return $this->module->OnlinePaymentPluginWebhooksSchema($key);
	}

	//accepts all http methods
	public function actionCallback($onpid)
  {
		return ['onpid' => $onpid];




		if (($onlinePaymentModel = OnlinePaymentModel::findOne(['onpID' => $onpid])) === null) {
			Yii::error('The requested online payment does not exist.', __METHOD__);
			throw new NotFoundHttpException("The requested online payment does not exist.");
		}

		// $onlinePaymentClass = $onlinePaymentModel->getOnlinePaymentClass();

		// if (!($onlinePaymentClass instanceof \shopack\base\common\classes\IWebhook)) {
		// 	Yii::error('Webhook not supported by this online-payment.', __METHOD__);
		// 	throw new UnprocessableEntityHttpException('Webhook not supported by this online-payment.');
		// }

		// //check caller
		// if (!YII_ENV_DEV) {
		// 	if (method_exists($onlinePaymentClass, 'validateCaller')) {
		// 		$ret = $onlinePaymentClass->validateCaller();
		// 		if ($ret !== true) {
		// 			Yii::error($ret[1], __METHOD__);
		// 			throw new UnprocessableEntityHttpException($ret[1]);
		// 		}
		// 	}
		// }

		// $ret = $onlinePaymentClass->callWebhook($command);

		// $params = [
		// 	'get' => $_GET,
		// 	'post' => $_POST,
		// ];

		// $onlinePaymentClass->log(
		// 	/* onplogMethodName */ 'webhook',
		// 	/* onplogRequest    */ $params,
		// 	/* onplogResponse   */ $ret
		// );

		// return $ret;
  }

}
