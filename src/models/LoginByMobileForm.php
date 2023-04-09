<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use yii\base\Model;
use yii\web\UnprocessableEntityHttpException;
use yii\web\UnauthorizedHttpException;
use shopack\base\backend\helpers\PhoneHelper;
use shopack\aaa\common\enums\enuUserStatus;

class LoginByMobileForm extends Model
{
  public $mobile;
  // public $code;

  public function rules()
  {
    return [
      ['mobile', 'required'],
      // ['code', 'string'],
    ];
  }

	public function process()
	{
    if ($this->validate() == false)
      throw new UnauthorizedHttpException(implode("\n", $this->getFirstErrors()));

		$normalizedMobile = PhoneHelper::normalizePhoneNumber($this->mobile);
		if (!$normalizedMobile)
			throw new UnprocessableEntityHttpException('Invalid mobile number');

		//send code
		//------------------------
		// if (empty($this->code)) {
			// $userID = null;
			// $gender = null;
			// $firstName = null;
			// $lastName = null;

			$user = UserModel::find()
				->andWhere('usrStatus != \'' . enuUserStatus::REMOVED . '\'')
				->andWhere(['usrMobile' => $normalizedMobile])
				->one();

			if (!$user) {
				$user = new UserModel();
				$user->usrMobile = $normalizedMobile;
				$user->bypassRequestApprovalCode = true;
				$user->usrStatus = enuUserStatus::NEW_FOR_LOGIN_BY_MOBILE;

				if ($user->save() == false)
        	throw new UnprocessableEntityHttpException("could not create new user\n" . implode("\n", $user->getFirstErrors()));
			}

			// if ($user) {
				$userID    = $user->usrID;
				$gender    = $user->usrGender;
				$firstName = $user->usrFirstName;
				$lastName  = $user->usrLastName;
			// }

			$result = ApprovalRequestModel::requestCode(
				$normalizedMobile,
				$userID,
				$gender,
				$firstName,
				$lastName,
				true
			);

			// list ($token, $mustApprove) = AuthHelper::doLogin($user, false, ['otp' => 'sms']);

			return array_merge([
				// 'token' => $token,
				'challenge' => 'otp,type=sms',
			],
			$result);
		// } // if (empty($this->code))

		//login
		//------------------------
		// $userModel = ApprovalRequestModel::acceptCode($normalizedMobile, $this->code);
		// if ($userModel) {
		// 	list ($token, $mustApprove) = AuthHelper::doLogin($userModel);

		// 	return [
		// 		'token' => $token,
		// 		'mustApprove' => $mustApprove,
		// 	];
		// }

		// throw new UnauthorizedHttpException("could not login. \n" . implode("\n", $this->getFirstErrors()));
	}

}
