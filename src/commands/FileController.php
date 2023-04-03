<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\commands;

use Yii;
use yii\db\Expression;
use yii\console\Controller;
use yii\console\ExitCode;

class FileController extends Controller
{
  //must be called by cron
  public function actionProcessQueue($maxItemCount = 100)
  {
    try {

      Yii::$app->fileManager->processQueue($maxItemCount);

		} catch(\Exception $e) {
      echo $e->getMessage();
			Yii::error($e, __METHOD__);
		}

    return ExitCode::OK;
  }

}
