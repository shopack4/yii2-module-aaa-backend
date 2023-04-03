<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend;

use Yii;
use yii\base\BootstrapInterface;

class Module
	extends \shopack\base\common\base\BaseModule
	implements BootstrapInterface
{
	public function init()
	{
		if (empty($this->id))
			$this->id = 'aaa';

		parent::init();
	}

	public function bootstrap($app)
	{
		if ($app instanceof \yii\web\Application)
		{
			$rules = [
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/auth'],
					'pluralize' => false,
					'extraPatterns' => [
						'POST signup' => 'signup',
						'POST login' => 'login',
						'GET,POST logout' => 'logout',

						'POST login-by-mobile' => 'login-by-mobile',

						'POST challenge' => 'challenge',
						'POST challenge-timer-info' => 'challenge-timer-info',

						'POST request-approval-code' => 'request-approval-code',
						'POST accept-approval' => 'accept-approval',

						'POST request-forgot-password' => 'request-forgot-password',
						'POST password-reset-by-forgot-code' => 'password-reset-by-forgot-code',
						'POST password-reset' => 'password-reset',
						'POST password-change' => 'password-change',
					],
				],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/user'],
					'pluralize' => false,
					'extraPatterns' => [
						'GET,POST whoami' => 'who-am-i',
						'POST email-change' => 'email-change',
						'POST update-image' => 'update-image',
					],
				],
				// [
				// 	'class' => \yii\rest\UrlRule::class,
				// 	// 'prefix' => 'v1',
				// 	'controller' => [$this->id . '/user-extra-info'],
				// 	'pluralize' => false,
				// ],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/approval-request'],
					'pluralize' => false,
				],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/role'],
					'pluralize' => false,
				],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/gateway'],
					'pluralize' => false,
					'extraPatterns' => [
						'GET,POST plugin-list' => 'plugin-list',
						'GET,POST plugin-params-schema' => 'plugin-params-schema',
						'GET,POST plugin-restrictions-schema' => 'plugin-restrictions-schema',
						'GET,POST plugin-usages-schema' => 'plugin-usages-schema',
						'GET,POST plugin-webhooks-schema' => 'plugin-webhooks-schema',
						'webhook' => 'webhook',
					],
				],

				//fin
				// [
				// 	'class' => \yii\rest\UrlRule::class,
				// 	// 'prefix' => 'v1',
				// 	'controller' => [$this->id . '/payment-gateway'],
				// 	'pluralize' => false,
				// 	'extraPatterns' => [
				// 		'POST callback' => 'callback',
				// 	],
				// ],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/online-payment'],
					'pluralize' => false,
					'extraPatterns' => [
						'callback/<onpid:\d+>' => 'callback',
						'callback' => 'callback',
					],
				],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/voucher'],
					'pluralize' => false,
				],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/geo-country'],
					'pluralize' => false,
				],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/geo-state'],
					'pluralize' => false,
				],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/geo-city-or-village'],
					'pluralize' => false,
				],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/geo-town'],
					'pluralize' => false,
				],
				[
					'class' => \yii\rest\UrlRule::class,
					// 'prefix' => 'v1',
					'controller' => [$this->id . '/upload-file'],
					'pluralize' => false,
				],
			];

			$app->urlManager->addRules($rules, false);
		}
		elseif ($app instanceof \yii\console\Application)
		{
			//http://www.yiiframework.com/wiki/820/yii2-create-console-commands-inside-a-module-or-extension/
			$this->controllerNamespace = 'shopack\aaa\backend\commands';
			// $app->controllerMap['aaa'] = [
				// 'class' => 'shopack\aaa\backend\commands\SmsController',
				// 'generators' => array_merge($this->coreGenerators(), $this->generators),
				// 'module' => $this,
			// ];
		}
	}

	public function GatewayPluginList($type = null)
	{
		return $this->ExtensionList('gateways', $type);
	}

	public function GatewayPluginParamsSchema($key)
	{
		return $this->ExtensionParamsSchema('gateways', $key);
	}

	public function GatewayPluginRestrictionsSchema($key)
	{
		return $this->ExtensionRestrictionsSchema('gateways', $key);
	}

	public function GatewayPluginUsagesSchema($key)
	{
		return $this->ExtensionUsagesSchema('gateways', $key);
	}

	public function GatewayPluginWebhooksSchema($key)
	{
		return $this->ExtensionWebhooksSchema('gateways', $key);
	}

	public function GatewayClass($pluginName)
	{
		return $this->ExtensionClass('gateways', $pluginName);
	}

}
