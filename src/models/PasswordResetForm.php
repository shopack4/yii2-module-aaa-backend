<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use yii\base\Model;
use yii\web\NotFoundHttpException;

class PasswordResetForm extends Model
{
  public $userID;
  public $newPassword;

  public function rules()
  {
    return [
      ['userID', 'required'],
      ['newPassword', 'required'],
    ];
  }

  public function save()
  {
    if ($this->validate() == false)
      return false;

		$userModel = UserModel::findOne([
			'usrID' => $this->userID
		]);

		if (!$userModel)
			throw new NotFoundHttpException("user not found");

		$userModel->usrPassword = $this->newPassword;
		return $userModel->save();
  }

}
