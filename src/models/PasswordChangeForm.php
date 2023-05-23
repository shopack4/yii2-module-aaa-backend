<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\base\Model;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

class PasswordChangeForm extends Model
{
  public $oldPassword;
  public $newPassword;

  public function rules()
  {
    return [
      ['oldPassword', 'required'],
      ['newPassword', 'required'],
    ];
  }

  public function save()
  {
    if ($this->validate() == false)
      return false;

		$userModel = UserModel::findOne([
			'usrID' => Yii::$app->user->id
		]);

		if (!$userModel)
			throw new NotFoundHttpException('user not found');

		if ($userModel->validatePassword($this->oldPassword) == false)
			throw new ForbiddenHttpException('incorrect old password');

		$userModel->usrPassword = $this->newPassword;
		return $userModel->save();
  }

}
