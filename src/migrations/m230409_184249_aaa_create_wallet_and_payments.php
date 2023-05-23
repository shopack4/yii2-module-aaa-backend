<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230409_184249_aaa_create_wallet_and_payments extends Migration
{
  public function safeUp()
	{
		$this->execute(<<<SQLSTR
CREATE TABLE `tbl_AAA_Wallet` (
	`walID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`walOwnerUserID` BIGINT(20) UNSIGNED NOT NULL,
	`walName` VARCHAR(128) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`walIsDefault` BIT(1) NOT NULL DEFAULT 0,
	`walRemainedAmount` DOUBLE UNSIGNED NOT NULL,
	`walStatus` CHAR(1) NOT NULL DEFAULT 'A' COMMENT 'A:Active, D:Disable, R:Removed' COLLATE 'utf8mb4_unicode_ci',
	`walCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`walCreatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`walUpdatedAt` DATETIME NULL DEFAULT NULL,
	`walUpdatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`walRemovedAt` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`walRemovedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`walID`) USING BTREE,
	INDEX `FK_tbl_AAA_Wallet_tbl_AAA_User` (`walOwnerUserID`) USING BTREE,
	CONSTRAINT `FK_tbl_AAA_Wallet_tbl_AAA_User` FOREIGN KEY (`walOwnerUserID`) REFERENCES `tbl_AAA_User` (`usrID`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE `tbl_AAA_WalletTransaction` (
	`wtrID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`wtrWalletID` BIGINT(20) UNSIGNED NOT NULL,
	`wtrVoucherID` BIGINT(20) UNSIGNED NOT NULL,
	`wtrOnlinePaymentID` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`wtrOfflinePaymentID` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`wtrAmount` DOUBLE NOT NULL,
	`wtrStatus` CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'N:Active, R:Removed' COLLATE 'utf8mb4_unicode_ci',
	`wtrCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`wtrCreatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`wtrUpdatedAt` DATETIME NULL DEFAULT NULL,
	`wtrUpdatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`wtrRemovedAt` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`wtrRemovedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`wtrID`) USING BTREE,
	INDEX `FK_tbl_AAA_WalletTransaction_tbl_AAA_Wallet` (`wtrWalletID`) USING BTREE,
	CONSTRAINT `FK_tbl_AAA_WalletTransaction_tbl_AAA_Walet` FOREIGN KEY (`wtrWalletID`) REFERENCES `tbl_AAA_Wallet` (`walID`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TRIGGER trg_updatelog_tbl_AAA_Wallet AFTER UPDATE ON tbl_AAA_Wallet FOR EACH ROW BEGIN
	DECLARE Changes JSON DEFAULT JSON_OBJECT();

	IF ISNULL(OLD.walOwnerUserID) != ISNULL(NEW.walOwnerUserID) OR OLD.walOwnerUserID != NEW.walOwnerUserID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("walOwnerUserID", IF(ISNULL(OLD.walOwnerUserID), NULL, OLD.walOwnerUserID))); END IF;
	IF ISNULL(OLD.walName) != ISNULL(NEW.walName) OR OLD.walName != NEW.walName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("walName", IF(ISNULL(OLD.walName), NULL, OLD.walName))); END IF;
	IF ISNULL(OLD.walIsDefault) != ISNULL(NEW.walIsDefault) OR OLD.walIsDefault != NEW.walIsDefault THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("walIsDefault", IF(ISNULL(OLD.walIsDefault), NULL, OLD.walIsDefault))); END IF;
	IF ISNULL(OLD.walRemainedAmount) != ISNULL(NEW.walRemainedAmount) OR OLD.walRemainedAmount != NEW.walRemainedAmount THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("walRemainedAmount", IF(ISNULL(OLD.walRemainedAmount), NULL, OLD.walRemainedAmount))); END IF;
	IF ISNULL(OLD.walStatus) != ISNULL(NEW.walStatus) OR OLD.walStatus != NEW.walStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("walStatus", IF(ISNULL(OLD.walStatus), NULL, OLD.walStatus))); END IF;

	IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.walUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

		INSERT INTO tbl_SYS_ActionLogs
				SET atlBy     = NEW.walUpdatedBy
					, atlAction = "UPDATE"
					, atlTarget = "tbl_AAA_Wallet"
					, atlInfo   = JSON_OBJECT("walID", OLD.walID, "old", Changes);
	END IF;
