<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use shopack\aaa\backend\classes\AAAActiveRecord;
use yii\db\Expression;
use shopack\base\common\helpers\ArrayHelper;
use shopack\base\backend\helpers\AuthHelper;
use yii\web\UnauthorizedHttpException;
use yii\web\UnprocessableEntityHttpException;
use shopack\aaa\backend\models\UserModel;
use shopack\aaa\backend\models\AlertModel;
use shopack\base\backend\helpers\GeneralHelper;
use shopack\aaa\common\enums\enuForgotPasswordRequestKeyType;
use shopack\aaa\common\enums\enuForgotPasswordRequestAlertType;
use shopack\aaa\common\enums\enuForgotPasswordRequestStatus;

class ForgotPasswordRequestModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\ForgotPasswordRequestModelTrait;

  public $ElapsedSeconds;
  public $IsExpired;

  public static function tableName()
  {
    return '{{%AAA_ForgotPasswordRequest}}';
  }

  // public function rules()
  // {
  //   return [
  //     ['fprID', 'integer'],
  //     ['fprUserID', 'integer'],

  //     ['fprRequestedBy', 'string', 'max' => 1],

  //     ['fprCode', 'string', 'max' => 48],

  //     ['fprLastRequestAt', 'safe'],
  //     ['fprExpireAt', 'safe'],
  //     ['fprSentAt', 'safe'],
  //     ['fprApplyAt', 'safe'],

  //     ['fprStatus', 'string', 'max' => 1],
  //     ['fprStatus', 'default', 'value' => enuForgotPasswordRequestStatus::NEW],

  //     ['fprCreatedAt', 'safe'],
  //     // ['fprCreatedBy', 'integer'],
  //     // ['fprUpdatedAt', 'safe'],
  //     // ['fprUpdatedBy', 'integer'],

  //     [[
  //       'fprUserID',
  //       'fprRequestedBy',
  //       'fprCode',
  //       'fprLastRequestAt',
  //       'fprExpireAt',
  //       'fprStatus',
  //     ], 'required'],

  //   ];
  // }

  public function behaviors()
  {
    return [
      [
        'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
        'createdAtAttribute' => 'fprCreatedAt',
        // 'createdByAttribute' => 'fprCreatedBy',
        // 'updatedAtAttribute' => 'fprUpdatedAt',
        // 'updatedByAttribute' => 'fprUpdatedBy',
      ],
    ];
  }

  public function getUser()
  {
    return $this->hasOne(UserModel::class, ['usrID' => 'fprUserID']);
  }

  static function requestCode(
    $emailOrMobile,
    $userID = null,
    $gender = null,
    $firstName = null,
    $lastName = null
  ) {
    list ($normalizedInput, $inputType) = AuthHelper::checkLoginPhrase($emailOrMobile, false);

    // if ($inputType != $type)
    //   throw new UnauthorizedHttpException('input type is not correct');

    //flag expired
    //-----------------------------------
    $forgotPasswordRequestTableName = static::tableName();
    $userTableName = UserModel::tableName();
    $alertTableName = AlertModel::tableName();

    $qry =<<<SQLSTR
          UPDATE {$alertTableName} alr
      INNER JOIN {$forgotPasswordRequestTableName} fpr
              ON fpr.fprID = alr.alrForgotPasswordRequestID
      INNER JOIN {$userTableName} usr
              ON usr.usrID = fpr.fprUserID
             SET alrStatus = 'R'
           WHERE (
                 usrEmail = '{$normalizedInput}'
              OR usrMobile = '{$normalizedInput}'
                 )
             AND fprExpireAt <= NOW()
SQLSTR;
    static::getDb()->createCommand($qry)->execute();

    $qry =<<<SQLSTR
          UPDATE {$forgotPasswordRequestTableName} fpr
      INNER JOIN {$userTableName} usr
              ON usr.usrID = fpr.fprUserID
             SET fprStatus = 'E'
           WHERE (
                 usrEmail = '{$normalizedInput}'
              OR usrMobile = '{$normalizedInput}'
                 )
             AND fprExpireAt <= NOW()
SQLSTR;
    static::getDb()->createCommand($qry)->execute();

    //find current
    //-----------------------------------
    $models = ForgotPasswordRequestModel::find()
      ->addSelect([
        '*',
        'TIME_TO_SEC(TIMEDIFF(NOW(), COALESCE(fprSentAt, fprLastRequestAt))) AS ElapsedSeconds',
        'fprExpireAt <= NOW() AS IsExpired'
      ])
      ->joinWith('user', "INNER JOIN")
      ->where(['or',
        ['usrEmail' => $normalizedInput],
        ['usrMobile' => $normalizedInput]
      ])
      // ->andWhere(['fprCode' => $code])
      ->andWhere(['in', 'fprStatus', [
        enuForgotPasswordRequestStatus::NEW, enuForgotPasswordRequestStatus::SENT] ])
      ->limit(2)
      // ->asArray()
      ->all();

    if (empty($models) == false && count($models) > 1) {
      $qry =<<<SQLSTR
          UPDATE {$alertTableName} alr
      INNER JOIN {$forgotPasswordRequestTableName} fpr
              ON fpr.fprID = alr.alrForgotPasswordRequestID
      INNER JOIN {$userTableName} usr
              ON usr.usrID = fpr.fprUserID
             SET alrStatus = 'R'
           WHERE (
                 usrEmail = '{$normalizedInput}'
              OR usrMobile = '{$normalizedInput}'
                 )
             AND fprStatus IN ('N', 'S')
SQLSTR;
      static::getDb()->createCommand($qry)->execute();

      $qry =<<<SQLSTR
          UPDATE {$forgotPasswordRequestTableName} fpr
      INNER JOIN {$userTableName} usr
              ON usr.usrID = fpr.fprUserID
             SET fprStatus = 'E'
           WHERE (
                 usrEmail = '{$normalizedInput}'
              OR usrMobile = '{$normalizedInput}'
                 )
             AND fprStatus IN ('N', 'S')
SQLSTR;
      static::getDb()->createCommand($qry)->execute();
    }

    $settings = Yii::$app->params['settings'];
    $code = null;

    if (empty($models) == false) {
      $forgotPasswordRequestModel = $models[0];

      if (empty($userID)) {
        $userID    = $forgotPasswordRequestModel->user->usrID;
        $gender    = $forgotPasswordRequestModel->user->usrGender;
        $firstName = $forgotPasswordRequestModel->user->usrFirstName;
        $lastName  = $forgotPasswordRequestModel->user->usrLastName;
      }

      if ($forgotPasswordRequestModel->IsExpired) {
        $forgotPasswordRequestModel->fprStatus = enuForgotPasswordRequestStatus::EXPIRED;
        $forgotPasswordRequestModel->save();

        $forgotPasswordRequestModel = null;
      } else {
        $cfgPath = implode('.', [
          'AAA',
          'forgotPasswordRequest',
          $inputType == 'E' ? 'email' : 'mobile',
          'resend-ttl'
        ]);
        $resendTTL = ArrayHelper::getValue($settings, $cfgPath, 120);

        if ($forgotPasswordRequestModel->ElapsedSeconds < $resendTTL) {
          $seconds = $resendTTL - $forgotPasswordRequestModel->ElapsedSeconds;

          throw new UnauthorizedHttpException('the waiting time has not elapsed. ('
            . GeneralHelper::formatTimeFromSeconds($seconds) . ' remained)');
        }

        $code = $forgotPasswordRequestModel->fprCode;
      }
    }

    if (empty($userID)) {
      $userModel = UserModel::find()
        ->where(['usr' . ($inputType == 'E' ? 'Email' : 'Mobile') => $normalizedInput])
        ->andWhere("usrStatus != 'R'")
        ->one();

      if (!$userModel)
        throw new UnauthorizedHttpException('user not found');

      $userID    = $userModel->usrID;
      $gender    = $userModel->usrGender;
      $firstName = $userModel->usrFirstName;
      $lastName  = $userModel->usrLastName;
    }

    $codeIsNew = false;

    if (empty($code)) {
      $codeIsNew = true;

      if ($inputType == enuForgotPasswordRequestKeyType::EMAIL)
        $code = Yii::$app->security->generateRandomString() . '_' . time();
      else if ($inputType == enuForgotPasswordRequestKeyType::MOBILE)
        $code = strval(rand(123456, 987654));
      else
        throw new UnauthorizedHttpException("invalid input type {$inputType}");

      $cfgPath = implode('.', [
        'AAA',
        'forgotPasswordRequest',
        $inputType == 'E' ? 'email' : 'mobile',
        'expire-ttl'
      ]);
      $expireTTL = ArrayHelper::getValue($settings, $cfgPath, 20 * 60);

      $forgotPasswordRequestModel = new static();
      $forgotPasswordRequestModel->fprUserID        = $userID;
      $forgotPasswordRequestModel->fprRequestedBy   = $inputType;
      $forgotPasswordRequestModel->fprCode          = $code;
      $forgotPasswordRequestModel->fprLastRequestAt = new Expression('NOW()');
      $forgotPasswordRequestModel->fprExpireAt      = new Expression("DATE_ADD(NOW(), INTERVAL {$expireTTL} SECOND)");
      if ($forgotPasswordRequestModel->save() == false)
        throw new UnprocessableEntityHttpException("error in creating forgot password request\n" . implode("\n", $forgotPasswordRequestModel->getFirstErrors()));
    } else {
      $forgotPasswordRequestModel->fprLastRequestAt = new Expression('NOW()');
      if ($forgotPasswordRequestModel->save() == false)
        throw new UnprocessableEntityHttpException("error in updating forgot password request\n" . implode("\n", $forgotPasswordRequestModel->getFirstErrors()));

      $qry =<<<SQLSTR
          UPDATE {$alertTableName}
             SET alrStatus = 'R'
           WHERE alrForgotPasswordRequestID = '{$forgotPasswordRequestModel->fprID}'
SQLSTR;
      static::getDb()->createCommand($qry)->execute();
    }

    $alertModel = new AlertModel();
    $alertModel->alrUserID  = $userID;
    $alertModel->alrForgotPasswordRequestID = $forgotPasswordRequestModel->fprID;
    $alertModel->alrTarget  = $normalizedInput;

    $alrInfo = [
      'gender' => $gender,
      'firstName' => $firstName,
      'lastName' => $lastName,
      'code' => $code,
    ];

    if ($inputType == enuForgotPasswordRequestKeyType::EMAIL) {
      $alertModel->alrTypeKey = enuForgotPasswordRequestAlertType::REQUEST_BY_EMAIL;
      $alrInfo['email'] = $normalizedInput;
    } else {
      $alertModel->alrTypeKey = enuForgotPasswordRequestAlertType::REQUEST_BY_MOBILE;
      $alrInfo['mobile'] = $normalizedInput;
    }
    $alertModel->alrInfo = $alrInfo;

    if ($alertModel->save() == false)
      throw new UnprocessableEntityHttpException("could not save alert\n" . implode("\n", $alertModel->getFirstErrors()));

    return ($codeIsNew ? 'code sent' : 'code resent');
  }

  static function acceptCode($emailOrMobile, $code, $newPassword)
  {
    list ($normalizedInput, $inputType) = AuthHelper::checkLoginPhrase($emailOrMobile, false);

    //find current
    //------------------------------
    $models = ForgotPasswordRequestModel::find()
      ->addSelect([
        '*',
        'fprExpireAt <= NOW() AS IsExpired'
      ])
      ->joinWith('user', "INNER JOIN")
      ->where(['or',
        ['usrEmail' => $normalizedInput],
        ['usrMobile' => $normalizedInput]
      ])
      ->andWhere(['fprCode' => $code])
      ->andWhere(['in', 'fprStatus', [
        enuForgotPasswordRequestStatus::NEW, enuForgotPasswordRequestStatus::SENT, enuForgotPasswordRequestStatus::APPLIED] ])
      ->limit(2)
      // ->asArray()
      ->all();

    if (empty($models))
      throw new UnauthorizedHttpException('invalid ' . ($inputType == 'E' ? 'email' : 'mobile') . ' and/or code');

    if (count($models) > 1)
      throw new UnauthorizedHttpException('more than one request found');

    $forgotPasswordRequestModel = $models[0];

    // new ForgotPasswordRequestModel(); //$forgotPasswordRequestModelRaw);
    // $forgotPasswordRequestModelRaw = $rows[0];
    // ForgotPasswordRequestModel::populateRecord($forgotPasswordRequestModel, $forgotPasswordRequestModelRaw);

    //validate
    //------------------------------
    if ($forgotPasswordRequestModel->fprRequestedBy != $inputType) {
      $forgotPasswordRequestModel->fprStatus = enuForgotPasswordRequestStatus::EXPIRED;
      $forgotPasswordRequestModel->save();

      throw new UnauthorizedHttpException('incorrect key type');
    }

    if ($forgotPasswordRequestModel->fprStatus == enuForgotPasswordRequestStatus::APPLIED)
      throw new UnauthorizedHttpException('this code applied before');

    if ($forgotPasswordRequestModel->fprStatus != enuForgotPasswordRequestStatus::SENT)
      throw new UnauthorizedHttpException('code not sent to the client');

    if ($forgotPasswordRequestModel->IsExpired) {
      $forgotPasswordRequestModel->fprStatus = enuForgotPasswordRequestStatus::EXPIRED;
      $forgotPasswordRequestModel->save();

      throw new UnauthorizedHttpException('code expired');
    }

    //accept
    //------------------------------
    $transaction = static::getDb()->beginTransaction();
    try {
      //1: fpr
      $forgotPasswordRequestModel->fprStatus = enuForgotPasswordRequestStatus::APPLIED;
      $forgotPasswordRequestModel->fprApplyAt = new Expression('NOW()');
      if ($forgotPasswordRequestModel->save() == false)
        throw new UnprocessableEntityHttpException("could not save forgot password request\n" . implode("\n", $forgotPasswordRequestModel->getFirstErrors()));

      //2: user
      $forgotPasswordRequestModel->user->usrPassword = $newPassword;
      if ($forgotPasswordRequestModel->user->save() == false)
        throw new UnprocessableEntityHttpException("could not save new password\n" . implode("\n", $forgotPasswordRequestModel->user->getFirstErrors()));

      //3: send alert '[email|mobile]Approved'
      $alertModel = new AlertModel();
      $alertModel->alrUserID  = $forgotPasswordRequestModel->user->usrID;
      // $alertModel->alrForgotPasswordRequestID = null;

      $alrInfo = [
        'gender' => $forgotPasswordRequestModel->user->usrGender,
        'firstName' => $forgotPasswordRequestModel->user->usrFirstName,
        'lastName' => $forgotPasswordRequestModel->user->usrLastName,
      ];

      if ($forgotPasswordRequestModel->fprRequestedBy == enuForgotPasswordRequestKeyType::EMAIL) {
        $alertModel->alrTarget  = $forgotPasswordRequestModel->user->usrEmail;
        $alertModel->alrTypeKey = enuForgotPasswordRequestAlertType::CHANGED_BY_EMAIL;
        $alrInfo['email'] = $alertModel->alrTarget;
      } else {
        $alertModel->alrTarget  = $forgotPasswordRequestModel->user->usrMobile;
        $alertModel->alrTypeKey = enuForgotPasswordRequestAlertType::CHANGED_BY_MOBILE;
        $alrInfo['mobile'] = $alertModel->alrTarget;
      }
      $alertModel->alrInfo = $alrInfo;

      if ($alertModel->save() == false)
        throw new UnprocessableEntityHttpException("could not save alert\n" . implode("\n", $alertModel->getFirstErrors()));

      //
      $transaction->commit();

    } catch (\Exception $e) {
        $transaction->rollBack();
        throw $e;
    } catch (\Throwable $e) {
        $transaction->rollBack();
        throw $e;
    }

    return true;
  }

}
