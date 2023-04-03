<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230321_100104_aaa_create_tblAlertTemplate extends Migration
{
  public function safeUp()
	{
		$this->execute('DROP TRIGGER IF EXISTS `trg_updatelog_tbl_AAA_AlertType`;');

		$this->execute('RENAME TABLE `tbl_AAA_AlertType` TO `DELETED_tbl_AAA_AlertType`;');

    $this->execute(<<<SQLSTR
CREATE TABLE `{{%AAA_AlertTemplate}}` (
	`altID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`altKey` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`altMedia` CHAR(1) NOT NULL COMMENT 'E:Email, S:SMS' COLLATE 'utf8mb4_unicode_ci',
	`altLanguage` CHAR(5) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`altTitle` VARCHAR(512) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`altBody` MEDIUMTEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`altParamsPrefix` VARCHAR(10) NOT NULL DEFAULT '{{' COLLATE 'utf8mb4_unicode_ci',
	`altParamsSuffix` VARCHAR(10) NULL DEFAULT '}}' COLLATE 'utf8mb4_unicode_ci',
	`altStatus` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A:Active, D:Disable, R:Removed' COLLATE 'utf8mb4_unicode_ci',
	`altCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`altCreatedBy` BIGINT(19) NULL DEFAULT NULL,
	`altUpdatedAt` DATETIME NULL DEFAULT NULL,
	`altUpdatedBy` BIGINT(19) NULL DEFAULT NULL,
	`altRemovedAt` INT(10) NOT NULL DEFAULT '0',
	`altRemovedBy` BIGINT(19) NULL DEFAULT NULL,
	PRIMARY KEY (`altID`) USING BTREE,
	UNIQUE INDEX `altKey_altMedia_altLanguage` (`altKey`, `altMedia`, `altLanguage`) USING BTREE,
	INDEX `altKey` (`altKey`) USING BTREE,
	INDEX `altMedia` (`altMedia`) USING BTREE,
	INDEX `altLanguage` (`altLanguage`) USING BTREE
) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB ;
SQLSTR
		);

		//enuApprovalRequestAlertType
    $this->batchInsertIgnore('{{%AAA_AlertTemplate}}', [
      'altKey',
      'altMedia',
      'altLanguage',
      'altTitle',
      'altBody',
      'altParamsPrefix',
      'altParamsSuffix',
    ], [
			[ 'emailApproval',          'E', 'fa', 'email Approval',           'code:{{code}}<br>link:{{link}}', '{{', '}}' ],
			[ 'emailApprovalForLogin',  'E', 'fa', 'email Approval For Login', 'code:{{code}}<br>link:{{link}}', '{{', '}}' ],
			[ 'emailApproved',          'E', 'fa', 'email Approved',           'email approved',                 '{{', '}}' ],
			[ 'mobileApproval',         'S', 'fa', NULL,                       'code:{{code}}',                  '{{', '}}' ],
			[ 'mobileApprovalForLogin', 'S', 'fa', NULL,                       'code:{{code}}',                  '{{', '}}' ],
			[ 'mobileApproved',         'S', 'fa', NULL,                       'mobile approved',                '{{', '}}' ],
		]);

		//enuForgotPasswordRequestAlertType
    $this->batchInsertIgnore('{{%AAA_AlertTemplate}}', [
      'altKey',
      'altMedia',
      'altLanguage',
      'altTitle',
      'altBody',
      'altParamsPrefix',
      'altParamsSuffix',
    ], [
			[ 'forgotPassByEmail',   'E', 'fa', 'forgot Pass By Email',  'code:{{code}}<br>email:{{email}}',   '{{', '}}' ],
			[ 'passChangedByEmail',  'E', 'fa', 'pass Changed By Email', 'password changed',                   '{{', '}}' ],
			[ 'forgotPassByMobile',  'S', 'fa', NULL,                    'code:{{code}}<br>mobile:{{mobile}}', '{{', '}}' ],
			[ 'passChangedByMobile', 'S', 'fa', NULL,                    'password changed',                   '{{', '}}' ],
		]);

	}

	public function safeDown()
	{
		echo "m230321_100104_aaa_create_tblAlertTemplate cannot be reverted.\n";
		return false;
	}

}
