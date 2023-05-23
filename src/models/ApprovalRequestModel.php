<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use Yii;
use yii\db\Expression;
use shopack\base\common\helpers\ArrayHelper;
use yii\web\UnauthorizedHttpException;
use yii\web\UnprocessableEntityHttpException;
use yii\web\HttpException;
use shopack\aaa\backend\classes\AAAActiveRecord;
use shopack\base\backend\helpers\AuthHelper;
use shopack\base\backend\helpers\GeneralHelper;
use shopack\aaa\backend\models\UserModel;
use shopack\aaa\backend\models\MessageModel;
use shopack\aaa\common\enums\enuMessageStatus;
use shopack\aaa\common\enums\enuApprovalRequestKeyType;
use shopack\aaa\common\enums\enuApprovalRequestMessageType;
use shopack\aaa\common\enums\enuApprovalRequestStatus;
use shopack\aaa\common\enums\enuUserStatus;

class ApprovalRequestModel extends AAAActiveRecord
{
  use \shopack\aaa\common\models\ApprovalRequestModelTrait;

  // const ERROR_THE_WAITING_TIME_HAS_NOT_ELAPSED = [
  //   'status' => 401,
  //   'message' => 'THE_WAITING_TIME_HAS_NOT_ELAPSED',
  // ];

  const ERROR_USER_NOT_FOUND = [
    'status' => 401,
    'message' => 'USER_NOT_FOUND',
  ];

  public static function sendHttpError($errorInfo, $errorParams = []) {
    if (empty($errorParams))
      $message = $errorInfo['message'];
    else
      $message = json_encode(array_merge([$errorInfo['message']], $errorParams));

    throw new HttpException(
      $errorInfo['status'],
      $message
    );
  }

  public $ElapsedSeconds;
  public $IsExpired;

  public static function tableName()
  {
    return '{{%AAA_ApprovalRequest}}';
  }

  // public function rules()
  // {
  //   return [
  //     ['aprID', 'integer'],
  //     ['aprUserID', 'integer'],

  //     ['aprKeyType', 'string', 'max' => 1],

  //     ['aprKey', 'string', 'max' => 128],
  //     ['aprCode', 'string', 'max' => 48],

  //     ['aprLastRequestAt', 'safe'],
  //     ['aprExpireAt', 'safe'],
  //     ['aprSentAt', 'safe'],
  //     ['aprApplyAt', 'safe'],

  //     ['aprStatus', 'string', 'max' => 1],
  //     ['aprStatus', 'default', 'value' => enuApprovalRequestStatus::New],

  //     ['aprCreatedAt', 'safe'],
  //     // ['aprCreatedBy', 'integer'],
  //     // ['aprUpdatedAt', 'safe'],
  //     // ['aprUpdatedBy', 'integer'],

  //     [[
  //       // 'aprUserID',
  //       'aprKeyType',
  //       'aprKey',
  //       'aprCode',
  //       'aprLastRequestAt',
  //       'aprExpireAt',
  //       'aprStatus',
  //     ], 'required'],

  //   ];
  // }

  public function behaviors()
  {
    return [
      [
        'class' => \shopack\base\common\behaviors\RowDatesAttributesBehavior::class,
        'createdAtAttribute' => 'aprCreatedAt',
        // 'createdByAttribute' => 'aprCreatedBy',
        // 'updatedAtAttribute' => 'aprUpdatedAt',
        // 'updatedByAttribute' => 'aprUpdatedBy',
      ],
    ];
  }

  public function getUser()
  {
    return $this->hasOne(UserModel::class, ['usrID' => 'aprUserID']);
  }

