<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\components;

use Yii;
use yii\base\Component;
use yii\db\Expression;
use yii\web\NotFoundHttpException;
use shopack\aaa\common\enums\enuGatewayStatus;
use shopack\aaa\common\enums\enuAlertStatus;
use shopack\aaa\common\enums\enuApprovalRequestStatus;
use shopack\aaa\common\enums\enuForgotPasswordRequestStatus;
use shopack\aaa\backend\models\GatewayModel;
use shopack\aaa\backend\models\AlertModel;
use shopack\aaa\backend\classes\SmsSendResult;

class AlertManager extends Component
{
	private $defaultSmsGatewayModel = null;

	public function getDefaultSmsGateway()
	{
		if ($this->defaultSmsGatewayModel == null)
		{
      $this->defaultSmsGatewayModel = GatewayModel::find()
        ->andWhere(['gtwPluginType' => 'sms'])
        ->andWhere(['<>', 'gtwStatus', enuGatewayStatus::REMOVED])
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

	public function processQueue($maxItemCount = 100, $alertID = 0)
  {
    $fnGetConst = function($value) { return "'{$value}'"; };

    try {
      $instanceID = Yii::$app->getInstanceID();
      // echo "*** instance id: {$instanceID}\n";

      $lastTryInterval = (YII_ENV_DEV ? 1 : 10);

/*
      //unlock old
      $qry = <<<SQL
      UPDATE tbl_AAA_Alert
         SET alrLockedBy = NULL
           , alrLockedAt = NULL
       WHERE alrLockedBy = '{$instanceID}'
         AND (alrStatus = {$fnGetConst(enuAlertStatus::NEW)}
          OR (alrStatus = {$fnGetConst(enuAlertStatus::ERROR)}
         AND alrLastTryAt < DATE_SUB(NOW(), INTERVAL {$lastTryInterval} MINUTE)
             )
             )
SQL;

      echo "unlock query:\n{$qry}\n\n";

      $rowsCount = Yii::$app->db->createCommand($qry)->execute();
      echo "unlocked count: {$rowsCount}\n\n";
/**/

      //lock
      // INNER JOIN tbl_AAA_AlertTemplate
      //         ON tbl_AAA_AlertTemplate.altKey = tbl_AAA_Alert.alrTypeKey
      //  AND tbl_AAA_AlertTemplate.altLanguage = tbl_AAA_Alert.alrLanguage
			if (empty($alertID)) {
	      $qry = <<<SQL
      UPDATE tbl_AAA_Alert
         SET alrLockedAt = NOW()
           , alrLockedBy = '{$instanceID}'
       WHERE EXISTS(
      SELECT altID
        FROM tbl_AAA_AlertTemplate
       WHERE tbl_AAA_AlertTemplate.altKey = tbl_AAA_Alert.alrTypeKey
             )
         AND alrInfo != '__UNKNOWN__'
         AND (alrLockedAt IS NULL
          OR alrLockedAt < DATE_SUB(NOW(), INTERVAL 1 HOUR)
          OR alrLockedBy = '{$instanceID}'
             )
         AND (alrStatus = {$fnGetConst(enuAlertStatus::NEW)}
          OR (alrStatus = {$fnGetConst(enuAlertStatus::ERROR)}
         AND alrLastTryAt < DATE_SUB(NOW(), INTERVAL {$lastTryInterval} MINUTE)
             )
             )
    ORDER BY alrCreatedAt ASC
       LIMIT {$maxItemCount}
SQL;

      	// echo "lock query:\n{$qry}\n\n";

	      $rowsCount = Yii::$app->db->createCommand($qry)->execute();
      	// echo "locked count: {$rowsCount}\n";
			}

      //fetch
      //  AND tbl_AAA_AlertTemplate.altLanguage = tbl_AAA_Alert.alrLanguage
//       $qry = <<<SQL
//       SELECT *
//         FROM tbl_AAA_Alert
//   INNER JOIN tbl_AAA_AlertTemplate
//           ON tbl_AAA_AlertTemplate.altKey = tbl_AAA_Alert.alrTypeKey
//        WHERE alrLockedBy = '{$instanceID}'
//          AND (alrStatus = {$fnGetConst(enuAlertStatus::NEW)}
//           OR (alrStatus = {$fnGetConst(enuAlertStatus::ERROR)}
//          AND alrLastTryAt < DATE_SUB(NOW(), INTERVAL {$lastTryInterval} MINUTE)
//              )
//              )
//     ORDER BY alrCreatedAt ASC
// SQL;

      // echo "fetch query:\n{$qry}\n\n";
// $alertsModels = Yii::$app->db->createCommand($qry)->queryAll();

			$query = AlertModel::find()
        ->innerJoinWith('alertTemplate')
        ->andWhere(['alrLockedBy' => $instanceID])
        ->andWhere(['OR',
          ['alrStatus' => enuAlertStatus::NEW],
          ['AND',
            ['alrStatus' => enuAlertStatus::ERROR],
            ['<', 'alrLastTryAt', new Expression("DATE_SUB(NOW(), INTERVAL {$lastTryInterval} MINUTE)")]
          ]
        ])
        ->orderBy('alrCreatedAt');

			if ($alertID > 0) {
				$query->andWhere(['alrID' => $alertID]);
			} else {
				// $query->andWhere(['OR',
				// 	['alrLockedAt IS NULL'],
				// 	['<', 'alrLockedAt', new Expression('DATE_SUB(NOW(), INTERVAL 1 HOUR)')],
				// 	['alrLockedBy' => $instanceID],
				// ]);
			}

			$alertsModels = $query->all();

      // echo "fetched count: " . count($alertsModels) . "\n";

      if (empty($alertsModels)) {
        // echo "nothing to do\n";
        return; // ExitCode::OK;
      }

      $expNow = new Expression('NOW()');

      foreach ($alertsModels as $alertModel) {
        if (empty($alertModel->alertTemplate->altParamsPrefix) == false
          || empty($alertModel->alertTemplate->altParamsSuffix) == false)
        {
          $replacements = [];
          foreach ($alertModel->alrInfo as $k => $v) {
            $replacements[
              ($alertModel->alertTemplate->altParamsPrefix ?? '')
              . $k
              . ($alertModel->alertTemplate->altParamsSuffix ?? '')
            ] = $v;
          }
        } else
          $replacements = $alertModel->alrInfo;

        $title = strtr($alertModel->alertTemplate->altTitle ?? '', $replacements);
        $body = strtr($alertModel->alertTemplate->altBody ?? '', $replacements);

        $errorCount = 0;
        $now = date('U');

        $alrResult = $alertModel->alrResult;
        if ($alrResult == null)
          $alrResult = [];

				if (empty($alertID))
					echo "processing alert ({$alertModel->alrID}):\n";

        //-- email -----
        try {
          $key = 'E';

          if (in_array($alertModel->alertTemplate->altMedia, [$key, 'A'])
              && (($alrResult[$key]['status'] ?? 'N') != 'S')
          ) {
            if (empty($alertID))
							echo "    Send Email to " . $alertModel->alrTarget . ": ";

            $refID = $this->SendEmailForItem($alertModel, $title, $body);

            if (empty($alertID))
							echo "OK. ref: " . $refID . "\n";

            $alrResult[$key] = [
              'status' => 'S',
              'ref-id' => $refID,
              'sent-at' => $now,
            ];
          }
        } catch(\Exception $exp) {
          if (empty($alertID))
						echo "Error. " . $exp->getMessage() . "\n";

          ++$errorCount;

          $alrResult[$key] = [
            'status' => 'E',
            // 'at' => $expNow,
          ];
        }

        //-- sms -----
        try {
          $key = 'S';

          if (in_array($alertModel->alertTemplate->altMedia, [$key, 'A'])
              && (($alrResult[$key]['status'] ?? 'N') != 'S')
          ) {
            if (empty($alertID))
							echo "    Send Sms to " . $alertModel->alrTarget . ": ";

            $refID = $this->SendSmsForItem($alertModel, $title, $body);

            if (empty($alertID))
							echo "OK. ref: " . $refID . "\n";

            $alrResult[$key] = [
              'status' => 'S',
              'ref-id' => $refID,
              'sent-at' => $now,
            ];
          }
        } catch(\Exception $exp) {
          if (empty($alertID))
						echo "Error. " . $exp->getMessage() . "\n";

          ++$errorCount;

          $alrResult[$key] = [
            'status' => 'E',
            // 'at' => $expNow,
          ];
        }

        //-- push -----

        //--
        $alertModel->alrLockedAt  = null;
        $alertModel->alrLockedBy  = null;
        $alertModel->alrLastTryAt = $expNow;
        $alertModel->alrSentAt    = ($errorCount == 0 ? $expNow : null);
        $alertModel->alrResult    = empty($alrResult) ? null : $alrResult;
        $alertModel->alrStatus    = ($errorCount == 0 ? enuAlertStatus::SENT : enuAlertStatus::ERROR);
        $alertModel->save();

        if ($alertModel->alrStatus == enuAlertStatus::SENT) {
          $qry = '';

          if (empty($alertModel->alrApprovalRequestID) == false) {
            $qry = <<<SQL
       UPDATE tbl_AAA_ApprovalRequest
          SET aprStatus = {$fnGetConst(enuApprovalRequestStatus::SENT)}
            , aprSentAt = NOW()
        WHERE aprID = {$alertModel->alrApprovalRequestID}
SQL;
          } else if (empty($alertModel->alrForgotPasswordRequestID) == false) {
            $qry = <<<SQL
     UPDATE tbl_AAA_ForgotPasswordRequest
        SET fprStatus = {$fnGetConst(enuForgotPasswordRequestStatus::SENT)}
          , fprSentAt = NOW()
      WHERE fprID = {$alertModel->alrForgotPasswordRequestID}
SQL;
          }

          if (empty($qry) == false) {
            $rowsCount = Yii::$app->db->createCommand($qry)->execute();
          }
        }
      }

		} catch(\Exception $e) {
      if (empty($alertID))
				echo $e->getMessage();
			Yii::error($e, __METHOD__);
		}
	}

	  /**
   * return: refID : string
   */
  private function SendEmailForItem($alertModel, $title, $body) {
    if (empty(Yii::$app->params['senderEmail']))
      throw new \Exception("error in send email: senderEmail not set in config file");

    $email = Yii::$app->mailer
			->compose(
				//['html' => 'aaa-html', 'text' => 'aaa-text'],
				//['user' => $user]
			)
			->setFrom(Yii::$app->params['senderEmail'])
			->setTo($alertModel->alrTarget)
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
  private function SendSmsForItem($alertModel, $title, $body) {
    // $alr_altCode            = $row['alr_altCode'];
    // $altLanguage             = $row["altLanguage"];

    // return "dummy ref id";

    $result = $this->sendSms(
      $body,
      $alertModel->alrTarget
    );

    if ($result->status)
      return $result->refID;

    throw new \Exception("error in send sms: " . $result->message . " (" . $result->refID . ")");
  }

}
