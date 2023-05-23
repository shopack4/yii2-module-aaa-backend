<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\components;

use Yii;
use yii\base\Component;
use yii\db\Expression;
use yii\web\NotFoundHttpException;
use shopack\aaa\common\enums\enuGender;
use shopack\aaa\common\enums\enuUserStatus;
use shopack\aaa\common\enums\enuGatewayStatus;
use shopack\aaa\common\enums\enuMessageTemplateMedia;
use shopack\aaa\common\enums\enuMessageStatus;
use shopack\aaa\common\enums\enuMessageResultStatus;
use shopack\aaa\common\enums\enuApprovalRequestStatus;
use shopack\aaa\common\enums\enuForgotPasswordRequestStatus;
use shopack\aaa\backend\models\UserModel;
use shopack\aaa\backend\models\GatewayModel;
use shopack\aaa\backend\models\MessageModel;
use shopack\aaa\backend\classes\SmsSendResult;

class MessageManager extends Component
{
	private $defaultSmsGatewayModel = null;

	public function getDefaultSmsGateway()
	{
		if ($this->defaultSmsGatewayModel == null) {
      $this->defaultSmsGatewayModel = GatewayModel::find()
        ->andWhere(['gtwPluginType' => 'sms'])
        ->andWhere(['gtwStatus' => enuGatewayStatus::Active])
        ->one();

      if ($this->defaultSmsGatewayModel == null)
        throw new NotFoundHttpException('sms gateway not found');
		}

		return $this->defaultSmsGatewayModel;
	}

	/**
	 * return: [status(bool), result]
	 * status = true
	 * 		result =
	 */
	public function sendSms($message, $to, $from = null) : SmsSendResult
	{
		return $this->defaultSmsGateway->gatewayClass->send($message, $to, $from);
	}