  /**
   * @return: [$result, $info]
   */
  static function requestCode(
    $emailOrMobile,
    $userID = null,
    $gender = null,
    $firstName = null,
    $lastName = null,
    $forLogin = false
  ) {
    $fnGetConst = function($value) { return "'{$value}'"; };

    list ($normalizedInput, $inputType) = AuthHelper::checkLoginPhrase($emailOrMobile, false);

    // if ($inputType != $type)
    //   throw new UnauthorizedHttpException('input type is not correct');

    //flag expired
    //-----------------------------------
    $approvalRequestTableName = static::tableName();
    $messageTableName = MessageModel::tableName();

    $qry =<<<SQLSTR
          UPDATE {$messageTableName} msg
      INNER JOIN {$approvalRequestTableName} apr
              ON apr.aprID = msg.msgApprovalRequestID
             SET msgStatus = {$fnGetConst(enuMessageStatus::Removed)}
           WHERE aprKey = '{$normalizedInput}'
             AND aprExpireAt <= NOW()
             AND msgStatus != {$fnGetConst(enuMessageStatus::Sent)}
SQLSTR;
    static::getDb()->createCommand($qry)->execute();

    $qry =<<<SQLSTR
          UPDATE {$approvalRequestTableName}
             SET aprStatus = {$fnGetConst(enuApprovalRequestStatus::Expired)}
           WHERE aprKey = '{$normalizedInput}'
             AND aprExpireAt <= NOW()
             AND aprStatus != {$fnGetConst(enuApprovalRequestStatus::Applied)}
SQLSTR;
    static::getDb()->createCommand($qry)->execute();

    //find current
    //-----------------------------------
    $models = ApprovalRequestModel::find()
      ->addSelect([
        '*',
        'TIME_TO_SEC(TIMEDIFF(NOW(), COALESCE(aprSentAt, aprLastRequestAt))) AS ElapsedSeconds',
        'aprExpireAt <= NOW() AS IsExpired'
      ])
      ->joinWith('user', "INNER JOIN")
      ->where(['aprKey' => $normalizedInput])
      // ->andWhere(['aprCode' => $code])
      ->andWhere(['in', 'aprStatus', [
        enuApprovalRequestStatus::New, enuApprovalRequestStatus::Sent] ])
      ->limit(2)
      // ->asArray()
      ->all();

    if (empty($models) == false && count($models) > 1) {
      $qry =<<<SQLSTR
          UPDATE {$messageTableName} msg
      INNER JOIN {$approvalRequestTableName} apr
              ON apr.aprID = msg.msgApprovalRequestID
             SET msgStatus = {$fnGetConst(enuMessageStatus::Removed)}
           WHERE aprKey = '{$normalizedInput}'
             AND aprStatus IN ({$fnGetConst(enuApprovalRequestStatus::New)}, {$fnGetConst(enuApprovalRequestStatus::Sent)})
SQLSTR;
      static::getDb()->createCommand($qry)->execute();

      $qry =<<<SQLSTR
          UPDATE {$approvalRequestTableName}
             SET aprStatus = {$fnGetConst(enuApprovalRequestStatus::Expired)}
           WHERE aprKey = '{$normalizedInput}'
             AND aprStatus IN ({$fnGetConst(enuApprovalRequestStatus::New)}, {$fnGetConst(enuApprovalRequestStatus::Sent)})
SQLSTR;
      static::getDb()->createCommand($qry)->execute();
    }

    $settings = Yii::$app->params['settings'];
    $cfgPath = implode('.', [
      'AAA',
      'approvalRequest',
      $inputType == AuthHelper::PHRASETYPE_EMAIL ? 'email' : 'mobile',
      'resend-ttl'
    ]);
    $resendTTL = ArrayHelper::getValue($settings, $cfgPath, 120);

    $code = null;

    if (empty($models) == false) {
      $approvalRequestModel = $models[0];

      if (empty($userID) && $approvalRequestModel->aprUserID != null) {
        $userID    = $approvalRequestModel->user->usrID;
        $gender    = $approvalRequestModel->user->usrGender;
        $firstName = $approvalRequestModel->user->usrFirstName;
        $lastName  = $approvalRequestModel->user->usrLastName;
      }

      if ($approvalRequestModel->IsExpired) {
        $approvalRequestModel->aprStatus = enuApprovalRequestStatus::Expired;
        $approvalRequestModel->save();

        $approvalRequestModel = null;
      } else {
        if ($approvalRequestModel->ElapsedSeconds < $resendTTL) {
          $seconds = $resendTTL - $approvalRequestModel->ElapsedSeconds;

          return [
            'message' => 'THE_WAITING_TIME_HAS_NOT_ELAPSED',
            'ttl' => $seconds,
            'remained' => GeneralHelper::formatTimeFromSeconds($seconds),
          ];

          // self::sendHttpError(self::ERROR_THE_WAITING_TIME_HAS_NOT_ELAPSED, [
          //   'ttl' => $seconds,
          //   'remained' => GeneralHelper::formatTimeFromSeconds($seconds)]);
        }

        $code = $approvalRequestModel->aprCode;
      }
    }

    if (empty($userID)) {
      $userModel = UserModel::find()
        ->andWhere(['usr' . ($inputType == AuthHelper::PHRASETYPE_EMAIL ? 'Email' : 'Mobile') => $normalizedInput])
        ->andWhere(['!=', 'usrStatus', enuUserStatus::Removed])
        ->one();

      if (!$userModel && $forLogin == false)
        self::sendHttpError(self::ERROR_USER_NOT_FOUND);

      if ($userModel) {
        $userID    = $userModel->usrID;
        $gender    = $userModel->usrGender;
        $firstName = $userModel->usrFirstName;
        $lastName  = $userModel->usrLastName;
      }
    }

    $codeIsNew = false;

    if (empty($code)) {
      $codeIsNew = true;

      if ($inputType == enuApprovalRequestKeyType::Email)
        $code = Yii::$app->security->generateRandomString() . '_' . time();
      else if ($inputType == enuApprovalRequestKeyType::Mobile)
        $code = strval(rand(123456, 987654));
      else
        throw new UnauthorizedHttpException("invalid input type {$inputType}");

      $cfgPath = implode('.', [
        'AAA',
        'approvalRequest',
        $inputType == AuthHelper::PHRASETYPE_EMAIL ? 'email' : 'mobile',
        'expire-ttl'
      ]);
      $expireTTL = ArrayHelper::getValue($settings, $cfgPath, 20 * 60);

      $approvalRequestModel = new static();
      $approvalRequestModel->aprUserID        = $userID;
      $approvalRequestModel->aprKeyType       = $inputType;
      $approvalRequestModel->aprKey           = $normalizedInput;
      $approvalRequestModel->aprCode          = $code;
      $approvalRequestModel->aprLastRequestAt = new Expression('NOW()');
      $approvalRequestModel->aprExpireAt      = new Expression("DATE_ADD(NOW(), INTERVAL {$expireTTL} SECOND)");
      if ($approvalRequestModel->save() == false)
        throw new UnprocessableEntityHttpException("error in creating approval request\n" . implode("\n", $approvalRequestModel->getFirstErrors()));
    } else {
      $approvalRequestModel->aprLastRequestAt = new Expression('NOW()');
      if ($approvalRequestModel->save() == false)
        throw new UnprocessableEntityHttpException("error in updating approval request\n" . implode("\n", $approvalRequestModel->getFirstErrors()));

      $qry =<<<SQLSTR
          UPDATE {$messageTableName}
             SET msgStatus = {$fnGetConst(enuMessageStatus::Removed)}
           WHERE msgApprovalRequestID = '{$approvalRequestModel->aprID}'
SQLSTR;
      static::getDb()->createCommand($qry)->execute();
    }

    $messageModel = new MessageModel();
    $messageModel->msgIssuer  = 'aaa:approvalRequest:request';
    $messageModel->msgUserID  = $userID;
    $messageModel->msgApprovalRequestID = $approvalRequestModel->aprID;
    $messageModel->msgTarget  = $normalizedInput;

    $msgInfo = [
      'gender' => $gender,
      'firstName' => $firstName,
      'lastName' => $lastName,
      'code' => $code,
    ];

    if ($inputType == enuApprovalRequestKeyType::Email) {
      $messageModel->msgTypeKey = ($forLogin
        ? enuApprovalRequestMessageType::EmailApprovalForLogin
        : enuApprovalRequestMessageType::EmailApproval);
      $msgInfo['email'] = $normalizedInput;
    } else {
      $messageModel->msgTypeKey = ($forLogin
        ? enuApprovalRequestMessageType::MobileApprovalForLogin
        : enuApprovalRequestMessageType::MobileApproval);
      $msgInfo['mobile'] = $normalizedInput;
    }

    $messageModel->msgInfo = $msgInfo;

    if ($messageModel->save() == false)
      throw new UnprocessableEntityHttpException("could not save message\n" . implode("\n", $messageModel->getFirstErrors()));

    return [
      'message' => $codeIsNew ? 'CODE_SENT' : 'CODE_RESENT',
      'ttl' => $resendTTL,
      'remained' => GeneralHelper::formatTimeFromSeconds($resendTTL),
    ];
  }

