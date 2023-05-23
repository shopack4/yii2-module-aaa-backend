<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\controllers;

use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\data\ActiveDataProvider;
use shopack\base\backend\controller\BaseRestController;
use shopack\base\backend\helpers\PrivHelper;
use shopack\aaa\common\enums\enuPaymentGatewayType;
use shopack\aaa\backend\models\OnlinePaymentModel;
use shopack\aaa\backend\models\GatewayModel;
use shopack\aaa\common\enums\enuGatewayStatus;
use shopack\base\common\helpers\ArrayHelper;
use shopack\aaa\common\enums\enuVoucherType;

class OnlinePaymentController extends BaseRestController
{
	public function behaviors()
	{
		$behaviors = parent::behaviors();

		$behaviors[BaseRestController::BEHAVIOR_AUTHENTICATOR]['except'] = [
			'callback',
			'devtestpaymentpage',
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
			$filter = ['vchOwnerUserID' => Yii::$app->user->id];

		$searchModel = new OnlinePaymentModel;
		$query = $searchModel::find()
			->select(OnlinePaymentModel::selectableColumns())
			->joinWith('gateway')
			->joinWith('voucher')
			->joinWith('voucher.owner')
			->joinWith('wallet')
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
		$model = OnlinePaymentModel::find()
			->select(OnlinePaymentModel::selectableColumns())
			->joinWith('gateway')
			->joinWith('voucher')
			->joinWith('voucher.owner')
			->joinWith('wallet')
			->with('createdByUser')
			->with('updatedByUser')
			->with('removedByUser')
			->where(['onpID' => $id])
			->asArray()
			->one()
		;

		if ((PrivHelper::hasPriv('aaa/online-payment/crud', '0100') == false)
			&& ($model != null)
			&& (($model['voucher']['vchOwnerUserID'] ?? null) != Yii::$app->user->id)
		) {
			throw new ForbiddenHttpException('access denied');
		}

		return $this->modelToResponse($model);
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
			if (Yii::$app->user->id != $id)
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

	public function actionGetAllowedTypes()
	{
		$types = [];

		$models = GatewayModel::find()
			->select('gtwPluginName')
			->andWhere(['gtwPluginType' => 'payment'])
			->andWhere(['gtwStatus' => enuGatewayStatus::Active])
			->groupBy('gtwPluginName')
			->asArray()
			->all();
		;

		if (empty($models) == false) {
			foreach ($models as $model) {
				$gtwclass = Yii::$app->controller->module->GatewayClass($model['gtwPluginName']);

				$type = $gtwclass->getPaymentGatewayType();;

				if (YII_ENV_PROD && ($type == enuPaymentGatewayType::DevTest))
					continue;

				$types[] = $type;
			}
		}

		return $types;
	}

	public function actionDevtestpaymentpage($paymentkey, $callback)
	{
		if (!YII_ENV_DEV)
			throw new ServerErrorHttpException('only dev mode allowed');

		$onlinePaymentModel = OnlinePaymentModel::find()
      ->andWhere(['onpUUID' => $paymentkey])
      ->one();

		$this->response->format = \yii\web\Response::FORMAT_HTML;

		return <<<HTML
<p>this is test payment page</p>
<p>paymentkey: {$paymentkey}</p>
<p>amount: {$onlinePaymentModel->onpAmount}</p>
<p>callback: {$callback}</p>
<p>frontend callback: {$onlinePaymentModel->onpCallbackUrl}</p>
<p><a href='{$callback}?result=ok'>[OK]</a></p>
<p><a href='{$callback}?result=error'>[ERROR]</a></p>
<p><a href='{$callback}?result=cancel'>[CANCEL]</a></p>
HTML;
	}

	//accepts all http methods
	public function actionCallback($paymentkey)
  {
		$pgwResponse = array_merge(
			Yii::$app->request->getQueryParams(),
			Yii::$app->request->getBodyParams(),
		);

		try {
			$onlinePaymentModel = Yii::$app->paymentManager->approveOnlinePayment($paymentkey, $pgwResponse);

			$done = $onlinePaymentModel->voucher->processVoucher();

		} catch (\Throwable $th) {
			if (empty($onlinePaymentModel)) {
				$onlinePaymentModel = OnlinePaymentModel::find()
					->joinWith('gateway')
					->joinWith('voucher')
					->andWhere(['onpUUID' => $paymentkey])
					->one();
			}

			$onlinePaymentModel->addError('', $th->getMessage());

			//throw $th;
		}

		//---
		$url = $onlinePaymentModel->onpCallbackUrl;
		if (strpos($url, '?') === false)
			$url .= '?';
		else
			$url .= '&';
		$url .= 'paymentkey=' . $paymentkey;

		$errors = $onlinePaymentModel->getErrorSummary(true);
		if ($errors)
			$url .= '&errors=' . urlencode(implode('\n', $errors));

		$this->redirect($url);

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
