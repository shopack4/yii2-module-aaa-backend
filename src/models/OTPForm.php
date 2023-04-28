<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use yii\base\Model;
use shopack\base\backend\helpers\AuthHelper;
use yii\web\UnprocessableEntityHttpException;
use yii\web\UnauthorizedHttpException;
use shopack\base\backend\helpers\PhoneHelper;
use shopack\aaa\common\enums\enuUserStatus;

class OTPForm extends Model
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
		if (empty($this->code)) {
			$userID = null;
			$gender = null;
			$firstName = null;
			$lastName = null;

			$user = UserModel::find()
				->where('usrStatus != \'' . enuUserStatus::Removed . '\'')
				->andWhere(['usrMobile' => $normalizedMobile])
				->one();

			if ($user) {
				$userID    = $user->usrID;
				$gender    = $user->usrGender;
				$firstName = $user->usrFirstName;
				$lastName  = $user->usrLastName;
			}

			return ApprovalRequestModel::requestCode(
				$normalizedMobile,
				$userID,
				$gender,
				$firstName,
				$lastName,
				true
			);
		}

		//login
		//------------------------
		$userModel = ApprovalRequestModel::acceptCode($normalizedMobile, $this->code);
		if ($userModel) {
			list ($token, $mustApprove) = AuthHelper::doLogin($userModel);

			return [
				'token' => $token,
				'mustApprove' => $mustApprove,
			];
		}

		throw new UnauthorizedHttpException("could not login. \n" . implode("\n", $this->getFirstErrors()));
	}

}
