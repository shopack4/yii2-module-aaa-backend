<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230412_132444_aaa_change_expire_enum_from_e_to_x extends Migration
{
	public function safeUp()
	{
		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_ApprovalRequest`
	CHANGE COLUMN `aprStatus` `aprStatus` CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'N:New, S:Sent, A:Applied, X:Expired' COLLATE 'utf8mb4_unicode_ci' AFTER `aprApplyAt`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
UPDATE `tbl_AAA_ApprovalRequest`
	SET `aprStatus` = 'X'
	WHERE `aprStatus` = 'E';
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_ForgotPasswordRequest`
	CHANGE COLUMN `fprStatus` `fprStatus` CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'N:New, S:Sent, A:Applied, X:Expired' COLLATE 'utf8mb4_unicode_ci' AFTER `fprApplyAt`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
UPDATE `tbl_AAA_ForgotPasswordRequest`
	SET `fprStatus` = 'X'
	WHERE `fprStatus` = 'E';
SQLSTR
		);
	}

	public function safeDown()
	{
		echo "m230412_132444_aaa_change_expire_enum_from_e_to_x cannot be reverted.\n";
		return false;
	}

}
