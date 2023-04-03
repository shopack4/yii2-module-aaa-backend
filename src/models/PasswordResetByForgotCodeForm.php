<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use yii\base\Model;
use shopack\aaa\backend\models\ForgotPasswordRequestModel;

class PasswordResetByForgotCodeForm extends Model
{
  public $input;
  public $code;
  public $newPassword;

  public function rules()
  {
    return [
      ['input', 'required'],
      ['code', 'required'],
      ['newPassword', 'required'],
    ];
  }

  public function save()
  {
    if ($this->validate() == false)
      return false;

    // list ($normalizedInput, $inputType) = AuthHelper::checkLoginPhrase($this->input, false);

		return ForgotPasswordRequestModel::acceptCode($this->input, $this->code, $this->newPassword);
  }

}