  static function getTimerInfo($emailOrMobile)
  {
    list ($normalizedInput, $inputType) = AuthHelper::checkLoginPhrase($emailOrMobile, false);

    //find current
    //------------------------------
    $models = ApprovalRequestModel::find()
      ->addSelect([
        '*',
        'TIME_TO_SEC(TIMEDIFF(NOW(), COALESCE(aprSentAt, aprLastRequestAt))) AS ElapsedSeconds',
        'aprExpireAt <= NOW() AS IsExpired'
      ])
      ->joinWith('user', "INNER JOIN")
      ->where(['aprKey' => $normalizedInput])
      ->andWhere(['aprKeyType' => $inputType])
      ->andWhere(['in', 'aprStatus', [enuApprovalRequestStatus::New, enuApprovalRequestStatus::Sent]])
      ->limit(2)
      // ->asArray()
      ->all();

    if (empty($models))
      throw new UnauthorizedHttpException('invalid ' . ($inputType == AuthHelper::PHRASETYPE_EMAIL ? 'email' : 'mobile'));

    if (count($models) > 1)
      throw new UnauthorizedHttpException('more than one request found');

    $approvalRequestModel = $models[0];

    $seconds = 0;

    if ($approvalRequestModel->IsExpired) {
      // $approvalRequestModel->aprStatus = enuApprovalRequestStatus::Expired;
      // $approvalRequestModel->save();

      // throw new UnauthorizedHttpException('code expired');
    } else {
      $settings = Yii::$app->params['settings'];
      $cfgPath = implode('.', [
        'AAA',
        'approvalRequest',
        $inputType == AuthHelper::PHRASETYPE_EMAIL ? 'email' : 'mobile',
        'resend-ttl'
      ]);
      $resendTTL = ArrayHelper::getValue($settings, $cfgPath, 120);

      if ($approvalRequestModel->ElapsedSeconds < $resendTTL)
        $seconds = $resendTTL - $approvalRequestModel->ElapsedSeconds;
    }

    return [
      'ttl' => $seconds,
      'remained' => GeneralHelper::formatTimeFromSeconds($seconds),
    ];
  }

