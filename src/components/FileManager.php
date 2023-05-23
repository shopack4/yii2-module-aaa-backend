<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\components;

use Yii;
use yii\db\Expression;
use yii\base\Component;
use yii\web\NotFoundHttpException;
use yii\helpers\FileHelper;
use shopack\aaa\backend\models\UserModel;
use shopack\aaa\backend\models\GatewayModel;
use shopack\aaa\common\enums\enuGatewayStatus;
use shopack\aaa\common\enums\enuUploadQueueStatus;
use shopack\aaa\backend\models\UploadQueueModel;

class FileManager extends Component
{
	public $tempPath = '@app/tmp/upload';

	/**
	 * return $fileID
	 */
	public function saveUploadedFiles($userID, $targetPath = null)
	{
		if (empty($_FILES))
			throw new NotFoundHttpException('nothing to do');

		$functionResult = [];
		$files = [];

		foreach ($_FILES as $imageSetKey => $imageSet) {
			if (is_array($imageSet['name'])) {
				foreach ($imageSet['name'] as $fieldName => $name) {
					$full_path = $imageSet['full_path'][$fieldName];
					$type      = $imageSet['type'][$fieldName];
					$tmp_name  = $imageSet['tmp_name'][$fieldName];
					$error     = $imageSet['error'][$fieldName];
					$size      = $imageSet['size'][$fieldName];

					$files[$fieldName] = [
						'tempFileName' => $tmp_name,
						'fileName' => $name,
					];
				}
			} else {
				$name      = $imageSet['name'];
				$full_path = $imageSet['full_path'];
				$type      = $imageSet['type'];
				$tmp_name  = $imageSet['tmp_name'];
				$error     = $imageSet['error'];
				$size      = $imageSet['size'];

				$files[$imageSetKey] = [
					'tempFileName' => $tmp_name,
					'fileName' => $name,
				];
			}
		}

		$user = UserModel::findOne($userID);
		if ($user === null)
			throw new NotFoundHttpException('The requested item not exist.');

		$targetFullPath = [
			'user',
			$user->usrUUID,
			$userID,
		];

		if ($targetPath)
			$targetFullPath[] = $targetPath;

		$targetFullPath = implode('/', $targetFullPath);

		//
		$tempPath = Yii::getAlias($this->tempPath);
		if (str_ends_with($tempPath, DIRECTORY_SEPARATOR))
			$tempPath = rtrim($tempPath, DIRECTORY_SEPARATOR);
		$tempPath .= DIRECTORY_SEPARATOR . $targetFullPath;
		$tempPath = FileHelper::normalizePath($tempPath);
		if (!is_dir($tempPath))
			mkdir($tempPath, 0777, true);

		foreach ($files as $uploadedFile) {
			$tempFileName = $uploadedFile['tempFileName'];
			$fileName = $uploadedFile['fileName'];

			$namePart = explode('.', basename($fileName));
			$extPart = array_pop($namePart);
			$namePart = implode('.', $namePart);

			$namePart = $namePart . '_' . time();
			$newFileName = implode('.', [$namePart, $extPart]);
			// $targetFullFileName = FileHelper::normalizePath($tempPath . DIRECTORY_SEPARATOR . $newFileName);

			// if (!move_uploaded_file($tempFileName, $targetFullFileName))
			// 	continue;

			//--
			$iPath                   = $targetFullPath;
			$iOriginalFileName       = $fileName;
			$iFullTempPath           = $tempPath; //$tempFileName;
			$iSetTempFileNameToMD5   = true; //false;
			$iFileSize               = filesize($tempFileName);
			$iFileType               = $extPart;
			$iMimeType               = mime_content_type($tempFileName);
				if ($iMimeType === false) $iMimeType = null;

			$iOwnerUserID            = $userID;
			$iCreatorUserID          = Yii::$app->user->id;
			$iLockedBy               = Yii::$app->getInstanceID();

			$connection = \Yii::$app->db;
			$result = $connection->createCommand("CALL spUploadedFile_Create(
				:iPath,
				:iOriginalFileName,
				:iFullTempPath,
				:iSetTempFileNameToMD5,
				:iFileSize,
				:iFileType,
				:iMimeType,
				:iOwnerUserID,
				:iCreatorUserID,
				:iLockedBy,
				@oStoredFileName,
				@oTempFileName,
				@oUploadedFileID,
				@oQueueRowsCount
			)")
				->bindParam(':iPath',                 $iPath)
				->bindParam(':iOriginalFileName',     $iOriginalFileName)
				->bindParam(':iFullTempPath',         $iFullTempPath)
				->bindParam(':iSetTempFileNameToMD5', $iSetTempFileNameToMD5)
				->bindParam(':iFileSize',             $iFileSize)
				->bindParam(':iFileType',             $iFileType)
				->bindParam(':iMimeType',             $iMimeType)
				->bindParam(':iOwnerUserID',          $iOwnerUserID)
				->bindParam(':iCreatorUserID',        $iCreatorUserID)
				->bindParam(':iLockedBy',             $iLockedBy)
				// ->bindParam('@oStoredFileName',       $oStoredFileName, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT) //, 256)
				// ->bindParam('@oTempFileName',         $oTempFileName,   \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT) //, 256)
				// ->bindParam('@oUploadedFileID',       $oUploadedFileID, \PDO::PARAM_INT | \PDO::PARAM_INPUT_OUTPUT) //, 20)
				// ->bindParam('@oQueueRowsCount',       $oQueueRowsCount, \PDO::PARAM_INT | \PDO::PARAM_INPUT_OUTPUT) //, 10)
				->execute();

			$outValues = $connection->createCommand("select
				@oStoredFileName,
				@oTempFileName,
				@oUploadedFileID,
				@oQueueRowsCount
			")->queryOne();

			if (empty($outValues))
				continue;

			$oStoredFileName = $outValues['@oStoredFileName'] ?? null;
			$oTempFileName   = $outValues['@oTempFileName'] ?? null;
			$oUploadedFileID = $outValues['@oUploadedFileID'] ?? null;
			$oQueueRowsCount = $outValues['@oQueueRowsCount'] ?? null;

			if ($oUploadedFileID > 0) {
				$targetFullFileName = FileHelper::normalizePath($tempPath . DIRECTORY_SEPARATOR . $oTempFileName);

				if (!move_uploaded_file($tempFileName, $targetFullFileName))
					continue;

				$functionResult[$imageSetKey] = [
					'fileID' => $oUploadedFileID,
				];

				if ($oQueueRowsCount > 0) {
					//todo: send correct iLockedBy to the sp and async call for process upload queue item
					$this->processQueue($oQueueRowsCount, $oUploadedFileID);
				}

			}
		}

		return $functionResult;
	}

	public function processQueue($maxItemCount = 100, $uploadedFileID = 0)
  {
    $fnGetValue = function($value, $qouted = false) {
			return ($qouted ? "'" : "") . "{$value}" . ($qouted ? "'" : "");
		};

		$procesedQueueCount = 0;

    try {
      $instanceID = Yii::$app->getInstanceID();
      // echo "*** instance id: {$instanceID}\n";

      $lastTryInterval = (YII_ENV_DEV ? 1 : 10);

			$query = UploadQueueModel::find()
				->joinWith('uploadFile')
				->joinWith('gateway')
				->andWhere(['OR',
					['uquStatus' => enuUploadQueueStatus::New],
					['AND',
						['uquStatus' => enuUploadQueueStatus::Error],
						['<', 'uquLastTryAt', new Expression("DATE_SUB(NOW(), INTERVAL {$lastTryInterval} MINUTE)")],
					],
				])
				->orderBy('uquCreatedAt')
				->limit($maxItemCount)
			;

			if ($uploadedFileID > 0) {
				$query->andWhere(['uquFileID' => $uploadedFileID]);
			} else {
				$query->andWhere(['OR',
					['uquLockedAt IS NULL'],
					['<', 'uquLockedAt', new Expression('DATE_SUB(NOW(), INTERVAL 1 HOUR)')],
					['uquLockedBy' => $instanceID],
				]);
			}

			$queueModels = $query
				// ->asArray()
				->all();

			if (empty($queueModels))
				return false;

			//lock
			//$uploadedFileID was already locked before
			if ($uploadedFileID == 0) {
				$qIDs = array_column($queueModels, 'uquID');
				if (empty($qIDs))
					return false;

				$qry =<<<SQL
					UPDATE tbl_AAA_UploadQueue
						 SET uquLockedAt = NOW()
					     , uquLockedBy = '{$instanceID}'
					 WHERE uquID IN ({$fnGetValue(implode(',', $qIDs))})
SQL;
				$lockedRowsCount = Yii::$app->db->createCommand($qry)->execute();
				// echo "locked rows count: {$lockedRowsCount}\n";
			}

			//process
			$failedQueueIDs = [];
			$successQueueIDs = [];
			$successSizesPerGateway = [];
			$successCountsPerGateway = [];

			foreach ($queueModels as $queueModel) {
				$stored = false;

				try {
					$qry =<<<SQL
					UPDATE tbl_AAA_UploadQueue
						 SET uquLastTryAt = NOW()
					 WHERE uquID = {$queueModel->uquID}
SQL;
					Yii::$app->db->createCommand($qry)->execute();

					$stored = $this->storeQueueItemToServer($queueModel);

					if ($stored) {
						$qry =<<<SQL
					UPDATE tbl_AAA_UploadQueue
             SET uquLockedAt = NULL
               , uquLockedBy = NULL
               , uquStoredAt = NOW()
               , uquStatus   = {$fnGetValue(enuUploadQueueStatus::Stored, true)}
					 WHERE uquID = {$queueModel->uquID}
SQL;
						Yii::$app->db->createCommand($qry)->execute();
					}
				} catch (\Exception $exp) {
					// echo "error in storing item: " . $exp->getMessage();
				}

				if ($stored) {
					$successQueueIDs[] = $queueModel->uquID;

					if (isset($successSizesPerGateway[$queueModel->uquGatewayID])) {
						$successSizesPerGateway[$queueModel->uquGatewayID] = $successSizesPerGateway[$queueModel->uquGatewayID] + $queueModel->uploadFile->uflSize;
						$successCountsPerGateway[$queueModel->uquGatewayID] = $successCountsPerGateway[$queueModel->uquGatewayID] + 1;
					} else {
						$successSizesPerGateway[$queueModel->uquGatewayID] = $queueModel->uploadFile->uflSize;
						$successCountsPerGateway[$queueModel->uquGatewayID] = 1;
					}
        } else {
					$failedQueueIDs[] = $queueModel->uquID;
        }

				++$procesedQueueCount;
			} //foreach ($queueModels as $queueModel) {

			//update gateway usages
			if (empty($successSizesPerGateway) == false) {
				foreach (array_keys($successSizesPerGateway) as $gtwID) {
					$count = $successCountsPerGateway[$gtwID];
					$size = $successSizesPerGateway[$gtwID];

					$qry =<<<SQL
						UPDATE tbl_AAA_Gateway
							 SET gtwUsages = JSON_MERGE_PATCH(
										COALESCE(JSON_REMOVE(gtwUsages, '$.CreatedFilesCount', '$.CreatedFilesSize'), '{}'),
										JSON_OBJECT(
										 'CreatedFilesCount', IF(JSON_CONTAINS_PATH(gtwUsages, 'one', '$.CreatedFilesCount'),
											 CAST(JSON_EXTRACT(gtwUsages, '$.CreatedFilesCount') AS UNSIGNED) + {$count},
											 {$count}
										 ),
										 'CreatedFilesSize', IF(JSON_CONTAINS_PATH(gtwUsages, 'one', '$.CreatedFilesSize'),
											 CAST(JSON_EXTRACT(gtwUsages, '$.CreatedFilesSize') AS UNSIGNED) + {$size},
											 {$size}
										 )
										)
									 )
						 WHERE gtwID = {$gtwID}
SQL;

					Yii::$app->db->createCommand($qry)->execute();
				}
			}

			if (empty($failedQueueIDs) == false) {
				$qry =<<<SQL
					UPDATE tbl_AAA_UploadQueue
							SET uquLockedAt = NULL
								, uquLockedBy = NULL
								, uquStatus   = {$fnGetValue(enuUploadQueueStatus::Error, true)}
						WHERE uquID IN ({$fnGetValue(implode(',', $failedQueueIDs))})
SQL;

				Yii::$app->db->createCommand($qry)->execute();
			}

		} catch(\Exception $exp) {
      // echo $exp->getMessage();
			Yii::error($exp, __METHOD__);
		}

		return $procesedQueueCount;
  }

	public function storeQueueItemToServer($queueModel)
	{
		return $queueModel->gateway->gatewayClass->upload(
			$queueModel->uploadFile->uflLocalFullFileName,
			$queueModel->uploadFile->uflPath,
			$queueModel->uploadFile->uflStoredFileName
		);
	}

}