	public function processQueue($maxItemCount = 100, $messageID = 0)
  {
    $fnGetConst = function($value) { return "'{$value}'"; };

    try {
      $instanceID = Yii::$app->getInstanceID();
      // echo "*** instance id: {$instanceID}\n";

      $lastTryInterval = (YII_ENV_DEV ? 1 : 10);

/*
      //unlock old
      $qry = <<<SQL
      UPDATE tbl_AAA_Message
         SET msgLockedBy = NULL
           , msgLockedAt = NULL
       WHERE msgLockedBy = '{$instanceID}'
         AND (msgStatus = {$fnGetConst(enuMessageStatus::New)}
          OR (msgStatus = {$fnGetConst(enuMessageStatus::Error)}
         AND msgLastTryAt < DATE_SUB(NOW(), INTERVAL {$lastTryInterval} MINUTE)
             )
             )
SQL;

      echo "unlock query:\n{$qry}\n\n";

      $rowsCount = Yii::$app->db->createCommand($qry)->execute();
      echo "unlocked count: {$rowsCount}\n\n";
/**/

      //lock
      // INNER JOIN tbl_AAA_MessageTemplate
      //         ON tbl_AAA_MessageTemplate.mstKey = tbl_AAA_Message.msgTypeKey
      //  AND tbl_AAA_MessageTemplate.mstLanguage = tbl_AAA_Message.msgLanguage
			if (empty($messageID)) {
	      $qry = <<<SQL
      UPDATE tbl_AAA_Message
         SET msgLockedAt = NOW()
           , msgLockedBy = '{$instanceID}'
       WHERE EXISTS(
      SELECT mstID
        FROM tbl_AAA_MessageTemplate
       WHERE tbl_AAA_MessageTemplate.mstKey = tbl_AAA_Message.msgTypeKey
             )
         AND msgInfo != '__UNKNOWN__'
         AND (msgLockedAt IS NULL
          OR msgLockedAt < DATE_SUB(NOW(), INTERVAL 1 HOUR)
          OR msgLockedBy = '{$instanceID}'
             )
         AND (msgStatus = {$fnGetConst(enuMessageStatus::New)}
          OR (msgStatus = {$fnGetConst(enuMessageStatus::Error)}
         AND msgLastTryAt < DATE_SUB(NOW(), INTERVAL {$lastTryInterval} MINUTE)
             )
             )
    ORDER BY msgCreatedAt ASC
       LIMIT {$maxItemCount}
SQL;

      	// echo "lock query:\n{$qry}\n\n";

	      $rowsCount = Yii::$app->db->createCommand($qry)->execute();
      	// echo "locked count: {$rowsCount}\n";
			}

      //fetch
      //  AND tbl_AAA_MessageTemplate.mstLanguage = tbl_AAA_Message.msgLanguage
//       $qry = <<<SQL
//       SELECT *
//         FROM tbl_AAA_Message
//   INNER JOIN tbl_AAA_MessageTemplate
//           ON tbl_AAA_MessageTemplate.mstKey = tbl_AAA_Message.msgTypeKey
//        WHERE msgLockedBy = '{$instanceID}'
//          AND (msgStatus = {$fnGetConst(enuMessageStatus::New)}
//           OR (msgStatus = {$fnGetConst(enuMessageStatus::Error)}
//          AND msgLastTryAt < DATE_SUB(NOW(), INTERVAL {$lastTryInterval} MINUTE)
//              )
//              )
//     ORDER BY msgCreatedAt ASC
// SQL;

      // echo "fetch query:\n{$qry}\n\n";
// $messagesModels = Yii::$app->db->createCommand($qry)->queryAll();

			$query = MessageModel::find()
        ->innerJoinWith('messageTemplate')
        ->andWhere(['msgLockedBy' => $instanceID])
        ->andWhere(['OR',
          ['msgStatus' => enuMessageStatus::New],
          ['AND',
            ['msgStatus' => enuMessageStatus::Error],
            ['<', 'msgLastTryAt', new Expression("DATE_SUB(NOW(), INTERVAL {$lastTryInterval} MINUTE)")]
          ]
        ])
        ->orderBy('msgCreatedAt');

			if ($messageID > 0) {
				$query->andWhere(['msgID' => $messageID]);
			} else {
				// $query->andWhere(['OR',
				// 	['msgLockedAt IS NULL'],
				// 	['<', 'msgLockedAt', new Expression('DATE_SUB(NOW(), INTERVAL 1 HOUR)')],
				// 	['msgLockedBy' => $instanceID],
				// ]);
			}

			$messagesModels = $query->all();

      // echo "fetched count: " . count($messagesModels) . "\n";

      if (empty($messagesModels)) {
        // echo "nothing to do\n";
        return; // ExitCode::OK;
      }

      $expNow = new Expression('NOW()');

      foreach ($messagesModels as $messageModel) {
        if (empty($messageModel->messageTemplate->mstParamsPrefix) == false
          || empty($messageModel->messageTemplate->mstParamsSuffix) == false)
        {
          $replacements = [];
          foreach ($messageModel->msgInfo as $k => $v) {
            $replacements[
              ($messageModel->messageTemplate->mstParamsPrefix ?? '')
              . $k
              . ($messageModel->messageTemplate->mstParamsSuffix ?? '')
            ] = $v;
          }
        } else
          $replacements = $messageModel->msgInfo;

        $title = strtr($messageModel->messageTemplate->mstTitle ?? '', $replacements);
        $body = strtr($messageModel->messageTemplate->mstBody ?? '', $replacements);

        $errorCount = 0;
        $now = date('U');

        $msgResult = $messageModel->msgResult;
        if ($msgResult == null)
          $msgResult = [];

				if (empty($messageID))
					echo "processing message ({$messageModel->msgID}):\n";

        //-- email -----
        try {
          $key = enuMessageTemplateMedia::Email;

          if (in_array($messageModel->messageTemplate->mstMedia, [$key, 'A'])
              && (($msgResult[$key]['status'] ?? enuMessageResultStatus::New) != enuMessageResultStatus::Sent)
          ) {
            if (empty($messageID))
							echo "    Send Email to " . $messageModel->msgTarget . ": ";

            $refID = $this->SendEmailForItem($messageModel, $title, $body);

            if (empty($messageID))
							echo "OK. ref: " . $refID . "\n";

            $msgResult[$key] = [
              'status' => enuMessageResultStatus::Sent,
              'ref-id' => $refID,
              'sent-at' => $now,
            ];
          }
        } catch(\Exception $exp) {
          if (empty($messageID))
						echo "Error. " . $exp->getMessage() . "\n";

          ++$errorCount;

          $msgResult[$key] = [
            'status' => enuMessageResultStatus::Error,
            // 'at' => $expNow,
          ];
        }

        //-- sms -----
        try {
          $key = enuMessageTemplateMedia::Sms;

          if (in_array($messageModel->messageTemplate->mstMedia, [$key, 'A'])
              && (($msgResult[$key]['status'] ?? enuMessageResultStatus::New) != enuMessageResultStatus::Sent)
          ) {
            if (empty($messageID))
							echo "    Send Sms to " . $messageModel->msgTarget . ": ";

            $refID = $this->SendSmsForItem($messageModel, $title, $body);

            if (empty($messageID))
							echo "OK. ref: " . $refID . "\n";

            $msgResult[$key] = [
              'status' => enuMessageResultStatus::Sent,
              'ref-id' => $refID,
              'sent-at' => $now,
            ];
          }
        } catch(\Exception $exp) {
          if (empty($messageID))
						echo "Error. " . $exp->getMessage() . "\n";

          ++$errorCount;

          $msgResult[$key] = [
            'status' => enuMessageResultStatus::Error,
            // 'at' => $expNow,
          ];
        }

        //-- push -----

        //--
        $messageModel->msgLockedAt  = null;
        $messageModel->msgLockedBy  = null;
        $messageModel->msgLastTryAt = $expNow;
        $messageModel->msgSentAt    = ($errorCount == 0 ? $expNow : null);
        $messageModel->msgResult    = empty($msgResult) ? null : $msgResult;
        $messageModel->msgStatus    = ($errorCount == 0 ? enuMessageStatus::Sent : enuMessageStatus::Error);
        $messageModel->save();

        if ($messageModel->msgStatus == enuMessageStatus::Sent) {
          $qry = '';

          if (empty($messageModel->msgApprovalRequestID) == false) {
            $qry = <<<SQL
       UPDATE tbl_AAA_ApprovalRequest
          SET aprStatus = {$fnGetConst(enuApprovalRequestStatus::Sent)}
            , aprSentAt = NOW()
        WHERE aprID = {$messageModel->msgApprovalRequestID}
SQL;
          } else if (empty($messageModel->msgForgotPasswordRequestID) == false) {
            $qry = <<<SQL
     UPDATE tbl_AAA_ForgotPasswordRequest
        SET fprStatus = {$fnGetConst(enuForgotPasswordRequestStatus::Sent)}
          , fprSentAt = NOW()
      WHERE fprID = {$messageModel->msgForgotPasswordRequestID}
SQL;
          }

          if (empty($qry) == false) {
            $rowsCount = Yii::$app->db->createCommand($qry)->execute();
          }
        }
      }

		} catch(\Exception $e) {
      if (empty($messageID))
				echo $e->getMessage();
			Yii::error($e, __METHOD__);
		}
	}

