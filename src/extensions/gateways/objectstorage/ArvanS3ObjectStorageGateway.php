<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\extensions\gateways\objectstorage;

use Yii;
use shopack\aaa\backend\classes\BaseS3ObjectStorageGateway;
use shopack\aaa\backend\classes\IObjectStorageGateway;

class ArvanS3ObjectStorageGateway
	extends BaseS3ObjectStorageGateway
	implements IObjectStorageGateway
{
	// const ENDPOINT_SIMIN     = 'simin';
	// const ENDPOINT_SHAHRIYAR = 'shahriyar';

	const URL_ENDPOINT_SIMIN     = 'https://s3.ir-thr-at1.arvanstorage.ir';
	const URL_ENDPOINT_SHAHRIYAR = 'https://s3.ir-tbz-sh1.arvanstorage.ir';

	const PARAM_ENDPOINT                 = 'endpoint';

	const RESTRICTION_AllowedFileTypes   = 'AllowedFileTypes';
	const RESTRICTION_AllowedMimeTypes   = 'AllowedMimeTypes';
	const RESTRICTION_AllowedMinFileSize = 'AllowedMinFileSize';
	const RESTRICTION_AllowedMaxFileSize = 'AllowedMaxFileSize';
	const RESTRICTION_MaxFilesCount      = 'MaxFilesCount';
	const RESTRICTION_MaxFilesSize       = 'MaxFilesSize';

	const USAGE_CreatedFilesCount        = 'CreatedFilesCount';
	const USAGE_CreatedFilesSize         = 'CreatedFilesSize';
	const USAGE_DeletedFilesCount        = 'DeletedFilesCount';
	const USAGE_DeletedFilesSize         = 'DeletedFilesSize';
	const USAGE_LastActionTime           = 'LastActionTime';

	public function getTitle()
	{
		return 'ذخیره ساز S3 آروان';
	}

	public function getParametersSchema()
	{
		return array_merge(parent::getParametersSchema(), [
			[
				'id' => self::PARAM_ENDPOINT,
				'type' => 'combo',
				'data' => [
					self::URL_ENDPOINT_SIMIN => 'Simin',
					self::URL_ENDPOINT_SHAHRIYAR => 'Shahriyar',
				],
				'default' => self::URL_ENDPOINT_SIMIN,
				'label' => 'Endpoint',
				'mandatory' => 1,
			],
		]);
	}

	public function getRestrictionsSchema()
	{
		return array_merge([
			[
				'id' => self::RESTRICTION_AllowedFileTypes,
				'type' => 'string',
				'label' => 'Allowed File Types',
				// 'mandatory' => 1,
			],
			[
				'id' => self::RESTRICTION_AllowedMimeTypes,
				'type' => 'string',
				'label' => 'Allowed Mime Types',
				// 'mandatory' => 1,
			],
			[
				'id' => self::RESTRICTION_AllowedMinFileSize,
				'type' => 'string',
				'label' => 'Allowed Min File Size',
				// 'mandatory' => 1,
			],
			[
				'id' => self::RESTRICTION_AllowedMaxFileSize,
				'type' => 'string',
				'label' => 'Allowed Max File Size',
				// 'mandatory' => 1,
			],
			[
				'id' => self::RESTRICTION_MaxFilesCount,
				'type' => 'string',
				'label' => 'Max Files Count',
				// 'mandatory' => 1,
			],
			[
				'id' => self::RESTRICTION_MaxFilesSize,
				'type' => 'string',
				'label' => 'Max Files Size',
				// 'mandatory' => 1,
			],
		], parent::getRestrictionsSchema());
	}

	public function getUsagesSchema()
	{
		return array_merge([
			[
				'id' => self::USAGE_CreatedFilesCount,
				'type' => 'string',
				'label' => 'Created Files Count',
			],
			[
				'id' => self::USAGE_CreatedFilesSize,
				'type' => 'string',
				'label' => 'Created Files Size',
			],
			[
				'id' => self::USAGE_DeletedFilesCount,
				'type' => 'string',
				'label' => 'Deleted Files Count',
			],
			[
				'id' => self::USAGE_DeletedFilesSize,
				'type' => 'string',
				'label' => 'Deleted Files Size',
			],
			[
				'id' => self::USAGE_LastActionTime,
				'type' => 'string',
				'label' => 'Last Action Time',
			],
		], parent::getUsagesSchema());
	}

	public function getEndpointIsVirtualHosted()
	{
		return ($this->extensionModel->gtwPluginParameters[self::PARAM_EndpointIsVirtualHosted] ?? 0);
	}

	public function getEndpoint($appendAsPath = false)
	{
		$endpoint = $this->extensionModel->gtwPluginParameters[self::PARAM_ENDPOINT] ?? null;
		$EndpointIsVirtualHosted = $this->endpointIsVirtualHosted;

		// if ($endpoint === self::ENDPOINT_SIMIN)
		// 	$endpoint = self::URL_ENDPOINT_SIMIN;
		// else if ($endpoint === self::ENDPOINT_SHAHRIYAR)
		// 	$endpoint = self::URL_ENDPOINT_SHAHRIYAR;
		// else
		// 	return null;

		$bucket = $this->getBucket();

		if ($EndpointIsVirtualHosted) {
			$endpointUrl = implode('', [
				str_starts_with($endpoint, 'http://')
					? 'http://'
					: 'https://',
				$bucket,
				'.',
				str_starts_with($endpoint, 'http://')
					? substr($endpoint, 7)
					: substr($endpoint, 8)
			]);
		} elseif ($appendAsPath) {
			$endpointUrl = implode('/', [
				$endpoint,
				$bucket,
			]);
		} else
			$endpointUrl = $endpoint;

		return $endpointUrl;
	}
	public function getBucket()
	{
		return $this->extensionModel->gtwPluginParameters[self::PARAM_BUCKET] ?? null;
	}
	public function getAccessKey()
	{
		return $this->extensionModel->gtwPluginParameters[self::PARAM_ACCESS_KEY] ?? null;
	}
	public function getSecretKey()
	{
		return $this->extensionModel->gtwPluginParameters[self::PARAM_SECRET_KEY] ?? null;
	}

}
