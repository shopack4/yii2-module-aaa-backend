<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\db\Expression;
use shopack\base\common\validators\JsonValidator;
use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\aaa\backend\models\ApprovalRequestModel;
use shopack\aaa\common\enums\enuUserStatus;
use shopack\aaa\common\enums\enuRole;

class UserModel extends AAAActiveRecord
  implements \yii\web\IdentityInterface
{
  use \shopack\aaa\common\models\UserModelTrait;

  use \shopack\base\common\db\SoftDeleteActiveRecordTrait;
  public function initSoftDelete()
  {
    $this->softdelete_RemovedStatus  = enuUserStatus::Removed;
    $this->softdelete_StatusField    = 'usrStatus';
    $this->softdelete_RemovedAtField = 'usrRemovedAt';
    $this->softdelete_RemovedByField = 'usrRemovedBy';
  }

  public function init()
	{
    parent::init();
    $this->on(AAAActiveRecord::EVENT_AFTER_INSERT, [$this, 'slotAfterInsert']);
	}

  public $usrPassword;
  public $bypassRequestApprovalCode = false;

  public static function tableName()
  {
    return '{{%AAA_User}}';
  }

  public function extraRules()
  {
    return [
			['usrEmail', 'unique',
				'targetClass' => '\shopack\aaa\backend\models\UserModel',
        'filter' => ['!=', 'usrStatus', enuUserStatus::Removed],
				'message' => 'This email address has already been taken.',
				// 'on' => [self::SC_CREATE, self::SC_REGISTER, self::SC_UPDATE, self::SC_SELFUPDATE],
			],
			['usrMobile', 'unique',
				'targetClass' => '\shopack\aaa\backend\models\UserModel',
        'filter' => ['!=', 'usrStatus', enuUserStatus::Removed],
				'message' => 'This mobile number has already been taken.',
				// 'on' => [self::SC_CREATE, self::SC_REGISTER, self::SC_UPDATE, self::SC_SELFUPDATE],
			],
    ];
  }

  public function behaviors()
	{
		return [
			[
				'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
				'createdAtAttribute' => 'usrCreatedAt',
				'createdByAttribute' => 'usrCreatedBy',
				'updatedAtAttribute' => 'usrUpdatedAt',
				'updatedByAttribute' => 'usrUpdatedBy',
			],
		];
	}

  public function transactions()
  {
    return [
      self::SCENARIO_DEFAULT => self::OP_INSERT,
    ];
  }

  public static function find()
  {
    $query = parent::find();

    $query
      ->select(self::selectableColumns())
      ->addSelect(new \yii\db\Expression("usrPasswordHash IS NOT NULL AND usrPasswordHash != '' AS hasPassword"))
      ->with('imageFile')
    ;

    return $query;
  }

  public function save($runValidation = true, $attributeNames = null)
  {
    if ($this->isNewRecord) {
      if (empty($this->usrUUID)) {
        $this->usrUUID = new Expression('UUID()'); //Uuid::uuid4()->toString();
      }
    }

    if (empty($this->usrPassword) == false) {
      $this->usrPasswordHash = Yii::$app->security->generatePasswordHash($this->usrPassword);
      $this->usrPasswordCreatedAt = new Expression('NOW()');
    }

    return parent::save($runValidation, $attributeNames);
  }

  public function slotAfterInsert()
	{
    // $settings = Yii::$app->params['settings'];

    if ($this->bypassRequestApprovalCode == false) {
      if (empty($this->usrEmail) == false) {
        ApprovalRequestModel::requestCode(
          $this->usrEmail,
          $this->usrID,
          $this->usrGender,
          $this->usrFirstName,
          $this->usrLastName
        );
      }

      if (empty($this->usrMobile) == false) {
        ApprovalRequestModel::requestCode(
          $this->usrMobile,
          $this->usrID,
          $this->usrGender,
          $this->usrFirstName,
          $this->usrLastName
        );
      }
    } //bypassRequestApprovalCode
  }

  /**
   * {@inheritdoc}
   */
  public static function findIdentity($id)
  {
    return UserModel::find()
      ->where(['usrID' => $id])
      ->andWhere(['!=', 'usrStatus', enuUserStatus::Removed])
      ->one();
  }

  public static function findIdentityByAccessToken($token, $type = null)
  {
    $user = UserModel::find()
      ->joinWith('role')
      ->innerJoin(SessionModel::tableName(),
        SessionModel::tableName() . '.ssnUserID = ' . UserModel::tableName() . '.usrID'
      )
      ->where(['ssnJWT' => $token])
      ->one();

    // if ($user == null)
    //   throw new \yii\web\ForbiddenHttpException(Yii::t('yii', 'token not found'));

    return $user;
  }

  // public static function findByUsername($username)
  // {
  //   return static::find()
  //     ->joinWith('role')
  //     ->where(['usrStatus' => self::STATUS_ACTIVE])
  //     ->andWhere(['or',
  //                   ['usrEmail' => $username],
  //                   ['usrMobile' => $username]
  //               ])
  //     ->one()
  //   ;
  // }

  /**
   * {@inheritdoc}
   */
  public function getId()
  {
    return $this->usrID;
  }

  public function getAuthKey()
  {
    return null; //$this->authKey;
  }

  public function validateAuthKey($authKey)
  {
    return false;
  //   return $this->authKey === $authKey;
  }

  /**
   * Validates password
   *
   * @param string $password password to validate
   * @return bool if password provided is valid for current user
   */
  public function validatePassword($password) //, $salt)
  {
    if (empty($this->usrPasswordHash))
      return false;

		return Yii::$app->security->validatePassword($password, $this->usrPasswordHash);
    // return md5($salt . $this->usrPasswordHash) == $password;
  }

}