	  /**
   * return: refID : string
   */
  private function SendEmailForItem($messageModel, $title, $body) {
    if (empty(Yii::$app->params['senderEmail']))
      throw new \Exception("error in send email: senderEmail not set in config file");

    $email = Yii::$app->mailer
			->compose(
				//['html' => 'aaa-html', 'text' => 'aaa-text'],
				//['user' => $user]
			)
			->setFrom(Yii::$app->params['senderEmail'])
			->setTo($messageModel->msgTarget)
			->setSubject($title)
			->setTextBody($body)
			->setHtmlBody($body);

    $result = $email->send();

    if ($result)
      return 'ok';

    throw new \Exception("error in send email");
  }

  /**
   * return: refID : string
   */
  private function SendSmsForItem($messageModel, $title, $body) {
    // $msg_mstCode = $row['msg_mstCode'];
    // $mstLanguage = $row["mstLanguage"];

    $result = $this->sendSms(
      $body,
      $messageModel->msgTarget
    );

    if ($result->status)
      return $result->refID;

    throw new \Exception("error in send sms: " . $result->message . " (" . $result->refID . ")");
  }

  public function sendBirthdayGreetings()
  {
    $query = UserModel::find(false)
      ->addSelect(MessageModel::selectableColumns())
      ->leftJoin(
        [
          'msg' => MessageModel::find()
            ->andWhere(['msgTypeKey' => 'happyBirthday'])
            ->andWhere(new Expression('DATE(msgCreatedAt) = CURDATE()'))
        ],
        'msg.msgUserID = usrID'
      )
      ->andWhere(['IS', 'usrBirthDate', new Expression('NOT NULL')])
      ->andWhere(['IS', 'usrMobile', new Expression('NOT NULL')])
      ->andWhere(['IS', 'usrMobileApprovedAt', new Expression('NOT NULL')])
      ->andWhere(['!=', 'usrStatus', enuUserStatus::Removed])
      ->andWhere(new Expression('MONTH(usrBirthDate) = MONTH(NOW())'))
      ->andWhere(new Expression('DAY(usrBirthDate) = DAY(NOW())'))
      ->andWhere(['IS', 'msgID', new Expression('NULL')])
    ;

    $userModels = $query
      ->indexBy(function($row) {
        return implode('.', [
          $row['usrID'] ?? 'NULL',
          $row['msgID'] ?? 'NULL'
        ]);
      })
      ->asArray()
      ->all()
    ;

    if (empty($userModels))
      return 0;

    $rowsCount = 0;

    foreach ($userModels as $userModel) {
      $memberFullName = [];
      if ((empty($userModel['usrGender']) == false)
          && ($userModel['usrGender'] != enuGender::NotSet))
        $memberFullName[] = enuGender::getAbrLabel($userModel['usrGender']);
      if (empty($userModel['usrFirstName']) == false)
        $memberFullName[] = $userModel['usrFirstName'];
      if (empty($userModel['usrLastName']) == false)
        $memberFullName[] = $userModel['usrLastName'];
      $memberFullName = implode(' ', $memberFullName);

      //
      $messageModel = new MessageModel;

      $messageModel->msgIssuer  = 'aaa:sendBirthdayGreetings';
      $messageModel->msgUserID  = $userModel['usrID'];
      $messageModel->msgTypeKey = 'happyBirthday';
      $messageModel->msgTarget  = $userModel['usrMobile'];
      $messageModel->msgInfo    = [
        'mobile'    => $userModel['usrMobile'],
        'gender'    => $userModel['usrGender'],
        'firstName' => $userModel['usrFirstName'],
        'lastName'  => $userModel['usrLastName'],
        'member'    => $memberFullName,
      ];

      if ($messageModel->save())
        ++$rowsCount;
    }

    return $rowsCount;

  }

}
