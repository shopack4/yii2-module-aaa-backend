<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\classes;

use Yii;
use shopack\aaa\backend\classes\BaseObjectStorageGateway;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use yii\helpers\FileHelper;

abstract class BaseS3ObjectStorageGateway extends BaseObjectStorageGateway
{
	abstract public function getEndpointIsVirtualHosted();
	abstract public function getEndpoint();
	abstract public function getBucket();
	abstract public function getAccessKey();
	abstract public function getSecretKey();

	const PARAM_BUCKET                  = 'bucket';
	const PARAM_EndpointIsVirtualHosted = 'EndpointIsVirtualHosted';
	const PARAM_ACCESS_KEY              = 'accesskey';
	const PARAM_SECRET_KEY              = 'secretkey';

	public function getParametersSchema()
	{
		return array_merge(parent::getParametersSchema(), [
			[
				'id' => self::PARAM_TYPE,
				'type' => 'combo',
				'data' => [
					"s3" => "AWS S3",
				],
				'default' => "s3",
				'label' => 'Type',
				'mandatory' => 1,
			],
			[
				'id' => self::PARAM_BUCKET,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Bucket',
				'style' => 'direction:ltr',
			],
			[
				'id' => self::PARAM_EndpointIsVirtualHosted,
				'type' => 'bool',
				'mandatory' => 1,
				'label' => 'Endpoint Is Virtual Hosted',
			],
			[
				'id' => self::PARAM_ACCESS_KEY,
				'type' => 'string',
				'mandatory' => 1,
				'label' => 'Access Key',
				'style' => 'direction:ltr',
			],
			[
				'id' => self::PARAM_SECRET_KEY,
				'type' => 'password',
				'mandatory' => 1,
				'label' => 'Secret Key',
				'style' => 'direction:ltr',
			],
		]);
	}

	/**
	 * return bool
	 */
	public function upload(
		$fullFileName,
    $path,
    $fileName
	) {
		$EndpointIsVirtualHosted = $this->getEndpointIsVirtualHosted();
		$endpoint   = $this->getEndpoint();
		$bucket     = $this->getBucket();
		$accesskey  = $this->getAccessKey();
		$secretkey  = $this->getSecretKey();

		$s3Client = new S3Client([
			'region' => '',
			// 'version' => 'latest',
			'version' => '2006-03-01',
			'endpoint' => $endpoint,
			'credentials' => [
				'key' => $accesskey,
				'secret' => $secretkey,
			],
			// Set the S3 class to use objects. arvanstorage.com/bucket
			// instead of bucket.objects.arvanstorage.com
			'use_path_style_endpoint' => !$EndpointIsVirtualHosted,
			'http' => [
				'verify' => false, //'/path/to/my/cert.pem'
			],
			//prevent using curl due to undefined function curl_multi_init
			'http_handler' => new \GuzzleHttp\Handler\StreamHandler,
		]);

		$fullKey = implode('/', [$path, $fileName]);

		$fullFileName = FileHelper::normalizePath($fullFileName);

		try {
			$s3Client->putObject([
				'Bucket' => $bucket,
				'Key'    => $fullKey,
				'Body'   => fopen($fullFileName, 'r'),
				'ACL'    => 'public-read',
			]);

			return true;

		} catch (S3Exception $exp) {
			echo "There was an error uploading the file: " . $exp->getMessage();
			throw $exp;
		}

		return false;
	}

}
