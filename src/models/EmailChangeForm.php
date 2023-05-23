<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

 namespace shopack\aaa\backend\models;

use Yii;
use yii\base\Model;
use yii\web\UnauthorizedHttpException;
use yii\web\UnprocessableEntityHttpException;
use shopack\base\backend\helpers\AuthHelper;
use shopack\aaa\backend\models\ApprovalRequestModel;

class EmailChangeForm extends Model
{
  public $email;

  public function rules()
  {
    return [
      ['email', 'required'],
    ];
  }

  public function process()
  {
    if (Yii::$app->user->isGuest)
      throw new UnauthorizedHttpException("This process is not for guest.");

    $this->email = strtolower(trim($this->email));

    if ($this->validate() == false)
      throw new UnauthorizedHttpException(implode("\n", $this->getFirstErrors()));

    if (AuthHelper::isEmail($this->email) == false)
      throw new UnprocessableEntityHttpException("Invalid email");

    $user = UserModel::findOne(Yii::$app->user->id);

    if ((empty($user->usrEmail) == false) && ($this->email == $user->usrEmail))
      throw new UnprocessableEntityHttpException("New email is the same as the current.");

    return ApprovalRequestModel::requestCode(
      $this->email,
      $user->usrID,
      $user->usrGender,
      $user->usrFirstName,
      $user->usrLastName
    );
  }

}
