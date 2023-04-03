<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\classes;

use shopack\base\backend\rest\RestServerActiveRecord;

abstract class AAAActiveRecord extends RestServerActiveRecord
{
	public static function getDb()
	{
		return \shopack\aaa\backend\Module::getInstance()->db;
		// return Yii::$app->controller->module->db;
	}

}
