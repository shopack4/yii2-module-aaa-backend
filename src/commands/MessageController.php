<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class MessageController extends Controller
{
  //must be called by cron
  public function actionProcessQueue($maxItemCount = 100)
  {
    try {

      Yii::$app->messageManager->processQueue($maxItemCount);

		} catch(\Exception $e) {
      echo $e->getMessage();
			Yii::error($e, __METHOD__);
		}

    return ExitCode::OK;
  }

    //must be called by cron
    public function actionSendBirthdayGreetings()
    {
      try {

        $rowsCount = Yii::$app->messageManager->sendBirthdayGreetings();
        if ($rowsCount > 0)
          echo "new messages: {$rowsCount}";

      } catch(\Exception $e) {
        echo $e->getMessage();
        Yii::error($e, __METHOD__);
      }

      return ExitCode::OK;
    }

}
