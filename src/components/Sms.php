<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\components;

use Yii;
use yii\base\Component;
use yii\web\NotFoundHttpException;
use shopack\aaa\backend\models\GatewayModel;
use shopack\aaa\common\enums\enuGatewayStatus;
use shopack\aaa\backend\classes\SmsSendResult;

class Sms extends Component
{
	private $defaultGatewayModel = null;

	public function getDefaultGateway()
	{
		if ($this->defaultGatewayModel == null)
		{
      $this->defaultGatewayModel = GatewayModel::find()
        ->andWhere(['gtwPluginType' => 'sms'])
        ->andWhere(['<>', 'gtwStatus', enuGatewayStatus::REMOVED])
        ->one();

      if ($this->defaultGatewayModel == null)
        throw new NotFoundHttpException('sms gateway not found');
		}

		return $this->defaultGatewayModel;
	}

	/**
	 * return: [status(bool), result]
	 * status = true
	 * 		result =
	 */
	public function send($message, $to, $from = null) : SmsSendResult
	{
		return $this->defaultGateway->gatewayClass->send($message, $to, $from);
	}

}
