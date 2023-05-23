<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use phpDocumentor\Reflection\DocBlock\Tag\AuthorTag;
use Yii;
use shopack\aaa\backend\classes\AAAActiveRecord;
use yii\db\Expression;
use shopack\base\common\helpers\ArrayHelper;
use shopack\base\backend\helpers\AuthHelper;
use yii\web\UnauthorizedHttpException;
use yii\web\UnprocessableEntityHttpException;
use shopack\aaa\backend\models\UserModel;
use shopack\aaa\backend\models\MessageModel;
use shopack\aaa\common\enums\enuMessageStatus;
use shopack\base\backend\helpers\GeneralHelper;
use shopack\aaa\common\enums\enuForgotPasswordRequestKeyType;
use shopack\aaa\common\enums\enuForgotPasswordRequestMessageType;
use shopack\aaa\common\enums\enuForgotPasswordRequestStatus;
use shopack\aaa\common\enums\enuUserStatus;

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
  //     ['fprStatus', 'default', 'value' => enuForgotPasswordRequestStatus::New],

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
    $fnGetConst = function($value) { return "'{$value}'"; };

    list ($normalizedInput, $inputType) = AuthHelper::checkLoginPhrase($emailOrMobile, false);

    // if ($inputType != $type)
    //   throw new UnauthorizedHttpException('input type is not correct');

    //flag expired
    //-----------------------------------
    $forgotPasswordRequestTableName = static::tableName();
    $userTableName = UserModel::tableName();
    $messageTableName = MessageModel::tableName();

    $qry =<<<SQLSTR
          UPDATE {$messageTableName} msg
      INNER JOIN {$forgotPasswordRequestTableName} fpr
              ON fpr.fprID = msg.msgForgotPasswordRequestID
      INNER JOIN {$userTableName} usr
              ON usr.usrID = fpr.fprUserID
             SET msgStatus = {$fnGetConst(enuMessageStatus::Removed)}
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
             SET fprStatus = {$fnGetConst(enuForgotPasswordRequestStatus::Expired)}
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
        enuForgotPasswordRequestStatus::New, enuForgotPasswordRequestStatus::Sent] ])
      ->limit(2)
      // ->asArray()
      ->all();

    if (empty($models) == false && count($models) > 1) {
      $qry =<<<SQLSTR
          UPDATE {$messageTableName} msg
      INNER JOIN {$forgotPasswordRequestTableName} fpr
              ON fpr.fprID = msg.msgForgotPasswordRequestID
      INNER JOIN {$userTableName} usr
              ON usr.usrID = fpr.fprUserID
             SET msgStatus = {$fnGetConst(enuMessageStatus::Error)}
           WHERE (
                 usrEmail = '{$normalizedInput}'
              OR usrMobile = '{$normalizedInput}'
                 )
             AND fprStatus IN ({$fnGetConst(enuForgotPasswordRequestStatus::New)}, {$fnGetConst(enuForgotPasswordRequestStatus::Sent)})
SQLSTR;
      static::getDb()->createCommand($qry)->execute();

      $qry =<<<SQLSTR
          UPDATE {$forgotPasswordRequestTableName} fpr
      INNER JOIN {$userTableName} usr
              ON usr.usrID = fpr.fprUserID
             SET fprStatus = {$fnGetConst(enuForgotPasswordRequestStatus::Expired)}
           WHERE (
                 usrEmail = '{$normalizedInput}'
              OR usrMobile = '{$normalizedInput}'
                 )
             AND fprStatus IN ({$fnGetConst(enuForgotPasswordRequestStatus::New)}, {$fnGetConst(enuForgotPasswordRequestStatus::Sent)})
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
        $forgotPasswordRequestModel->fprStatus = enuForgotPasswordRequestStatus::Expired;
        $forgotPasswordRequestModel->save();

        $forgotPasswordRequestModel = null;
      } else {
        $cfgPath = implode('.', [
          'AAA',
          'forgotPasswordRequest',
          $inputType == AuthHelper::PHRASETYPE_EMAIL ? 'email' : 'mobile',
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
        ->andWhere(['usr' . ($inputType == AuthHelper::PHRASETYPE_EMAIL ? 'Email' : 'Mobile') => $normalizedInput])
        ->andWhere(['!=', 'usrStatus', enuUserStatus::Removed])
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

      if ($inputType == enuForgotPasswordRequestKeyType::Email)
        $code = Yii::$app->security->generateRandomString() . '_' . time();
      else if ($inputType == enuForgotPasswordRequestKeyType::Mobile)
        $code = strval(rand(123456, 987654));
      else
        throw new UnauthorizedHttpException("invalid input type {$inputType}");

      $cfgPath = implode('.', [
        'AAA',
        'forgotPasswordRequest',
        $inputType == AuthHelper::PHRASETYPE_EMAIL ? 'email' : 'mobile',
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
          UPDATE {$messageTableName}
             SET msgStatus = {$fnGetConst(enuMessageStatus::Removed)}
           WHERE msgForgotPasswordRequestID = '{$forgotPasswordRequestModel->fprID}'
SQLSTR;
      static::getDb()->createCommand($qry)->execute();
    }

    $messageModel = new MessageModel();
    $messageModel->msgIssuer  = 'aaa:forgotPasswordRequest:request';
    $messageModel->msgUserID  = $userID;
    $messageModel->msgForgotPasswordRequestID = $forgotPasswordRequestModel->fprID;
    $messageModel->msgTarget  = $normalizedInput;

    $msgInfo = [
      'gender' => $gender,
      'firstName' => $firstName,
      'lastName' => $lastName,
      'code' => $code,
    ];

    if ($inputType == enuForgotPasswordRequestKeyType::Email) {
      $messageModel->msgTypeKey = enuForgotPasswordRequestMessageType::RequestByEmail;
      $msgInfo['email'] = $normalizedInput;
    } else {
      $messageModel->msgTypeKey = enuForgotPasswordRequestMessageType::RequestByMobile;
      $msgInfo['mobile'] = $normalizedInput;
    }
    $messageModel->msgInfo = $msgInfo;

    if ($messageModel->save() == false)
      throw new UnprocessableEntityHttpException("could not save message\n" . implode("\n", $messageModel->getFirstErrors()));

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
        enuForgotPasswordRequestStatus::New, enuForgotPasswordRequestStatus::Sent, enuForgotPasswordRequestStatus::Applied] ])
      ->limit(2)
      // ->asArray()
      ->all();

    if (empty($models))
      throw new UnauthorizedHttpException('invalid ' . ($inputType == AuthHelper::PHRASETYPE_EMAIL ? 'email' : 'mobile') . ' and/or code');

    if (count($models) > 1)
      throw new UnauthorizedHttpException('more than one request found');

    $forgotPasswordRequestModel = $models[0];

    // new ForgotPasswordRequestModel(); //$forgotPasswordRequestModelRaw);
    // $forgotPasswordRequestModelRaw = $rows[0];
    // ForgotPasswordRequestModel::populateRecord($forgotPasswordRequestModel, $forgotPasswordRequestModelRaw);

    //validate
    //------------------------------
    if ($forgotPasswordRequestModel->fprRequestedBy != $inputType) {
      $forgotPasswordRequestModel->fprStatus = enuForgotPasswordRequestStatus::Expired;
      $forgotPasswordRequestModel->save();

      throw new UnauthorizedHttpException('incorrect key type');
    }

    if ($forgotPasswordRequestModel->fprStatus == enuForgotPasswordRequestStatus::Applied)
      throw new UnauthorizedHttpException('this code applied before');

    if ($forgotPasswordRequestModel->fprStatus != enuForgotPasswordRequestStatus::Sent)
      throw new UnauthorizedHttpException('code not sent to the client');

    if ($forgotPasswordRequestModel->IsExpired) {
      $forgotPasswordRequestModel->fprStatus = enuForgotPasswordRequestStatus::Expired;
      $forgotPasswordRequestModel->save();

      throw new UnauthorizedHttpException('code expired');
    }

    //accept
    //------------------------------
    $transaction = static::getDb()->beginTransaction();
    try {
      //1: fpr
      $forgotPasswordRequestModel->fprStatus = enuForgotPasswordRequestStatus::Applied;
      $forgotPasswordRequestModel->fprApplyAt = new Expression('NOW()');
      if ($forgotPasswordRequestModel->save() == false)
        throw new UnprocessableEntityHttpException("could not save forgot password request\n" . implode("\n", $forgotPasswordRequestModel->getFirstErrors()));

      //2: user
      $forgotPasswordRequestModel->user->usrPassword = $newPassword;
      if ($forgotPasswordRequestModel->user->save() == false)
        throw new UnprocessableEntityHttpException("could not save new password\n" . implode("\n", $forgotPasswordRequestModel->user->getFirstErrors()));

      //3: send message '[email|mobile]Approved'
      $messageModel = new MessageModel();
      $messageModel->msgIssuer  = 'aaa:forgotPasswordRequest:accept';
      $messageModel->msgUserID  = $forgotPasswordRequestModel->user->usrID;
      // $messageModel->msgForgotPasswordRequestID = null;

      $msgInfo = [
        'gender' => $forgotPasswordRequestModel->user->usrGender,
        'firstName' => $forgotPasswordRequestModel->user->usrFirstName,
        'lastName' => $forgotPasswordRequestModel->user->usrLastName,
      ];

      if ($forgotPasswordRequestModel->fprRequestedBy == enuForgotPasswordRequestKeyType::Email) {
        $messageModel->msgTarget  = $forgotPasswordRequestModel->user->usrEmail;
        $messageModel->msgTypeKey = enuForgotPasswordRequestMessageType::ChangedByEmail;
        $msgInfo['email'] = $messageModel->msgTarget;
      } else {
        $messageModel->msgTarget  = $forgotPasswordRequestModel->user->usrMobile;
        $messageModel->msgTypeKey = enuForgotPasswordRequestMessageType::ChangedByMobile;
        $msgInfo['mobile'] = $messageModel->msgTarget;
      }
      $messageModel->msgInfo = $msgInfo;

      if ($messageModel->save() == false)
        throw new UnprocessableEntityHttpException("could not save message\n" . implode("\n", $messageModel->getFirstErrors()));

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
