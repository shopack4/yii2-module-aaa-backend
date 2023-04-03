<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

 namespace shopack\aaa\backend\models;

use Yii;
use yii\base\Model;
use yii\web\UnprocessableEntityHttpException;
use yii\web\NotFoundHttpException;

class UpdateImageForm extends Model
{
  public $userID;
  public $email;

  public function rules()
  {
    return [
      ['userID', 'required'],
    ];
  }

  public function process()
  {
    if ($this->validate() == false)
      throw new UnprocessableEntityHttpException(implode("\n", $this->getFirstErrors()));

    $user = UserModel::findOne($this->userID);
    if ($user === null)
  		throw new NotFoundHttpException('The requested item not exist.');

    $uploadResult = Yii::$app->fileManager->saveUploadedFiles($this->userID, 'user');

    if (empty($uploadResult))
      return false;

    $result = current($uploadResult);

    $user->usrImageFileID = $result['fileID'];
    $user->save();

    return true;
  }

}
