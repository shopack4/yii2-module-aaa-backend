<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230324_135000_aaa_add_uuid_to_users extends Migration
{
	public function safeUp()
	{
    $this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_User`
	ADD COLUMN `usrUUID` VARCHAR(38) NULL AFTER `usrID`;
SQLSTR
		);

    $this->execute(<<<SQLSTR
UPDATE tbl_AAA_User
	SET usrUUID = UUID()
	WHERE usrUUID IS NULL;
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_User`
	CHANGE COLUMN `usrUUID` `usrUUID` VARCHAR(38) NOT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `usrID`;
SQLSTR
		);
	}

	public function safeDown()
	{
		echo "m230324_135000_aaa_add_uuid_to_users cannot be reverted.\n";
		return false;
	}

}
