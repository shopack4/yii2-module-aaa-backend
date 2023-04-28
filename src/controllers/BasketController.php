<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\controllers;

use shopack\base\backend\controller\BaseRestController;
use shopack\aaa\backend\models\BasketForm;

class BasketController extends BaseRestController
{
	public function actionAdd()
	{
		$model = new BasketForm;
		return $model->addToBasket();
	}

	/**
	 * checkout entire prevoucher
	 */
	public function actionCheckout()
	{
	}

}