END;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TRIGGER trg_updatelog_tbl_AAA_WalletTransaction AFTER UPDATE ON tbl_AAA_WalletTransaction FOR EACH ROW BEGIN
	DECLARE Changes JSON DEFAULT JSON_OBJECT();

	IF ISNULL(OLD.wtrWalletID) != ISNULL(NEW.wtrWalletID) OR OLD.wtrWalletID != NEW.wtrWalletID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("wtrWalletID", IF(ISNULL(OLD.wtrWalletID), NULL, OLD.wtrWalletID))); END IF;
	IF ISNULL(OLD.wtrVoucherID) != ISNULL(NEW.wtrVoucherID) OR OLD.wtrVoucherID != NEW.wtrVoucherID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("wtrVoucherID", IF(ISNULL(OLD.wtrVoucherID), NULL, OLD.wtrVoucherID))); END IF;
	IF ISNULL(OLD.wtrOnlinePaymentID) != ISNULL(NEW.wtrOnlinePaymentID) OR OLD.wtrOnlinePaymentID != NEW.wtrOnlinePaymentID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("wtrOnlinePaymentID", IF(ISNULL(OLD.wtrOnlinePaymentID), NULL, OLD.wtrOnlinePaymentID))); END IF;
	IF ISNULL(OLD.wtrOfflinePaymentID) != ISNULL(NEW.wtrOfflinePaymentID) OR OLD.wtrOfflinePaymentID != NEW.wtrOfflinePaymentID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("wtrOfflinePaymentID", IF(ISNULL(OLD.wtrOfflinePaymentID), NULL, OLD.wtrOfflinePaymentID))); END IF;
	IF ISNULL(OLD.wtrAmount) != ISNULL(NEW.wtrAmount) OR OLD.wtrAmount != NEW.wtrAmount THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("wtrAmount", IF(ISNULL(OLD.wtrAmount), NULL, OLD.wtrAmount))); END IF;
	IF ISNULL(OLD.wtrStatus) != ISNULL(NEW.wtrStatus) OR OLD.wtrStatus != NEW.wtrStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("wtrStatus", IF(ISNULL(OLD.wtrStatus), NULL, OLD.wtrStatus))); END IF;

	IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.wtrUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

		INSERT INTO tbl_SYS_ActionLogs
				SET atlBy     = NEW.wtrUpdatedBy
					, atlAction = "UPDATE"
					, atlTarget = "tbl_AAA_WalletTransaction"
					, atlInfo   = JSON_OBJECT("wtrID", OLD.wtrID, "old", Changes);
	END IF;
