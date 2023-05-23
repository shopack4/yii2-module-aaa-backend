<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230518_181736_aaa_rename_alert_to_message extends Migration
{
	public function safeUp()
	{
		$this->execute(<<<SQLSTR
DROP TRIGGER IF EXISTS `trg_updatelog_tbl_AAA_Alert`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
RENAME TABLE `tbl_AAA_Alert` TO `tbl_AAA_Message`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_Message`
	DROP FOREIGN KEY `FK_tbl_AAA_Alert_tbl_AAA_ApprovalRequest`,
	DROP FOREIGN KEY `FK_tbl_AAA_Alert_tbl_AAA_ForgotPasswordRequest`,
	DROP FOREIGN KEY `FK_tbl_AAA_Alert_tbl_AAA_User`,
	DROP FOREIGN KEY `FK_tbl_AAA_Alert_tbl_AAA_User_creator`;
SQLSTR
		);

		$this->execute("CALL DropIndexIfExists('tbl_AAA_Message', 'FK_tbl_AAA_Alert_tbl_AAA_User');");
		$this->execute("CALL DropIndexIfExists('tbl_AAA_Message', 'FK_tbl_AAA_Alert_tbl_AAA_User_creator');");
		$this->execute("CALL DropIndexIfExists('tbl_AAA_Message', 'FK_tbl_AAA_Alert_tbl_AAA_ApprovalRequest');");
		$this->execute("CALL DropIndexIfExists('tbl_AAA_Message', 'FK_tbl_AAA_Alert_tbl_AAA_ForgotPasswordRequest');");

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_Message`
	CHANGE COLUMN `alrID` `msgID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
	CHANGE COLUMN `alrUserID` `msgUserID` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `msgID`,
	CHANGE COLUMN `alrApprovalRequestID` `msgApprovalRequestID` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `msgUserID`,
	CHANGE COLUMN `alrForgotPasswordRequestID` `msgForgotPasswordRequestID` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `msgApprovalRequestID`,
	CHANGE COLUMN `alrTypeKey` `msgTypeKey` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `msgForgotPasswordRequestID`,
	CHANGE COLUMN `alrTarget` `msgTarget` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `msgTypeKey`,
	CHANGE COLUMN `alrInfo` `msgInfo` JSON NOT NULL AFTER `msgTarget`,
	CHANGE COLUMN `alrLockedAt` `msgLockedAt` DATETIME NULL DEFAULT NULL AFTER `msgInfo`,
	CHANGE COLUMN `alrLockedBy` `msgLockedBy` VARCHAR(64) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `msgLockedAt`,
	CHANGE COLUMN `alrLastTryAt` `msgLastTryAt` DATETIME NULL DEFAULT NULL AFTER `msgLockedBy`,
	CHANGE COLUMN `alrSentAt` `msgSentAt` DATETIME NULL DEFAULT NULL AFTER `msgLastTryAt`,
	CHANGE COLUMN `alrResult` `msgResult` JSON NULL DEFAULT NULL AFTER `msgSentAt`,
	CHANGE COLUMN `alrStatus` `msgStatus` CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'N:New, P:Processing, S:Sent, E:Error, R:Removed' COLLATE 'utf8mb4_unicode_ci' AFTER `msgResult`,
	CHANGE COLUMN `alrCreatedAt` `msgCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `msgStatus`,
	CHANGE COLUMN `alrCreatedBy` `msgCreatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `msgCreatedAt`,
	CHANGE COLUMN `alrUpdatedAt` `msgUpdatedAt` DATETIME NULL DEFAULT NULL AFTER `msgCreatedBy`,
	CHANGE COLUMN `alrUpdatedBy` `msgUpdatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `msgUpdatedAt`,
	CHANGE COLUMN `alrRemovedAt` `msgRemovedAt` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `msgUpdatedBy`,
	CHANGE COLUMN `alrRemovedBy` `msgRemovedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `msgRemovedAt`,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`msgID`) USING BTREE;
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_Message`
	ADD CONSTRAINT `FK_tbl_AAA_Message_tbl_AAA_User` FOREIGN KEY (`msgUserID`) REFERENCES `tbl_AAA_User` (`usrID`) ON UPDATE NO ACTION ON DELETE NO ACTION,
	ADD CONSTRAINT `FK_tbl_AAA_Message_tbl_AAA_ApprovalRequest` FOREIGN KEY (`msgApprovalRequestID`) REFERENCES `tbl_AAA_ApprovalRequest` (`aprID`) ON UPDATE NO ACTION ON DELETE NO ACTION,
	ADD CONSTRAINT `FK_tbl_AAA_Message_tbl_AAA_ForgotPasswordRequest` FOREIGN KEY (`msgForgotPasswordRequestID`) REFERENCES `tbl_AAA_ForgotPasswordRequest` (`fprID`) ON UPDATE NO ACTION ON DELETE NO ACTION;
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_Message`
	ADD COLUMN `msgIssuer` VARCHAR(64) NULL DEFAULT NULL AFTER `msgInfo`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
UPDATE `tbl_AAA_Message`
	SET `msgIssuer` = 'aaa'
	WHERE `msgIssuer` IS NULL;
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_Message`
	CHANGE COLUMN `msgIssuer` `msgIssuer` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `msgInfo`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER trg_updatelog_tbl_AAA_Message AFTER UPDATE ON tbl_AAA_Message FOR EACH ROW BEGIN
	DECLARE Changes JSON DEFAULT JSON_OBJECT();

	IF ISNULL(OLD.msgUserID) != ISNULL(NEW.msgUserID) OR OLD.msgUserID != NEW.msgUserID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgUserID", IF(ISNULL(OLD.msgUserID), NULL, OLD.msgUserID))); END IF;
	IF ISNULL(OLD.msgApprovalRequestID) != ISNULL(NEW.msgApprovalRequestID) OR OLD.msgApprovalRequestID != NEW.msgApprovalRequestID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgApprovalRequestID", IF(ISNULL(OLD.msgApprovalRequestID), NULL, OLD.msgApprovalRequestID))); END IF;
	IF ISNULL(OLD.msgForgotPasswordRequestID) != ISNULL(NEW.msgForgotPasswordRequestID) OR OLD.msgForgotPasswordRequestID != NEW.msgForgotPasswordRequestID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgForgotPasswordRequestID", IF(ISNULL(OLD.msgForgotPasswordRequestID), NULL, OLD.msgForgotPasswordRequestID))); END IF;
	IF ISNULL(OLD.msgTypeKey) != ISNULL(NEW.msgTypeKey) OR OLD.msgTypeKey != NEW.msgTypeKey THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgTypeKey", IF(ISNULL(OLD.msgTypeKey), NULL, OLD.msgTypeKey))); END IF;
	IF ISNULL(OLD.msgTarget) != ISNULL(NEW.msgTarget) OR OLD.msgTarget != NEW.msgTarget THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgTarget", IF(ISNULL(OLD.msgTarget), NULL, OLD.msgTarget))); END IF;
	IF ISNULL(OLD.msgInfo) != ISNULL(NEW.msgInfo) OR OLD.msgInfo != NEW.msgInfo THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgInfo", IF(ISNULL(OLD.msgInfo), NULL, OLD.msgInfo))); END IF;
	IF ISNULL(OLD.msgIssuer) != ISNULL(NEW.msgIssuer) OR OLD.msgIssuer != NEW.msgIssuer THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgIssuer", IF(ISNULL(OLD.msgIssuer), NULL, OLD.msgIssuer))); END IF;
	IF ISNULL(OLD.msgLockedAt) != ISNULL(NEW.msgLockedAt) OR OLD.msgLockedAt != NEW.msgLockedAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgLockedAt", IF(ISNULL(OLD.msgLockedAt), NULL, OLD.msgLockedAt))); END IF;
	IF ISNULL(OLD.msgLockedBy) != ISNULL(NEW.msgLockedBy) OR OLD.msgLockedBy != NEW.msgLockedBy THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgLockedBy", IF(ISNULL(OLD.msgLockedBy), NULL, OLD.msgLockedBy))); END IF;
	IF ISNULL(OLD.msgLastTryAt) != ISNULL(NEW.msgLastTryAt) OR OLD.msgLastTryAt != NEW.msgLastTryAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgLastTryAt", IF(ISNULL(OLD.msgLastTryAt), NULL, OLD.msgLastTryAt))); END IF;
	IF ISNULL(OLD.msgSentAt) != ISNULL(NEW.msgSentAt) OR OLD.msgSentAt != NEW.msgSentAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgSentAt", IF(ISNULL(OLD.msgSentAt), NULL, OLD.msgSentAt))); END IF;
	IF ISNULL(OLD.msgResult) != ISNULL(NEW.msgResult) OR OLD.msgResult != NEW.msgResult THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgResult", IF(ISNULL(OLD.msgResult), NULL, OLD.msgResult))); END IF;
	IF ISNULL(OLD.msgStatus) != ISNULL(NEW.msgStatus) OR OLD.msgStatus != NEW.msgStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("msgStatus", IF(ISNULL(OLD.msgStatus), NULL, OLD.msgStatus))); END IF;

	IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.msgUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

		INSERT INTO tbl_SYS_ActionLogs
				SET atlBy     = NEW.msgUpdatedBy
					, atlAction = "UPDATE"
					, atlTarget = "tbl_AAA_Message"
					, atlInfo   = JSON_OBJECT("msgID", OLD.msgID, "old", Changes);
	END IF;
END;
SQLSTR
		);

		$this->execute(<<<SQLSTR
DROP TRIGGER IF EXISTS `trg_updatelog_tbl_AAA_AlertTemplate`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
RENAME TABLE `tbl_AAA_AlertTemplate` TO `tbl_AAA_MessageTemplate`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_MessageTemplate`
	DROP INDEX `altKey_altMedia_altLanguage`,
	DROP INDEX `altKey`,
	DROP INDEX `altMedia`,
	DROP INDEX `altLanguage`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_MessageTemplate`
	CHANGE COLUMN `altID` `mstID` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
	CHANGE COLUMN `altKey` `mstKey` VARCHAR(64) NOT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `mstID`,
	CHANGE COLUMN `altMedia` `mstMedia` CHAR(1) NOT NULL COMMENT 'E:Email, S:SMS' COLLATE 'utf8mb4_unicode_ci' AFTER `mstKey`,
	CHANGE COLUMN `altLanguage` `mstLanguage` CHAR(5) NOT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `mstMedia`,
	CHANGE COLUMN `altTitle` `mstTitle` VARCHAR(512) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `mstLanguage`,
	CHANGE COLUMN `altBody` `mstBody` MEDIUMTEXT NOT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `mstTitle`,
	CHANGE COLUMN `altParamsPrefix` `mstParamsPrefix` VARCHAR(10) NOT NULL DEFAULT '{{' COLLATE 'utf8mb4_unicode_ci' AFTER `mstBody`,
	CHANGE COLUMN `altParamsSuffix` `mstParamsSuffix` VARCHAR(10) NULL DEFAULT '}}' COLLATE 'utf8mb4_unicode_ci' AFTER `mstParamsPrefix`,
	CHANGE COLUMN `altStatus` `mstStatus` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A:Active, D:Disable, R:Removed' COLLATE 'utf8mb4_unicode_ci' AFTER `mstParamsSuffix`,
	CHANGE COLUMN `altCreatedAt` `mstCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `mstStatus`,
	CHANGE COLUMN `altCreatedBy` `mstCreatedBy` BIGINT(19) NULL DEFAULT NULL AFTER `mstCreatedAt`,
	CHANGE COLUMN `altUpdatedAt` `mstUpdatedAt` DATETIME NULL DEFAULT NULL AFTER `mstCreatedBy`,
	CHANGE COLUMN `altUpdatedBy` `mstUpdatedBy` BIGINT(19) NULL DEFAULT NULL AFTER `mstUpdatedAt`,
	CHANGE COLUMN `altRemovedAt` `mstRemovedAt` INT(10) NOT NULL DEFAULT '0' AFTER `mstUpdatedBy`,
	CHANGE COLUMN `altRemovedBy` `mstRemovedBy` BIGINT(19) NULL DEFAULT NULL AFTER `mstRemovedAt`,
	DROP PRIMARY KEY,
	ADD PRIMARY KEY (`mstID`) USING BTREE;
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_MessageTemplate`
	ADD UNIQUE INDEX `mstKey_mstMedia_mstLanguage` (`mstKey`, `mstMedia`, `mstLanguage`);
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_MessageTemplate`
	ADD COLUMN `mstIsSystem` BIT NULL AFTER `mstParamsSuffix`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
UPDATE `tbl_AAA_MessageTemplate`
	SET `mstIsSystem` = 1
	WHERE `mstIsSystem` IS NULL;
SQLSTR
		);

		$this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_MessageTemplate`
	CHANGE COLUMN `mstIsSystem` `mstIsSystem` BIT(1) NOT NULL AFTER `mstParamsSuffix`;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER trg_updatelog_tbl_AAA_MessageTemplate AFTER UPDATE ON tbl_AAA_MessageTemplate FOR EACH ROW BEGIN
	DECLARE Changes JSON DEFAULT JSON_OBJECT();

	IF ISNULL(OLD.mstKey) != ISNULL(NEW.mstKey) OR OLD.mstKey != NEW.mstKey THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("mstKey", IF(ISNULL(OLD.mstKey), NULL, OLD.mstKey))); END IF;
	IF ISNULL(OLD.mstMedia) != ISNULL(NEW.mstMedia) OR OLD.mstMedia != NEW.mstMedia THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("mstMedia", IF(ISNULL(OLD.mstMedia), NULL, OLD.mstMedia))); END IF;
	IF ISNULL(OLD.mstLanguage) != ISNULL(NEW.mstLanguage) OR OLD.mstLanguage != NEW.mstLanguage THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("mstLanguage", IF(ISNULL(OLD.mstLanguage), NULL, OLD.mstLanguage))); END IF;
	IF ISNULL(OLD.mstTitle) != ISNULL(NEW.mstTitle) OR OLD.mstTitle != NEW.mstTitle THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("mstTitle", IF(ISNULL(OLD.mstTitle), NULL, OLD.mstTitle))); END IF;
	IF ISNULL(OLD.mstBody) != ISNULL(NEW.mstBody) OR OLD.mstBody != NEW.mstBody THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("mstBody", IF(ISNULL(OLD.mstBody), NULL, OLD.mstBody))); END IF;
	IF ISNULL(OLD.mstParamsPrefix) != ISNULL(NEW.mstParamsPrefix) OR OLD.mstParamsPrefix != NEW.mstParamsPrefix THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("mstParamsPrefix", IF(ISNULL(OLD.mstParamsPrefix), NULL, OLD.mstParamsPrefix))); END IF;
	IF ISNULL(OLD.mstParamsSuffix) != ISNULL(NEW.mstParamsSuffix) OR OLD.mstParamsSuffix != NEW.mstParamsSuffix THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("mstParamsSuffix", IF(ISNULL(OLD.mstParamsSuffix), NULL, OLD.mstParamsSuffix))); END IF;
	IF ISNULL(OLD.mstIsSystem) != ISNULL(NEW.mstIsSystem) OR OLD.mstIsSystem != NEW.mstIsSystem THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("mstIsSystem", IF(ISNULL(OLD.mstIsSystem), NULL, OLD.mstIsSystem))); END IF;
	IF ISNULL(OLD.mstStatus) != ISNULL(NEW.mstStatus) OR OLD.mstStatus != NEW.mstStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("mstStatus", IF(ISNULL(OLD.mstStatus), NULL, OLD.mstStatus))); END IF;

	IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.mstUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

		INSERT INTO tbl_SYS_ActionLogs
				SET atlBy     = NEW.mstUpdatedBy
					, atlAction = "UPDATE"
					, atlTarget = "tbl_AAA_MessageTemplate"
					, atlInfo   = JSON_OBJECT("mstID", OLD.mstID, "old", Changes);
	END IF;
END;
SQLSTR
		);

    $this->batchInsertIgnore('tbl_AAA_MessageTemplate', [
      'mstKey',
      'mstMedia',
      'mstLanguage',
      'mstTitle',
      'mstBody',
      'mstParamsPrefix',
      'mstParamsSuffix',
			'mstIsSystem',
    ], [
			[ 'happyBirthday', 'S', 'fa', NULL, "خانه موسیقی\nعضو محترم\n{{member}}\nزادروزتان مبارک", '{{', '}}', 1 ],
		]);

	}

	public function safeDown()
	{
		echo "m230518_181736_aaa_rename_alert_to_message cannot be reverted.\n";
		return false;
	}

}
