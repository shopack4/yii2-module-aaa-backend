<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230324_135200_aaa_modify_gateway extends Migration
{
	public function safeUp()
	{
    $this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_Gateway`
	ADD COLUMN `gtwRestrictions` JSON NULL AFTER `gtwPluginParameters`,
	ADD COLUMN `gtwUsages` JSON NULL AFTER `gtwRestrictions`;
SQLSTR
		);

	}

	public function safeDown()
	{
		echo "m230324_135200_aaa_modify_gateway cannot be reverted.\n";
		return false;
	}

}