  static function acceptCode($emailOrMobile, $code)
  {
    list ($normalizedInput, $inputType) = AuthHelper::checkLoginPhrase($emailOrMobile, false);

    $messageTableName = MessageModel::tableName();

    //find current
    //------------------------------
    $models = ApprovalRequestModel::find()
      ->addSelect([
        '*',
        'aprExpireAt <= NOW() AS IsExpired'
      ])
      ->joinWith('user', "INNER JOIN")
      ->where(['aprKey' => $normalizedInput])
      ->andWhere(['aprCode' => $code])
      ->andWhere(['in', 'aprStatus', [
        enuApprovalRequestStatus::New, enuApprovalRequestStatus::Sent, enuApprovalRequestStatus::Applied] ])
      ->limit(2)
      // ->asArray()
      ->all();

    if (empty($models))
      throw new UnauthorizedHttpException('invalid ' . ($inputType == AuthHelper::PHRASETYPE_EMAIL ? 'email' : 'mobile') . ' and/or code');

    if (count($models) > 1)
      throw new UnauthorizedHttpException('more than one request found');

    $approvalRequestModel = $models[0];

    // new ApprovalRequestModel(); //$approvalRequestModelRaw);
    // $approvalRequestModelRaw = $rows[0];
    // ApprovalRequestModel::populateRecord($approvalRequestModel, $approvalRequestModelRaw);

    //validate
    //------------------------------
    if ($approvalRequestModel->aprKeyType != $inputType) {
      $approvalRequestModel->aprStatus = enuApprovalRequestStatus::Expired;
      $approvalRequestModel->save();

      throw new UnauthorizedHttpException('incorrect key type');
    }

    if ($approvalRequestModel->aprStatus == enuApprovalRequestStatus::Applied)
      throw new UnauthorizedHttpException('this code applied before');

    if ($approvalRequestModel->aprStatus != enuApprovalRequestStatus::Sent)
      throw new UnauthorizedHttpException('code not sent to the client');

    if ($approvalRequestModel->IsExpired) {
      $approvalRequestModel->aprStatus = enuApprovalRequestStatus::Expired;
      $approvalRequestModel->save();

      throw new UnauthorizedHttpException('code expired');
    }

    //accept
    //------------------------------
    $transaction = static::getDb()->beginTransaction();
    try {
      //1: user
      $sendMessage = null;
      $userModel = $approvalRequestModel->user;
      if ($userModel == null) {
        $userModel = new UserModel();
        $sendMessage = false;
      }

      $userModel->bypassRequestApprovalCode = true;

      if ($approvalRequestModel->aprKeyType == enuApprovalRequestKeyType::Email) {
        $userModel->usrEmailApprovedAt = new Expression('NOW()');
        if (empty($userModel->usrEmail)
            || ($userModel->usrEmail != $approvalRequestModel->aprKey)
        ) {
          $userModel->usrEmail = $approvalRequestModel->aprKey;
          if ($sendMessage === null)
            $sendMessage = true;
        }
      } else if ($approvalRequestModel->aprKeyType == enuApprovalRequestKeyType::Mobile) {
        $userModel->usrMobileApprovedAt = new Expression('NOW()');
        if (empty($userModel->usrMobile)
            || ($userModel->usrMobile != $approvalRequestModel->aprKey)
        ) {
          $userModel->usrMobile = $approvalRequestModel->aprKey;
          if ($sendMessage === null)
            $sendMessage = true;
        }
      }

      if ($userModel->save() == false)
        throw new UnprocessableEntityHttpException("could not save user\n" . implode("\n", $userModel->getFirstErrors()));

      //2: apr
      if ($approvalRequestModel->aprUserID == null)
        $approvalRequestModel->aprUserID = $userModel->usrID;
      $approvalRequestModel->aprStatus = enuApprovalRequestStatus::Applied;
      $approvalRequestModel->aprApplyAt = new Expression('NOW()');
      if ($approvalRequestModel->save() == false)
        throw new UnprocessableEntityHttpException("could not save approval request\n" . implode("\n", $approvalRequestModel->getFirstErrors()));

      //3: old message
      $qry =<<<SQLSTR
          UPDATE {$messageTableName}
             SET msgUserID = :UserID
           WHERE msgApprovalRequestID = '{$approvalRequestModel->aprID}'
             AND msgUserID IS NULL
SQLSTR;
      static::getDb()->createCommand($qry, [
        ':UserID' => $userModel->usrID,
      ])->execute();

      //4: send message '[email|mobile]Approved'
      if ($sendMessage === true) {
        $messageModel = new MessageModel();
        $messageModel->msgIssuer  = 'aaa:approvalRequest:accept';
        $messageModel->msgUserID  = $userModel->usrID;
        // $messageModel->msgApprovalRequestID = null;
        $messageModel->msgTarget  = $approvalRequestModel->aprKey;

        $msgInfo = [
          'gender' => $userModel->usrGender,
          'firstName' => $userModel->usrFirstName,
          'lastName' => $userModel->usrLastName,
        ];

        if ($approvalRequestModel->aprKeyType == enuApprovalRequestKeyType::Email) {
          $messageModel->msgTypeKey = enuApprovalRequestMessageType::EmailApproved;
          $msgInfo['email'] = $approvalRequestModel->aprKey;
        } else {
          $messageModel->msgTypeKey = enuApprovalRequestMessageType::MobileApproved;
          $msgInfo['mobile'] = $approvalRequestModel->aprKey;
        }
        $messageModel->msgInfo = $msgInfo;

        if ($messageModel->save() == false)
          throw new UnprocessableEntityHttpException("could not save message\n" . implode("\n", $messageModel->getFirstErrors()));
      }

      //
      $transaction->commit();

      return $userModel;

    } catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    } catch (\Throwable $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

}
