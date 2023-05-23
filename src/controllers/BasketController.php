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
use shopack\aaa\backend\models\BasketForm;
use shopack\aaa\backend\models\BasketItemForm;
use shopack\aaa\backend\models\BasketCheckoutForm;
// use shopack\base\backend\models\BasketModel;

class BasketController extends BaseRestController
{
	//just called from other services with encryption
	public function actionAddItem()
	{
		return BasketItemForm::addItem();
	}

	public function actionRemoveItem($key)
	{
		return BasketItemForm::removeItem($key);
	}

	public function actionCheckout()
	{
		$model = new BasketCheckoutForm();

		if ($model->load(Yii::$app->request->getBodyParams(), '') == false)
			throw new NotFoundHttpException("parameters not provided");

		try {
			$result = $model->checkout();
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