END;
SQLSTR
		);

		//create default wallet trigger after user inserted
		$this->execute(<<<SQLSTR
CREATE TRIGGER `trg_tbl_AAA_User_after_insert` AFTER INSERT ON `tbl_AAA_User` FOR EACH ROW BEGIN
	INSERT INTO tbl_AAA_Wallet
	SET walOwnerUserID = NEW.usrID
		, walName = 'Default'
		, walIsDefault = true
		, walRemainedAmount = 0
	;
END;
SQLSTR
		);

		$this->execute(<<<SQLSTR
INSERT INTO tbl_AAA_Wallet(
		walOwnerUserID,
		walName,
		walIsDefault,
		walRemainedAmount
	)
	SELECT usrID
			 , 'Default'
			 , true
			 , 0
	FROM tbl_AAA_User
	LEFT JOIN tbl_AAA_Wallet
	ON tbl_AAA_Wallet.walOwnerUserID = tbl_AAA_User.usrID
	AND tbl_AAA_Wallet.walIsDefault
	WHERE walID IS NULL
;
SQLSTR
		);

		//--
		$this->execute(<<<SQLSTR
CREATE TABLE `tbl_AAA_Voucher` (
	`vchID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`vchOwnerUserID` BIGINT(20) UNSIGNED NOT NULL,
	`vchType` CHAR(1) NOT NULL COMMENT 'B:Basket, W:Withdrawal, I:Income, C:Credit, T:TransferTo, F:TransferFrom, Z:Prize' COLLATE 'utf8mb4_unicode_ci',
	`vchAmount` DOUBLE UNSIGNED NOT NULL,
	`vchPaidByWallet` DOUBLE UNSIGNED NULL DEFAULT NULL,
	`vchOnlinePaid` DOUBLE UNSIGNED NULL DEFAULT NULL,
	`vchOfflinePaid` DOUBLE UNSIGNED NULL DEFAULT NULL,
	`vchTotalPaid` DOUBLE UNSIGNED NULL DEFAULT NULL,
	`vchItems` JSON NULL DEFAULT NULL,
	`vchStatus` CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'N:New, C:Canceled, E:Error, F:Finshed, R:Removed' COLLATE 'utf8mb4_unicode_ci',
	`vchCreatedAt` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
	`vchCreatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`vchUpdatedAt` DATETIME NULL DEFAULT NULL,
	`vchUpdatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`vchRemovedAt` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`vchRemovedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`vchID`) USING BTREE,
	INDEX `FK_tbl_AAA_Voucher_tbl_AAA_User` (`vchOwnerUserID`) USING BTREE,
	CONSTRAINT `FK_tbl_AAA_Voucher_tbl_AAA_User` FOREIGN KEY (`vchOwnerUserID`) REFERENCES `tbl_AAA_User` (`usrID`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;

SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TABLE `tbl_AAA_OnlinePayment` (
	`onpID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`onpUUID` VARCHAR(38) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`onpGatewayID` INT(10) UNSIGNED NOT NULL,
	`onpVoucherID` BIGINT(20) UNSIGNED NOT NULL,
	`onpAmount` DOUBLE UNSIGNED NOT NULL,
	`onpCallbackUrl` VARCHAR(1024) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`onpWalletID` BIGINT(20) UNSIGNED NOT NULL,
	`onpTrackNumber` VARCHAR(64) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`onpRRN` VARCHAR(64) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
	`onpResult` JSON NULL DEFAULT NULL,
	`onpStatus` CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'N:New, P:Pending, I:Paid, E:Error, R:Removed' COLLATE 'utf8mb4_unicode_ci',
	`onpCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`onpCreatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`onpUpdatedAt` DATETIME NULL DEFAULT NULL,
	`onpUpdatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	`onpRemovedAt` INT(10) UNSIGNED NOT NULL DEFAULT '0',
	`onpRemovedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
	PRIMARY KEY (`onpID`) USING BTREE,
	INDEX `FK_tbl_AAA_OnlinePayment_tbl_AAA_Gateway` (`onpGatewayID`) USING BTREE,
	INDEX `FK_tbl_AAA_OnlinePayment_tbl_AAA_Voucher` (`onpVoucherID`) USING BTREE,
	INDEX `FK_tbl_AAA_OnlinePayment_tbl_AAA_Wallet` (`onpWalletID`) USING BTREE,
	CONSTRAINT `FK_tbl_AAA_OnlinePayment_tbl_AAA_Gateway` FOREIGN KEY (`onpGatewayID`) REFERENCES `tbl_AAA_Gateway` (`gtwID`) ON UPDATE NO ACTION ON DELETE NO ACTION,
	CONSTRAINT `FK_tbl_AAA_OnlinePayment_tbl_AAA_Voucher` FOREIGN KEY (`onpVoucherID`) REFERENCES `tbl_AAA_Voucher` (`vchID`) ON UPDATE NO ACTION ON DELETE NO ACTION,
	CONSTRAINT `FK_tbl_AAA_OnlinePayment_tbl_AAA_Wallet` FOREIGN KEY (`onpWalletID`) REFERENCES `tbl_AAA_Wallet` (`walID`) ON UPDATE NO ACTION ON DELETE NO ACTION
)
COLLATE='utf8mb4_unicode_ci'
ENGINE=InnoDB
;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER trg_updatelog_tbl_AAA_Voucher AFTER UPDATE ON tbl_AAA_Voucher FOR EACH ROW BEGIN
	DECLARE Changes JSON DEFAULT JSON_OBJECT();

	IF ISNULL(OLD.vchOwnerUserID) != ISNULL(NEW.vchOwnerUserID) OR OLD.vchOwnerUserID != NEW.vchOwnerUserID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("vchOwnerUserID", IF(ISNULL(OLD.vchOwnerUserID), NULL, OLD.vchOwnerUserID))); END IF;
	IF ISNULL(OLD.vchType) != ISNULL(NEW.vchType) OR OLD.vchType != NEW.vchType THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("vchType", IF(ISNULL(OLD.vchType), NULL, OLD.vchType))); END IF;
	IF ISNULL(OLD.vchAmount) != ISNULL(NEW.vchAmount) OR OLD.vchAmount != NEW.vchAmount THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("vchAmount", IF(ISNULL(OLD.vchAmount), NULL, OLD.vchAmount))); END IF;
	IF ISNULL(OLD.vchPaidByWallet) != ISNULL(NEW.vchPaidByWallet) OR OLD.vchPaidByWallet != NEW.vchPaidByWallet THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("vchPaidByWallet", IF(ISNULL(OLD.vchPaidByWallet), NULL, OLD.vchPaidByWallet))); END IF;
	IF ISNULL(OLD.vchOnlinePaid) != ISNULL(NEW.vchOnlinePaid) OR OLD.vchOnlinePaid != NEW.vchOnlinePaid THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("vchOnlinePaid", IF(ISNULL(OLD.vchOnlinePaid), NULL, OLD.vchOnlinePaid))); END IF;
	IF ISNULL(OLD.vchOfflinePaid) != ISNULL(NEW.vchOfflinePaid) OR OLD.vchOfflinePaid != NEW.vchOfflinePaid THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("vchOfflinePaid", IF(ISNULL(OLD.vchOfflinePaid), NULL, OLD.vchOfflinePaid))); END IF;
	IF ISNULL(OLD.vchTotalPaid) != ISNULL(NEW.vchTotalPaid) OR OLD.vchTotalPaid != NEW.vchTotalPaid THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("vchTotalPaid", IF(ISNULL(OLD.vchTotalPaid), NULL, OLD.vchTotalPaid))); END IF;
	IF ISNULL(OLD.vchItems) != ISNULL(NEW.vchItems) OR OLD.vchItems != NEW.vchItems THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("vchItems", IF(ISNULL(OLD.vchItems), NULL, OLD.vchItems))); END IF;
	IF ISNULL(OLD.vchStatus) != ISNULL(NEW.vchStatus) OR OLD.vchStatus != NEW.vchStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("vchStatus", IF(ISNULL(OLD.vchStatus), NULL, OLD.vchStatus))); END IF;

	IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.vchUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

		INSERT INTO tbl_SYS_ActionLogs
				SET atlBy     = NEW.vchUpdatedBy
					, atlAction = "UPDATE"
					, atlTarget = "tbl_AAA_Voucher"
					, atlInfo   = JSON_OBJECT("vchID", OLD.vchID, "old", Changes);
	END IF;
END;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER trg_updatelog_tbl_AAA_OnlinePayment AFTER UPDATE ON tbl_AAA_OnlinePayment FOR EACH ROW BEGIN
	DECLARE Changes JSON DEFAULT JSON_OBJECT();

	IF ISNULL(OLD.onpUUID) != ISNULL(NEW.onpUUID) OR OLD.onpUUID != NEW.onpUUID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpUUID", IF(ISNULL(OLD.onpUUID), NULL, OLD.onpUUID))); END IF;
	IF ISNULL(OLD.onpGatewayID) != ISNULL(NEW.onpGatewayID) OR OLD.onpGatewayID != NEW.onpGatewayID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpGatewayID", IF(ISNULL(OLD.onpGatewayID), NULL, OLD.onpGatewayID))); END IF;
	IF ISNULL(OLD.onpVoucherID) != ISNULL(NEW.onpVoucherID) OR OLD.onpVoucherID != NEW.onpVoucherID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpVoucherID", IF(ISNULL(OLD.onpVoucherID), NULL, OLD.onpVoucherID))); END IF;
	IF ISNULL(OLD.onpAmount) != ISNULL(NEW.onpAmount) OR OLD.onpAmount != NEW.onpAmount THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpAmount", IF(ISNULL(OLD.onpAmount), NULL, OLD.onpAmount))); END IF;
	IF ISNULL(OLD.onpCallbackUrl) != ISNULL(NEW.onpCallbackUrl) OR OLD.onpCallbackUrl != NEW.onpCallbackUrl THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpCallbackUrl", IF(ISNULL(OLD.onpCallbackUrl), NULL, OLD.onpCallbackUrl))); END IF;
	IF ISNULL(OLD.onpWalletID) != ISNULL(NEW.onpWalletID) OR OLD.onpWalletID != NEW.onpWalletID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpWalletID", IF(ISNULL(OLD.onpWalletID), NULL, OLD.onpWalletID))); END IF;
	IF ISNULL(OLD.onpTrackNumber) != ISNULL(NEW.onpTrackNumber) OR OLD.onpTrackNumber != NEW.onpTrackNumber THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpTrackNumber", IF(ISNULL(OLD.onpTrackNumber), NULL, OLD.onpTrackNumber))); END IF;
	IF ISNULL(OLD.onpRRN) != ISNULL(NEW.onpRRN) OR OLD.onpRRN != NEW.onpRRN THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpRRN", IF(ISNULL(OLD.onpRRN), NULL, OLD.onpRRN))); END IF;
	IF ISNULL(OLD.onpResult) != ISNULL(NEW.onpResult) OR OLD.onpResult != NEW.onpResult THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpResult", IF(ISNULL(OLD.onpResult), NULL, OLD.onpResult))); END IF;
	IF ISNULL(OLD.onpStatus) != ISNULL(NEW.onpStatus) OR OLD.onpStatus != NEW.onpStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("onpStatus", IF(ISNULL(OLD.onpStatus), NULL, OLD.onpStatus))); END IF;

	IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.onpUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

		INSERT INTO tbl_SYS_ActionLogs
				SET atlBy     = NEW.onpUpdatedBy
					, atlAction = "UPDATE"
					, atlTarget = "tbl_AAA_OnlinePayment"
					, atlInfo   = JSON_OBJECT("onpID", OLD.onpID, "old", Changes);
	END IF;
END;
SQLSTR
		);

  }

	public function safeDown()
	{
		echo "m230409_184249_aaa_create_wallet_and_payments cannot be reverted.\n";
		return false;
	}

}
