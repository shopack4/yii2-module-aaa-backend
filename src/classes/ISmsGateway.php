<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\classes;

// use Yii;
// use shopack\base\helpers\ArrayHelper;
// use yii\helpers\Inflector;

class SmsSendResult
{
	public bool $status;
	public ?string $message;
	public ?string $refID;

	public function __construct(
		bool $status,
		?string $message = null,
		?string $refID = null
	) {
		$this->status = $status;
		$this->message = $message;
		$this->refID = $refID;
	}
}

interface ISmsGateway
{
	// public function getLineNumber();

	public function send(
		$message,
		$to,
		$from = null //null => use default in gtwPluginParameters
	) : SmsSendResult;

	public function receive();

}
