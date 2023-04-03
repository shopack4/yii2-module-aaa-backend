<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\components;

use Yii;
use yii\web\User as BaseUser;
use shopack\base\backend\helpers\PrivHelper;

class User extends BaseUser
{
	public $identityClass = \shopack\aaa\backend\models\UserModel::class;

	//current sessions jwt token dataset
	public ?\Lcobucci\JWT\Token\Plain $accessToken = null;

	public function loginByAccessToken($token, $type = null)
	{
		$identity = parent::loginByAccessToken($token, $type);

		if ($identity)
			$this->accessToken = Yii::$app->jwt->parse($token);

		return $identity;
	}

	public function hasPriv($path, $priv='1')
	{
		return PrivHelper::hasPriv($path, $priv);
	}

}
