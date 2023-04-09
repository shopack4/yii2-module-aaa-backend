<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use yii\base\Model;
use yii\web\UnprocessableEntityHttpException;
use yii\web\UnauthorizedHttpException;
use shopack\base\backend\helpers\PhoneHelper;
use shopack\aaa\backend\models\UserModel;

class SignupForm extends Model
{
  public $email;
  public $mobile;
  public $password;
  public $rememberMe = false;

	// private $_inputName = '';
  private $_user = false;

  public function rules()
  {
    return [
      [[
        'email',
        'mobile',
      ], 'string'],

      ['password', 'string', 'min' => 4],

      [[
        'email',
        'mobile',
        'password',
      ], 'required'],

      ['rememberMe', 'boolean'],
    ];
  }

  public function signup()
  {
    if ($this->validate() == false)
      throw new UnauthorizedHttpException(implode("\n", $this->getFirstErrors()));

    $model = new UserModel();

    $model->usrMobile = PhoneHelper::normalizePhoneNumber($this->mobile);
		if (!$model->usrMobile)
			throw new UnprocessableEntityHttpException('Invalid mobile number');

		// list ($normalizedInput, $type) = AuthHelper::recognizeLoginPhrase($this->input);

    /*
		if ($type == AuthHelper::PHRASETYPE_EMAIL) {
			$this->_inputName = 'email';
			$model->usrEmail = $normalizedInput;
		} else if ($type == AuthHelper::PHRASETYPE_MOBILE) {
			$this->_inputName = 'mobile';
			$model->usrMobile = $normalizedInput;
		// } else if ($type == AuthHelper::PHRASETYPE_SSID) {
		// 	$this->_inputName = 'ssid';
		// 	$model->usrSSID = $normalizedInput;
		} else
			throw new UnprocessableEntityHttpException('Invalid input');
    */

    $model->usrEmail = $this->email;
		$model->usrPassword = $this->password;

    if ($model->save() == false)
      throw new UnprocessableEntityHttpException(implode("\n", $model->getFirstErrors()));

    $this->_user = $model;
    return true;
  }

	// public function getInputName()
  // {
  //   return $this->_inputName;
  // }

	public function getUser()
  {
    return $this->_user;
  }

}
