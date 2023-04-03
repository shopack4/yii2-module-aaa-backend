<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m221015_160300_aaa_init extends Migration
{
  public function safeUp()
	{
    $this->execute(<<<SQLSTR
CREATE PROCEDURE `DropIndexIfExists`(
	IN i_table_name VARCHAR(128),
	IN i_index_name VARCHAR(128)
)
BEGIN
	SET @tableName = i_table_name;
	SET @indexName = i_index_name;
	SET @indexExists = 0;

	SELECT	IFNULL(tmp._cnt,0)
		INTO	@indexExists
		FROM	(
	SELECT	TABLE_NAME
			 ,  INDEX_NAME
			 ,  COUNT(*) AS _cnt
		FROM	INFORMATION_SCHEMA.STATISTICS
	 WHERE	TABLE_NAME = @tableName
		 AND	INDEX_NAME = @indexName
		 AND	TABLE_SCHEMA = DATABASE()
GROUP BY	TABLE_NAME,INDEX_NAME
					) tmp
	 WHERE	IFNULL(tmp._cnt,0) > 0
	;

	SET @query = CONCAT('DROP INDEX `', @indexName, '` ON `', @tableName, '`');
	IF (@indexExists > 0) THEN
		PREPARE stmt FROM @query;
		EXECUTE stmt;
		DEALLOCATE PREPARE stmt;
	END IF;
END ;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_GeoCountry` (
  `cntrID` smallint unsigned NOT NULL AUTO_INCREMENT,
  `cntrName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cntrCreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `cntrCreatedBy` bigint unsigned DEFAULT NULL,
  `cntrUpdatedAt` datetime DEFAULT NULL,
  `cntrUpdatedBy` bigint unsigned DEFAULT NULL,
  `cntrRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `cntrRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`cntrID`),
  KEY `cntrCreatedAt` (`cntrCreatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
    );

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_GeoState` (
  `sttID` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `sttName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sttCountryID` smallint unsigned NOT NULL,
  `sttCreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `sttCreatedBy` bigint unsigned DEFAULT NULL,
  `sttUpdatedAt` datetime DEFAULT NULL,
  `sttUpdatedBy` bigint unsigned DEFAULT NULL,
  `sttRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `sttRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`sttID`) USING BTREE,
  KEY `FK_tblGeoState_tblGeoCountry` (`sttCountryID`) USING BTREE,
  KEY `sttCreatedAt` (`sttCreatedAt`),
  CONSTRAINT `FK_tblGeoState_tblGeoCountry` FOREIGN KEY (`sttCountryID`) REFERENCES `tbl_AAA_GeoCountry` (`cntrID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_GeoCityOrVillage` (
  `ctvID` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `ctvName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ctvStateID` mediumint unsigned NOT NULL,
  `ctvType` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'C' COMMENT 'C:City, V:Village',
  `ctvCreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `ctvCreatedBy` bigint unsigned DEFAULT NULL,
  `ctvUpdatedAt` datetime DEFAULT NULL,
  `ctvUpdatedBy` bigint unsigned DEFAULT NULL,
  `ctvRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `ctvRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`ctvID`) USING BTREE,
  KEY `FK_tblGeoCityOrVillage_tblGeoState` (`ctvStateID`) USING BTREE,
  KEY `ctvCreatedAt` (`ctvCreatedAt`),
  CONSTRAINT `FK_tblGeoCityOrVillage_tblGeoState` FOREIGN KEY (`ctvStateID`) REFERENCES `tbl_AAA_GeoState` (`sttID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_GeoTown` (
  `twnID` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `twnName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `twnCityID` mediumint unsigned NOT NULL,
  `twnCreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `twnCreatedBy` bigint unsigned DEFAULT NULL,
  `twnUpdatedAt` datetime DEFAULT NULL,
  `twnUpdatedBy` bigint unsigned DEFAULT NULL,
  `twnRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `twnRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`twnID`) USING BTREE,
  KEY `FK_tblGeoTown_tblGeoCityOrVillage` (`twnCityID`) USING BTREE,
  KEY `twnCreatedAt` (`twnCreatedAt`),
  CONSTRAINT `FK_tblGeoTown_tblGeoCityOrVillage` FOREIGN KEY (`twnCityID`) REFERENCES `tbl_AAA_GeoCityOrVillage` (`ctvID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_User` (
  `usrID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `usrGender` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'M:Male, F:Female, N:Not Set',
  `usrFirstName` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usrFirstName_en` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usrLastName` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usrLastName_en` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usrEmail` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usrEmailApprovedAt` datetime DEFAULT NULL,
  `usrMobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usrMobileApprovedAt` datetime DEFAULT NULL,
  `usrSSID` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usrRoleID` int unsigned DEFAULT NULL,
  `usrPrivs` JSON DEFAULT NULL,
  `usrPasswordHash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usrPasswordCreatedAt` datetime DEFAULT NULL,
  `usrStatus` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A:Active, D:Disable, R:Removed',
  `usrCreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usrCreatedBy` bigint unsigned DEFAULT NULL,
  `usrUpdatedAt` datetime DEFAULT NULL,
  `usrUpdatedBy` bigint unsigned DEFAULT NULL,
  `usrRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `usrRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`usrID`) USING BTREE,
  UNIQUE KEY `usrEmail_usrRemovedAt` (`usrEmail`,`usrRemovedAt`),
  UNIQUE KEY `usrMobile_usrRemovedAt` (`usrMobile`,`usrRemovedAt`),
  UNIQUE KEY `usrSSID_usrRemovedAt` (`usrSSID`,`usrRemovedAt`),
  KEY `FK_tbl_AAA_User_tbl_AAA_User_remover` (`usrRemovedBy`),
  KEY `FK_tbl_AAA_User_tbl_AAA_User_creator` (`usrCreatedBy`) USING BTREE,
  KEY `FK_tbl_AAA_User_tbl_AAA_User_modifier` (`usrUpdatedBy`) USING BTREE,
  CONSTRAINT `FK_tbl_AAA_User_tbl_AAA_User_creator` FOREIGN KEY (`usrCreatedBy`) REFERENCES `tbl_AAA_User` (`usrID`),
  CONSTRAINT `FK_tbl_AAA_User_tbl_AAA_User_modifier` FOREIGN KEY (`usrUpdatedBy`) REFERENCES `tbl_AAA_User` (`usrID`),
  CONSTRAINT `FK_tbl_AAA_User_tbl_AAA_User_remover` FOREIGN KEY (`usrRemovedBy`) REFERENCES `tbl_AAA_User` (`usrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->alterColumn('{{%AAA_User}}', 'usrPrivs', $this->json());

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_UserExtraInfo` (
  `uexUserID` bigint unsigned NOT NULL,
  `uexBirthDate` date DEFAULT NULL,
  `uexCountryID` smallint unsigned DEFAULT NULL,
  `uexStateID` mediumint unsigned DEFAULT NULL,
  `uexCityOrVillageID` mediumint unsigned DEFAULT NULL,
  `uexTownID` mediumint unsigned DEFAULT NULL,
  `uexHomeAddress` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uexZipCode` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uexImage` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uexCreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `uexCreatedBy` bigint unsigned DEFAULT NULL,
  `uexUpdatedAt` datetime DEFAULT NULL,
  `uexUpdatedBy` bigint unsigned DEFAULT NULL,
  `uexRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `uexRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`uexUserID`) USING BTREE,
  KEY `FK_tbl_AAA_UserExtraInfo_tbl_AAA_GeoCountry` (`uexCountryID`) USING BTREE,
  KEY `FK_tbl_AAA_UserExtraInfo_tbl_AAA_GeoState` (`uexStateID`) USING BTREE,
  KEY `FK_tbl_AAA_UserExtraInfo_tbl_AAA_GeoCityOrVillage` (`uexCityOrVillageID`) USING BTREE,
  KEY `FK_tbl_AAA_UserExtraInfo_tbl_AAA_GeoTown` (`uexTownID`) USING BTREE,
  KEY `uexCreatedAt` (`uexCreatedAt`),
  CONSTRAINT `FK_tbl_AAA_UserExtraInfo_tbl_AAA_GeoCityOrVillage` FOREIGN KEY (`uexCityOrVillageID`) REFERENCES `tbl_AAA_GeoCityOrVillage` (`ctvID`),
  CONSTRAINT `FK_tbl_AAA_UserExtraInfo_tbl_AAA_GeoCountry` FOREIGN KEY (`uexCountryID`) REFERENCES `tbl_AAA_GeoCountry` (`cntrID`),
  CONSTRAINT `FK_tbl_AAA_UserExtraInfo_tbl_AAA_GeoState` FOREIGN KEY (`uexStateID`) REFERENCES `tbl_AAA_GeoState` (`sttID`),
  CONSTRAINT `FK_tbl_AAA_UserExtraInfo_tbl_AAA_GeoTown` FOREIGN KEY (`uexTownID`) REFERENCES `tbl_AAA_GeoTown` (`twnID`),
  CONSTRAINT `FK_tbl_AAA_UserExtraInfo_tbl_AAA_User` FOREIGN KEY (`uexUserID`) REFERENCES `tbl_AAA_User` (`usrID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_Role` (
  `rolID` int unsigned NOT NULL AUTO_INCREMENT,
  `rolName` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rolParentID` int unsigned DEFAULT NULL,
  `rolPrivs` JSON NOT NULL,
  `rolCreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rolCreatedBy` bigint unsigned DEFAULT NULL,
  `rolUpdatedAt` datetime DEFAULT NULL,
  `rolUpdatedBy` bigint unsigned DEFAULT NULL,
  `rolRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `rolRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`rolID`),
  KEY `FK_tbl_AAA_Role_tbl_AAA_User_creator` (`rolCreatedBy`),
  KEY `FK_tbl_AAA_Role_tbl_AAA_User_modifier` (`rolUpdatedBy`),
  CONSTRAINT `FK_tbl_AAA_Role_tbl_AAA_User_creator` FOREIGN KEY (`rolCreatedBy`) REFERENCES `tbl_AAA_User` (`usrID`),
  CONSTRAINT `FK_tbl_AAA_Role_tbl_AAA_User_modifier` FOREIGN KEY (`rolUpdatedBy`) REFERENCES `tbl_AAA_User` (`usrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->alterColumn('{{%AAA_Role}}', 'rolPrivs', $this->json());

    $this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_User`
  ADD CONSTRAINT `FK_tbl_AAA_User_tbl_AAA_Role` FOREIGN KEY (`usrRoleID`) REFERENCES `tbl_AAA_Role` (`rolID`) ON UPDATE NO ACTION ON DELETE NO ACTION;
SQLSTR
    );

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_ApprovalRequest` (
  `aprID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `aprUserID` bigint unsigned DEFAULT NULL,
  `aprKeyType` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'E:Email, M:Mobile',
  `aprKey` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aprCode` varchar(48) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aprLastRequestAt` datetime NOT NULL,
  `aprExpireAt` datetime NOT NULL,
  `aprSentAt` datetime DEFAULT NULL,
  `aprApplyAt` datetime DEFAULT NULL,
  `aprStatus` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT 'N:New, S:Sent, A:Applied, E:Expired',
  `aprCreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aprCreatedBy` bigint unsigned DEFAULT NULL,
  `aprUpdatedAt` datetime DEFAULT NULL,
  `aprUpdatedBy` bigint unsigned DEFAULT NULL,
  `aprRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `aprRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`aprID`),
  KEY `FK_tbl_AAA_ApprovalRequest_tbl_AAA_User` (`aprUserID`),
  CONSTRAINT `FK_tbl_AAA_ApprovalRequest_tbl_AAA_User` FOREIGN KEY (`aprUserID`) REFERENCES `tbl_AAA_User` (`usrID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_ForgotPasswordRequest` (
  `fprID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fprUserID` bigint unsigned NOT NULL,
  `fprRequestedBy` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'E:Email, M:Mobile',
  `fprCode` varchar(48) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fprLastRequestAt` datetime NOT NULL,
  `fprExpireAt` datetime NOT NULL,
  `fprSentAt` datetime DEFAULT NULL,
  `fprApplyAt` datetime DEFAULT NULL,
  `fprStatus` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT 'N:New, S:Sent, A:Applied, E:Expired',
  `fprCreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fprCreatedBy` bigint unsigned DEFAULT NULL,
  `fprUpdatedAt` datetime DEFAULT NULL,
  `fprUpdatedBy` bigint unsigned DEFAULT NULL,
  `fprRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `fprRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`fprID`),
  KEY `FK_tbl_AAA_ForgotPasswordRequest_tbl_AAA_User` (`fprUserID`),
  CONSTRAINT `FK_tbl_AAA_ForgotPasswordRequest_tbl_AAA_User` FOREIGN KEY (`fprUserID`) REFERENCES `tbl_AAA_User` (`usrID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_AlertType` (
  `altID` int unsigned NOT NULL AUTO_INCREMENT,
  `altKey` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `altType` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'E:Email, M:Mobile',
  `altBody` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `altCreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `altCreatedBy` bigint unsigned DEFAULT NULL,
  `altUpdatedAt` datetime DEFAULT NULL,
  `altUpdatedBy` bigint unsigned DEFAULT NULL,
  `altRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `altRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`altID`),
  UNIQUE KEY `altKey` (`altKey`),
  KEY `altCreatedAt` (`altCreatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_Alert` (
  `alrID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alrUserID` bigint unsigned DEFAULT NULL,
  `alrApprovalRequestID` bigint unsigned DEFAULT NULL,
  `alrForgotPasswordRequestID` bigint unsigned DEFAULT NULL,
  `alrTypeKey` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `alrTarget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alrInfo` JSON NOT NULL,
  `alrLockedAt` datetime DEFAULT NULL,
  `alrLockedBy` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alrLastTryAt` datetime DEFAULT NULL,
  `alrSentAt` datetime DEFAULT NULL,
  `alrResult` JSON DEFAULT NULL,
  `alrStatus` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N' COMMENT 'N:New, P:Processing, S:Sent, E:Error, R:Removed',
  `alrCreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alrCreatedBy` bigint unsigned DEFAULT NULL,
  `alrUpdatedAt` datetime DEFAULT NULL,
  `alrUpdatedBy` bigint unsigned DEFAULT NULL,
  `alrRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `alrRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`alrID`),
  KEY `FK_tbl_AAA_Alert_tbl_AAA_User` (`alrUserID`),
  KEY `FK_tbl_AAA_Alert_tbl_AAA_User_creator` (`alrCreatedBy`),
  KEY `FK_tbl_AAA_Alert_tbl_AAA_ApprovalRequest` (`alrApprovalRequestID`),
  KEY `FK_tbl_AAA_Alert_tbl_AAA_ForgotPasswordRequest` (`alrForgotPasswordRequestID`),
  CONSTRAINT `FK_tbl_AAA_Alert_tbl_AAA_ApprovalRequest` FOREIGN KEY (`alrApprovalRequestID`) REFERENCES `tbl_AAA_ApprovalRequest` (`aprID`) ON DELETE CASCADE,
  CONSTRAINT `FK_tbl_AAA_Alert_tbl_AAA_ForgotPasswordRequest` FOREIGN KEY (`alrForgotPasswordRequestID`) REFERENCES `tbl_AAA_ForgotPasswordRequest` (`fprID`) ON DELETE CASCADE,
  CONSTRAINT `FK_tbl_AAA_Alert_tbl_AAA_User` FOREIGN KEY (`alrUserID`) REFERENCES `tbl_AAA_User` (`usrID`) ON DELETE CASCADE,
  CONSTRAINT `FK_tbl_AAA_Alert_tbl_AAA_User_creator` FOREIGN KEY (`alrCreatedBy`) REFERENCES `tbl_AAA_User` (`usrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->alterColumn('{{%AAA_Alert}}', 'alrInfo', $this->json());
    $this->alterColumn('{{%AAA_Alert}}', 'alrResult', $this->json());

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_Gateway` (
  `gtwID` int unsigned NOT NULL AUTO_INCREMENT,
  `gtwName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gtwKey` varchar(48) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gtwPluginType` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gtwPluginName` varchar(48) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gtwPluginParameters` JSON NOT NULL,
  `gtwStatus` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A' COMMENT 'A:Active, D:Disable, R:Removed',
  `gtwCreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `gtwCreatedBy` bigint unsigned DEFAULT NULL,
  `gtwUpdatedAt` datetime DEFAULT NULL,
  `gtwUpdatedBy` bigint unsigned DEFAULT NULL,
  `gtwRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `gtwRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`gtwID`) USING BTREE,
  UNIQUE KEY `gtwKey_gtwRemovedAt` (`gtwKey`,`gtwRemovedAt`) USING BTREE,
  KEY `FK_tbl_AAA_Gateway_tbl_AAA_User_creator` (`gtwCreatedBy`) USING BTREE,
  KEY `FK_tbl_AAA_Gateway_tbl_AAA_User_modifier` (`gtwUpdatedBy`) USING BTREE,
  KEY `FK_tbl_AAA_Gateway_tbl_AAA_User_remover` (`gtwRemovedBy`) USING BTREE,
  CONSTRAINT `FK_tbl_AAA_Gateway_tbl_AAA_User_creator` FOREIGN KEY (`gtwCreatedBy`) REFERENCES `tbl_AAA_User` (`usrID`),
  CONSTRAINT `FK_tbl_AAA_Gateway_tbl_AAA_User_modifier` FOREIGN KEY (`gtwUpdatedBy`) REFERENCES `tbl_AAA_User` (`usrID`),
  CONSTRAINT `FK_tbl_AAA_Gateway_tbl_AAA_User_remover` FOREIGN KEY (`gtwRemovedBy`) REFERENCES `tbl_AAA_User` (`usrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->alterColumn('{{%AAA_Gateway}}', 'gtwPluginParameters', $this->json());

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_AAA_Session` (
  `ssnID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ssnUserID` bigint unsigned NOT NULL,
  `ssnJWT` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ssnJWTMD5` varchar(32) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (md5(`ssnJWT`)) VIRTUAL,
  `ssnStatus` char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'P' COMMENT 'P:Pending, A:Active, R:Removed',
  `ssnExpireAt` datetime DEFAULT NULL,
  `ssnCreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ssnCreatedBy` bigint unsigned DEFAULT NULL,
  `ssnUpdatedAt` datetime DEFAULT NULL,
  `ssnUpdatedBy` bigint unsigned DEFAULT NULL,
  `ssnRemovedAt` int unsigned NOT NULL DEFAULT '0',
  `ssnRemovedBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`ssnID`),
  UNIQUE KEY `ssnMd5JWT` (`ssnJWTMD5`) USING BTREE,
  KEY `FK_tblSession_tblUser_modifier` (`ssnUpdatedBy`),
  KEY `FK_tblSession_tblUser` (`ssnUserID`) USING BTREE,
  CONSTRAINT `FK_tblSession_tblUser` FOREIGN KEY (`ssnUserID`) REFERENCES `tbl_AAA_User` (`usrID`) ON DELETE CASCADE,
  CONSTRAINT `FK_tblSession_tblUser_modifier` FOREIGN KEY (`ssnUpdatedBy`) REFERENCES `tbl_AAA_User` (`usrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE IF NOT EXISTS `tbl_SYS_ActionLogs` (
  `atlID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `atlAction` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `atlTarget` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atlInfo` JSON DEFAULT NULL,
  `atlAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atlBy` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`atlID`),
  KEY `atlAt` (`atlAt`),
  KEY `FK_tbl_SYS_ActionLogs_tbl_AAA_User` (`atlBy`),
  KEY `atlType` (`atlAction`) USING BTREE,
  CONSTRAINT `FK_tbl_SYS_ActionLogs_tbl_AAA_User` FOREIGN KEY (`atlBy`) REFERENCES `tbl_AAA_User` (`usrID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQLSTR
		);

    $this->alterColumn('{{%SYS_ActionLogs}}', 'atlInfo', $this->json());

		$this->execute(<<<SQLSTR
CREATE PROCEDURE `spAutoUpdateTableTriggerAndCols`()
BEGIN
  DECLARE vTableName VARCHAR(200);
  DECLARE vFinished INTEGER DEFAULT 0;
  DECLARE vQueryStr LONGTEXT DEFAULT '';
  DECLARE vTempQueryStr TEXT;

  DECLARE curTables CURSOR FOR
    SELECT TABLE_NAME
      FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME != 'tbl_SYS_ActionLogs'
  ORDER BY TABLE_NAME
  ;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET vFinished = 1;

  OPEN curTables;

  RunScope: LOOP
    FETCH curTables
     INTO vTableName
    ;

    IF vFinished = 1 THEN
      LEAVE RunScope;
    END IF;

		SET vTempQueryStr = '';
    CALL spUpdateTableTriggerAndCols(DATABASE(), vTableName, TRUE, vTempQueryStr);

    SET vQueryStr = CONCAT(vQueryStr, '\n\n', vTempQueryStr);

  END LOOP RunScope;

  CLOSE curTables;

	SELECT vQueryStr;

END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE PROCEDURE `spFindTablePrefix`(
	IN `iTable` VARCHAR(128),
	OUT `oPrefix` VARCHAR(64)
)
Proc: BEGIN
  DECLARE vColumnCount INTEGER;
  DECLARE vFirstColumnName VARCHAR(200);
  DECLARE vLastColumnName VARCHAR(200);
  DECLARE vMinColumnLen INTEGER;
  DECLARE vi INTEGER;

  SET oPrefix = NULL;

  SELECT COUNT(*)
    INTO vColumnCount
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = DATABASE()
     AND information_schema.COLUMNS.TABLE_NAME = iTable
  ;

  IF vColumnCount = 0 THEN
    LEAVE Proc;
  END IF;

  SELECT COLUMN_NAME
    INTO vFirstColumnName
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = DATABASE()
     AND information_schema.COLUMNS.TABLE_NAME = iTable
ORDER BY COLUMN_NAME
   LIMIT 1,1
  ;

  IF vColumnCount = 1 THEN
    SET oPrefix = vFirstColumnName;
    LEAVE Proc;
  END IF;

  SELECT COLUMN_NAME
    INTO vLastColumnName
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = DATABASE()
     AND information_schema.COLUMNS.TABLE_NAME = iTable
ORDER BY COLUMN_NAME DESC
   LIMIT 1,1
  ;

  SET vMinColumnLen = LEAST(LENGTH(vFirstColumnName), LENGTH(vLastColumnName));

  SET vi = 0;
  WHILE vi < vMinColumnLen AND SUBSTRING(vFirstColumnName, vi+1, 1) = SUBSTRING(vLastColumnName, vi+1, 1) DO
    SET vi = vi + 1;
  END WHILE;

  IF vi > 0 THEN
    SET oPrefix = SUBSTRING(vFirstColumnName, 1, vi);
  END IF;

END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE PROCEDURE `spUpdateTableTriggerAndCols`(
	IN `iSchema` VARCHAR(50),
	IN `iTable` VARCHAR(50),
	IN `iFK` BOOLEAN,
	OUT `oQueryStr` TEXT
)
Proc: BEGIN
  -- DECLARE QueryStr VARCHAR(21000) DEFAULT '';
  DECLARE TempQueryStr VARCHAR(5000) DEFAULT 0;
  DECLARE TriggerName VARCHAR(100);
	DECLARE vPrefix VARCHAR(50);
  DECLARE vi INTEGER DEFAULT 0;

	SET oQueryStr = '';

	CALL spFindTablePrefix(iTable, vPrefix);
	IF vPrefix IS NULL OR vPrefix = '' THEN
		LEAVE Proc;
	END IF;

  -- CreatedAt
  SELECT COUNT(1)
    INTO vi
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = iSchema
     AND information_schema.COLUMNS.TABLE_NAME = iTable
     AND information_schema.COLUMNS.COLUMN_NAME = CONCAT(vPrefix, 'CreatedAt');

  IF vi = 0 THEN
    SET oQueryStr = CONCAT(oQueryStr, 'ALTER TABLE ', iTable, '
  ADD COLUMN ', vPrefix, 'CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  ADD INDEX  ', vPrefix, 'CreatedAt (', vPrefix, 'CreatedAt)
;

');
  END IF;

  -- CreatedBy
  SELECT COUNT(1)
    INTO vi
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = iSchema
     AND information_schema.COLUMNS.TABLE_NAME = iTable
     AND information_schema.COLUMNS.COLUMN_NAME = CONCAT(vPrefix, 'CreatedBy');

  IF vi = 0 THEN
    SET oQueryStr = CONCAT(oQueryStr, 'ALTER TABLE ', iTable, '
  ADD COLUMN ', vPrefix, 'CreatedBy BIGINT UNSIGNED NULL DEFAULT NULL AFTER ', vPrefix, 'CreatedAt
;

');
  END IF;

  -- UpdatedAt
  SELECT COUNT(1)
    INTO vi
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = iSchema
     AND information_schema.COLUMNS.TABLE_NAME = iTable
     AND information_schema.COLUMNS.COLUMN_NAME = CONCAT(vPrefix, 'UpdatedAt');

  IF vi = 0 THEN
    SET oQueryStr = CONCAT(oQueryStr, 'ALTER TABLE ', iTable, '
  ADD COLUMN ', vPrefix, 'UpdatedAt DATETIME NULL DEFAULT NULL AFTER ', vPrefix, 'CreatedBy
;

');
  END IF;

  -- UpdatedBy
  SELECT COUNT(1)
    INTO vi
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = iSchema
     AND information_schema.COLUMNS.TABLE_NAME = iTable
     AND information_schema.COLUMNS.COLUMN_NAME = CONCAT(vPrefix, 'UpdatedBy');

  IF vi = 0 THEN
    SET oQueryStr = CONCAT(oQueryStr, 'ALTER TABLE ', iTable, '
  ADD COLUMN ', vPrefix, 'UpdatedBy BIGINT UNSIGNED NULL DEFAULT NULL AFTER ', vPrefix, 'UpdatedAt
;

');
  END IF;

  -- RemovedAt
  SELECT COUNT(1)
    INTO vi
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = iSchema
     AND information_schema.COLUMNS.TABLE_NAME = iTable
     AND information_schema.COLUMNS.COLUMN_NAME = CONCAT(vPrefix, 'RemovedAt');

  IF vi = 0 THEN
    SET oQueryStr = CONCAT(oQueryStr, 'ALTER TABLE ', iTable, '
  ADD COLUMN ', vPrefix, 'RemovedAt INT UNSIGNED NOT NULL DEFAULT 0 AFTER ', vPrefix, 'UpdatedBy
;

');
  END IF;

  -- RemovedBy
  SELECT COUNT(1)
    INTO vi
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = iSchema
     AND information_schema.COLUMNS.TABLE_NAME = iTable
     AND information_schema.COLUMNS.COLUMN_NAME = CONCAT(vPrefix, 'RemovedBy');

  IF vi = 0 THEN
    SET oQueryStr = CONCAT(oQueryStr, 'ALTER TABLE ', iTable, '
  ADD COLUMN ', vPrefix, 'RemovedBy BIGINT UNSIGNED NULL DEFAULT NULL AFTER ', vPrefix, 'RemovedAt
;

');
  END IF;

--    IF (iFK = 1) THEN
--      SET oQueryStr = CONCAT(oQueryStr, '
--        ADD CONSTRAINT FKA_', iTable,'_tbl_AAA_User_creator FOREIGN KEY (', vPrefix, 'CreatedBy) REFERENCES tbl_AAA_User (usrID) ON UPDATE CASCADE ON DELETE RESTRICT,
--        ADD CONSTRAINT FKA_', iTable,'_tbl_AAA_User_modifier FOREIGN KEY (', vPrefix, 'UpdatedBy) REFERENCES tbl_AAA_User (usrID) ON UPDATE CASCADE ON DELETE RESTRICT
--        ');
--    ELSE
--      SET oQueryStr = CONCAT(oQueryStr, '
--        ADD INDEX ', vPrefix, 'CreatedBy (', vPrefix, 'CreatedBy) ,
--        ADD INDEX ', vPrefix, 'UpdatedBy (', vPrefix, 'UpdatedBy)
--        ');
--    END IF;
--
--    SET @SQL := oQueryStr;
--    PREPARE stmt FROM @SQL;
--    EXECUTE stmt;
--    DEALLOCATE PREPARE stmt;
--  ELSEIF oQueryStr < 3 THEN
--    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Some essential columns not found';
--  END IF;

  SET TriggerName = CONCAT('trg_updatelog_', iTable);
  SET oQueryStr = CONCAT(oQueryStr, 'DROP TRIGGER IF EXISTS ', TriggerName, ';
DELIMITER ;;
CREATE TRIGGER ', TriggerName, ' AFTER UPDATE ON ', iTable, ' FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();\n\n');

  SET SESSION group_concat_max_len = 1000000;

  SELECT GROUP_CONCAT(CONCAT('  IF ISNULL(OLD.', COLUMN_NAME, ') != ISNULL(NEW.', COLUMN_NAME, ')',
          ' OR OLD.', COLUMN_NAME, ' != NEW.', COLUMN_NAME,
					' THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("', COLUMN_NAME, '", IF(ISNULL(OLD.', COLUMN_NAME, '), NULL, OLD.', COLUMN_NAME, ')));',
					' END IF;')
          SEPARATOR '\n') INTO TempQueryStr
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = iSchema
     AND information_schema.COLUMNS.TABLE_NAME = iTable
     AND information_schema.COLUMNS.COLUMN_KEY != 'PRI'
     AND information_schema.COLUMNS.COLUMN_NAME NOT IN (
          CONCAT(vPrefix, 'CreatedAt'),
          CONCAT(vPrefix, 'CreatedBy'),
          CONCAT(vPrefix, 'UpdatedAt'),
          CONCAT(vPrefix, 'UpdatedBy'),
          CONCAT(vPrefix, 'RemovedAt'),
          CONCAT(vPrefix, 'RemovedBy')
         )
ORDER BY ORDINAL_POSITION ASC;

  SET oQueryStr = CONCAT(oQueryStr, TempQueryStr, '\n');

  SELECT GROUP_CONCAT(CONCAT('"', COLUMN_NAME, '", OLD.', COLUMN_NAME) SEPARATOR ', ')
    INTO TempQueryStr
    FROM information_schema.COLUMNS
   WHERE information_schema.COLUMNS.TABLE_SCHEMA = iSchema
     AND information_schema.COLUMNS.TABLE_NAME = iTable
     AND information_schema.COLUMNS.COLUMN_KEY = 'PRI'
   ORDER BY ORDINAL_POSITION ASC;

  SET oQueryStr = CONCAT(oQueryStr, '
  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.', CONCAT(vPrefix, 'UpdatedBy'), ') THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.', CONCAT(vPrefix, 'UpdatedBy'), '
         , atlAction = "UPDATE"
         , atlTarget = "', iTable, '"
         , atlInfo   = JSON_OBJECT(', TempQueryStr, ', "old", Changes);
  END IF;
END;;
DELIMITER ;');

--  SELECT oQueryStr;

--  SET @SQL := oQueryStr;
--  PREPARE stmt FROM @SQL;
--  EXECUTE stmt;
--  DEALLOCATE PREPARE stmt;

END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_Alert` AFTER UPDATE ON `tbl_AAA_Alert` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.alrUserID) != ISNULL(NEW.alrUserID) OR OLD.alrUserID != NEW.alrUserID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrUserID", IF(ISNULL(OLD.alrUserID), NULL, OLD.alrUserID))); END IF;
  IF ISNULL(OLD.alrApprovalRequestID) != ISNULL(NEW.alrApprovalRequestID) OR OLD.alrApprovalRequestID != NEW.alrApprovalRequestID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrApprovalRequestID", IF(ISNULL(OLD.alrApprovalRequestID), NULL, OLD.alrApprovalRequestID))); END IF;
  IF ISNULL(OLD.alrForgotPasswordRequestID) != ISNULL(NEW.alrForgotPasswordRequestID) OR OLD.alrForgotPasswordRequestID != NEW.alrForgotPasswordRequestID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrForgotPasswordRequestID", IF(ISNULL(OLD.alrForgotPasswordRequestID), NULL, OLD.alrForgotPasswordRequestID))); END IF;
  IF ISNULL(OLD.alrTypeKey) != ISNULL(NEW.alrTypeKey) OR OLD.alrTypeKey != NEW.alrTypeKey THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrTypeKey", IF(ISNULL(OLD.alrTypeKey), NULL, OLD.alrTypeKey))); END IF;
  IF ISNULL(OLD.alrTarget) != ISNULL(NEW.alrTarget) OR OLD.alrTarget != NEW.alrTarget THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrTarget", IF(ISNULL(OLD.alrTarget), NULL, OLD.alrTarget))); END IF;
  IF ISNULL(OLD.alrInfo) != ISNULL(NEW.alrInfo) OR OLD.alrInfo != NEW.alrInfo THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrInfo", IF(ISNULL(OLD.alrInfo), NULL, OLD.alrInfo))); END IF;
  IF ISNULL(OLD.alrLockedAt) != ISNULL(NEW.alrLockedAt) OR OLD.alrLockedAt != NEW.alrLockedAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrLockedAt", IF(ISNULL(OLD.alrLockedAt), NULL, OLD.alrLockedAt))); END IF;
  IF ISNULL(OLD.alrLockedBy) != ISNULL(NEW.alrLockedBy) OR OLD.alrLockedBy != NEW.alrLockedBy THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrLockedBy", IF(ISNULL(OLD.alrLockedBy), NULL, OLD.alrLockedBy))); END IF;
  IF ISNULL(OLD.alrLastTryAt) != ISNULL(NEW.alrLastTryAt) OR OLD.alrLastTryAt != NEW.alrLastTryAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrLastTryAt", IF(ISNULL(OLD.alrLastTryAt), NULL, OLD.alrLastTryAt))); END IF;
  IF ISNULL(OLD.alrSentAt) != ISNULL(NEW.alrSentAt) OR OLD.alrSentAt != NEW.alrSentAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrSentAt", IF(ISNULL(OLD.alrSentAt), NULL, OLD.alrSentAt))); END IF;
  IF ISNULL(OLD.alrResult) != ISNULL(NEW.alrResult) OR OLD.alrResult != NEW.alrResult THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrResult", IF(ISNULL(OLD.alrResult), NULL, OLD.alrResult))); END IF;
  IF ISNULL(OLD.alrStatus) != ISNULL(NEW.alrStatus) OR OLD.alrStatus != NEW.alrStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("alrStatus", IF(ISNULL(OLD.alrStatus), NULL, OLD.alrStatus))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.alrUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.alrUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_Alert"
         , atlInfo   = JSON_OBJECT("alrID", OLD.alrID, "old", Changes);
  END IF;
END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_AlertType` AFTER UPDATE ON `tbl_AAA_AlertType` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.altKey) != ISNULL(NEW.altKey) OR OLD.altKey != NEW.altKey THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("altKey", IF(ISNULL(OLD.altKey), NULL, OLD.altKey))); END IF;
  IF ISNULL(OLD.altType) != ISNULL(NEW.altType) OR OLD.altType != NEW.altType THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("altType", IF(ISNULL(OLD.altType), NULL, OLD.altType))); END IF;
  IF ISNULL(OLD.altBody) != ISNULL(NEW.altBody) OR OLD.altBody != NEW.altBody THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("altBody", IF(ISNULL(OLD.altBody), NULL, OLD.altBody))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.altUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.altUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_AlertType"
         , atlInfo   = JSON_OBJECT("altID", OLD.altID, "old", Changes);
  END IF;
END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_ApprovalRequest` AFTER UPDATE ON `tbl_AAA_ApprovalRequest` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.aprUserID) != ISNULL(NEW.aprUserID) OR OLD.aprUserID != NEW.aprUserID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("aprUserID", IF(ISNULL(OLD.aprUserID), NULL, OLD.aprUserID))); END IF;
  IF ISNULL(OLD.aprKeyType) != ISNULL(NEW.aprKeyType) OR OLD.aprKeyType != NEW.aprKeyType THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("aprKeyType", IF(ISNULL(OLD.aprKeyType), NULL, OLD.aprKeyType))); END IF;
  IF ISNULL(OLD.aprKey) != ISNULL(NEW.aprKey) OR OLD.aprKey != NEW.aprKey THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("aprKey", IF(ISNULL(OLD.aprKey), NULL, OLD.aprKey))); END IF;
  IF ISNULL(OLD.aprCode) != ISNULL(NEW.aprCode) OR OLD.aprCode != NEW.aprCode THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("aprCode", IF(ISNULL(OLD.aprCode), NULL, OLD.aprCode))); END IF;
  IF ISNULL(OLD.aprLastRequestAt) != ISNULL(NEW.aprLastRequestAt) OR OLD.aprLastRequestAt != NEW.aprLastRequestAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("aprLastRequestAt", IF(ISNULL(OLD.aprLastRequestAt), NULL, OLD.aprLastRequestAt))); END IF;
  IF ISNULL(OLD.aprExpireAt) != ISNULL(NEW.aprExpireAt) OR OLD.aprExpireAt != NEW.aprExpireAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("aprExpireAt", IF(ISNULL(OLD.aprExpireAt), NULL, OLD.aprExpireAt))); END IF;
  IF ISNULL(OLD.aprSentAt) != ISNULL(NEW.aprSentAt) OR OLD.aprSentAt != NEW.aprSentAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("aprSentAt", IF(ISNULL(OLD.aprSentAt), NULL, OLD.aprSentAt))); END IF;
  IF ISNULL(OLD.aprApplyAt) != ISNULL(NEW.aprApplyAt) OR OLD.aprApplyAt != NEW.aprApplyAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("aprApplyAt", IF(ISNULL(OLD.aprApplyAt), NULL, OLD.aprApplyAt))); END IF;
  IF ISNULL(OLD.aprStatus) != ISNULL(NEW.aprStatus) OR OLD.aprStatus != NEW.aprStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("aprStatus", IF(ISNULL(OLD.aprStatus), NULL, OLD.aprStatus))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.aprUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.aprUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_ApprovalRequest"
         , atlInfo   = JSON_OBJECT("aprID", OLD.aprID, "old", Changes);
  END IF;
END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_ForgotPasswordRequest` AFTER UPDATE ON `tbl_AAA_ForgotPasswordRequest` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.fprUserID) != ISNULL(NEW.fprUserID) OR OLD.fprUserID != NEW.fprUserID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("fprUserID", IF(ISNULL(OLD.fprUserID), NULL, OLD.fprUserID))); END IF;
  IF ISNULL(OLD.fprRequestedBy) != ISNULL(NEW.fprRequestedBy) OR OLD.fprRequestedBy != NEW.fprRequestedBy THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("fprRequestedBy", IF(ISNULL(OLD.fprRequestedBy), NULL, OLD.fprRequestedBy))); END IF;
  IF ISNULL(OLD.fprCode) != ISNULL(NEW.fprCode) OR OLD.fprCode != NEW.fprCode THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("fprCode", IF(ISNULL(OLD.fprCode), NULL, OLD.fprCode))); END IF;
  IF ISNULL(OLD.fprLastRequestAt) != ISNULL(NEW.fprLastRequestAt) OR OLD.fprLastRequestAt != NEW.fprLastRequestAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("fprLastRequestAt", IF(ISNULL(OLD.fprLastRequestAt), NULL, OLD.fprLastRequestAt))); END IF;
  IF ISNULL(OLD.fprExpireAt) != ISNULL(NEW.fprExpireAt) OR OLD.fprExpireAt != NEW.fprExpireAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("fprExpireAt", IF(ISNULL(OLD.fprExpireAt), NULL, OLD.fprExpireAt))); END IF;
  IF ISNULL(OLD.fprSentAt) != ISNULL(NEW.fprSentAt) OR OLD.fprSentAt != NEW.fprSentAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("fprSentAt", IF(ISNULL(OLD.fprSentAt), NULL, OLD.fprSentAt))); END IF;
  IF ISNULL(OLD.fprApplyAt) != ISNULL(NEW.fprApplyAt) OR OLD.fprApplyAt != NEW.fprApplyAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("fprApplyAt", IF(ISNULL(OLD.fprApplyAt), NULL, OLD.fprApplyAt))); END IF;
  IF ISNULL(OLD.fprStatus) != ISNULL(NEW.fprStatus) OR OLD.fprStatus != NEW.fprStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("fprStatus", IF(ISNULL(OLD.fprStatus), NULL, OLD.fprStatus))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.fprUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.fprUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_ForgotPasswordRequest"
         , atlInfo   = JSON_OBJECT("fprID", OLD.fprID, "old", Changes);
  END IF;
END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_Gateway` AFTER UPDATE ON `tbl_AAA_Gateway` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.gtwName) != ISNULL(NEW.gtwName) OR OLD.gtwName != NEW.gtwName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("gtwName", IF(ISNULL(OLD.gtwName), NULL, OLD.gtwName))); END IF;
  IF ISNULL(OLD.gtwKey) != ISNULL(NEW.gtwKey) OR OLD.gtwKey != NEW.gtwKey THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("gtwKey", IF(ISNULL(OLD.gtwKey), NULL, OLD.gtwKey))); END IF;
  IF ISNULL(OLD.gtwPluginType) != ISNULL(NEW.gtwPluginType) OR OLD.gtwPluginType != NEW.gtwPluginType THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("gtwPluginType", IF(ISNULL(OLD.gtwPluginType), NULL, OLD.gtwPluginType))); END IF;
  IF ISNULL(OLD.gtwPluginName) != ISNULL(NEW.gtwPluginName) OR OLD.gtwPluginName != NEW.gtwPluginName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("gtwPluginName", IF(ISNULL(OLD.gtwPluginName), NULL, OLD.gtwPluginName))); END IF;
  IF ISNULL(OLD.gtwPluginParameters) != ISNULL(NEW.gtwPluginParameters) OR OLD.gtwPluginParameters != NEW.gtwPluginParameters THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("gtwPluginParameters", IF(ISNULL(OLD.gtwPluginParameters), NULL, OLD.gtwPluginParameters))); END IF;
  IF ISNULL(OLD.gtwStatus) != ISNULL(NEW.gtwStatus) OR OLD.gtwStatus != NEW.gtwStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("gtwStatus", IF(ISNULL(OLD.gtwStatus), NULL, OLD.gtwStatus))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.gtwUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.gtwUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_Gateway"
         , atlInfo   = JSON_OBJECT("gtwID", OLD.gtwID, "old", Changes);
  END IF;
END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_GeoCityOrVillage` AFTER UPDATE ON `tbl_AAA_GeoCityOrVillage` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.ctvName) != ISNULL(NEW.ctvName) OR OLD.ctvName != NEW.ctvName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("ctvName", IF(ISNULL(OLD.ctvName), NULL, OLD.ctvName))); END IF;
  IF ISNULL(OLD.ctvStateID) != ISNULL(NEW.ctvStateID) OR OLD.ctvStateID != NEW.ctvStateID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("ctvStateID", IF(ISNULL(OLD.ctvStateID), NULL, OLD.ctvStateID))); END IF;
  IF ISNULL(OLD.ctvType) != ISNULL(NEW.ctvType) OR OLD.ctvType != NEW.ctvType THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("ctvType", IF(ISNULL(OLD.ctvType), NULL, OLD.ctvType))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.ctvUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.ctvUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_GeoCityOrVillage"
         , atlInfo   = JSON_OBJECT("ctvID", OLD.ctvID, "old", Changes);
  END IF;
END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_GeoCountry` AFTER UPDATE ON `tbl_AAA_GeoCountry` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.cntrName) != ISNULL(NEW.cntrName) OR OLD.cntrName != NEW.cntrName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("cntrName", IF(ISNULL(OLD.cntrName), NULL, OLD.cntrName))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.cntrUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.cntrUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_GeoCountry"
         , atlInfo   = JSON_OBJECT("cntrID", OLD.cntrID, "old", Changes);
  END IF;
END ;
SQLSTR
		);

		$this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_GeoState` AFTER UPDATE ON `tbl_AAA_GeoState` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.sttName) != ISNULL(NEW.sttName) OR OLD.sttName != NEW.sttName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("sttName", IF(ISNULL(OLD.sttName), NULL, OLD.sttName))); END IF;
  IF ISNULL(OLD.sttCountryID) != ISNULL(NEW.sttCountryID) OR OLD.sttCountryID != NEW.sttCountryID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("sttCountryID", IF(ISNULL(OLD.sttCountryID), NULL, OLD.sttCountryID))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.sttUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.sttUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_GeoState"
         , atlInfo   = JSON_OBJECT("sttID", OLD.sttID, "old", Changes);
  END IF;
END ;
SQLSTR
    );

    $this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_GeoTown` AFTER UPDATE ON `tbl_AAA_GeoTown` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.twnName) != ISNULL(NEW.twnName) OR OLD.twnName != NEW.twnName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("twnName", IF(ISNULL(OLD.twnName), NULL, OLD.twnName))); END IF;
  IF ISNULL(OLD.twnCityID) != ISNULL(NEW.twnCityID) OR OLD.twnCityID != NEW.twnCityID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("twnCityID", IF(ISNULL(OLD.twnCityID), NULL, OLD.twnCityID))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.twnUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.twnUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_GeoTown"
         , atlInfo   = JSON_OBJECT("twnID", OLD.twnID, "old", Changes);
  END IF;
END ;
SQLSTR
    );

    $this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_Role` AFTER UPDATE ON `tbl_AAA_Role` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.rolName) != ISNULL(NEW.rolName) OR OLD.rolName != NEW.rolName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("rolName", IF(ISNULL(OLD.rolName), NULL, OLD.rolName))); END IF;
  IF ISNULL(OLD.rolParentID) != ISNULL(NEW.rolParentID) OR OLD.rolParentID != NEW.rolParentID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("rolParentID", IF(ISNULL(OLD.rolParentID), NULL, OLD.rolParentID))); END IF;
  IF ISNULL(OLD.rolPrivs) != ISNULL(NEW.rolPrivs) OR OLD.rolPrivs != NEW.rolPrivs THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("rolPrivs", IF(ISNULL(OLD.rolPrivs), NULL, OLD.rolPrivs))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.rolUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.rolUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_Role"
         , atlInfo   = JSON_OBJECT("rolID", OLD.rolID, "old", Changes);
  END IF;
END ;
SQLSTR
    );

    $this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_Session` AFTER UPDATE ON `tbl_AAA_Session` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.ssnUserID) != ISNULL(NEW.ssnUserID) OR OLD.ssnUserID != NEW.ssnUserID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("ssnUserID", IF(ISNULL(OLD.ssnUserID), NULL, OLD.ssnUserID))); END IF;
  IF ISNULL(OLD.ssnJWT) != ISNULL(NEW.ssnJWT) OR OLD.ssnJWT != NEW.ssnJWT THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("ssnJWT", IF(ISNULL(OLD.ssnJWT), NULL, OLD.ssnJWT))); END IF;
  IF ISNULL(OLD.ssnJWTMD5) != ISNULL(NEW.ssnJWTMD5) OR OLD.ssnJWTMD5 != NEW.ssnJWTMD5 THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("ssnJWTMD5", IF(ISNULL(OLD.ssnJWTMD5), NULL, OLD.ssnJWTMD5))); END IF;
  IF ISNULL(OLD.ssnStatus) != ISNULL(NEW.ssnStatus) OR OLD.ssnStatus != NEW.ssnStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("ssnStatus", IF(ISNULL(OLD.ssnStatus), NULL, OLD.ssnStatus))); END IF;
  IF ISNULL(OLD.ssnExpireAt) != ISNULL(NEW.ssnExpireAt) OR OLD.ssnExpireAt != NEW.ssnExpireAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("ssnExpireAt", IF(ISNULL(OLD.ssnExpireAt), NULL, OLD.ssnExpireAt))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.ssnUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.ssnUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_Session"
         , atlInfo   = JSON_OBJECT("ssnID", OLD.ssnID, "old", Changes);
  END IF;
END ;
SQLSTR
    );

    $this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_User` AFTER UPDATE ON `tbl_AAA_User` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.usrGender) != ISNULL(NEW.usrGender) OR OLD.usrGender != NEW.usrGender THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrGender", IF(ISNULL(OLD.usrGender), NULL, OLD.usrGender))); END IF;
  IF ISNULL(OLD.usrFirstName) != ISNULL(NEW.usrFirstName) OR OLD.usrFirstName != NEW.usrFirstName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrFirstName", IF(ISNULL(OLD.usrFirstName), NULL, OLD.usrFirstName))); END IF;
  IF ISNULL(OLD.usrFirstName_en) != ISNULL(NEW.usrFirstName_en) OR OLD.usrFirstName_en != NEW.usrFirstName_en THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrFirstName_en", IF(ISNULL(OLD.usrFirstName_en), NULL, OLD.usrFirstName_en))); END IF;
  IF ISNULL(OLD.usrLastName) != ISNULL(NEW.usrLastName) OR OLD.usrLastName != NEW.usrLastName THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrLastName", IF(ISNULL(OLD.usrLastName), NULL, OLD.usrLastName))); END IF;
  IF ISNULL(OLD.usrLastName_en) != ISNULL(NEW.usrLastName_en) OR OLD.usrLastName_en != NEW.usrLastName_en THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrLastName_en", IF(ISNULL(OLD.usrLastName_en), NULL, OLD.usrLastName_en))); END IF;
  IF ISNULL(OLD.usrEmail) != ISNULL(NEW.usrEmail) OR OLD.usrEmail != NEW.usrEmail THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrEmail", IF(ISNULL(OLD.usrEmail), NULL, OLD.usrEmail))); END IF;
  IF ISNULL(OLD.usrEmailApprovedAt) != ISNULL(NEW.usrEmailApprovedAt) OR OLD.usrEmailApprovedAt != NEW.usrEmailApprovedAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrEmailApprovedAt", IF(ISNULL(OLD.usrEmailApprovedAt), NULL, OLD.usrEmailApprovedAt))); END IF;
  IF ISNULL(OLD.usrMobile) != ISNULL(NEW.usrMobile) OR OLD.usrMobile != NEW.usrMobile THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrMobile", IF(ISNULL(OLD.usrMobile), NULL, OLD.usrMobile))); END IF;
  IF ISNULL(OLD.usrMobileApprovedAt) != ISNULL(NEW.usrMobileApprovedAt) OR OLD.usrMobileApprovedAt != NEW.usrMobileApprovedAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrMobileApprovedAt", IF(ISNULL(OLD.usrMobileApprovedAt), NULL, OLD.usrMobileApprovedAt))); END IF;
  IF ISNULL(OLD.usrSSID) != ISNULL(NEW.usrSSID) OR OLD.usrSSID != NEW.usrSSID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrSSID", IF(ISNULL(OLD.usrSSID), NULL, OLD.usrSSID))); END IF;
  IF ISNULL(OLD.usrRoleID) != ISNULL(NEW.usrRoleID) OR OLD.usrRoleID != NEW.usrRoleID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrRoleID", IF(ISNULL(OLD.usrRoleID), NULL, OLD.usrRoleID))); END IF;
  IF ISNULL(OLD.usrPrivs) != ISNULL(NEW.usrPrivs) OR OLD.usrPrivs != NEW.usrPrivs THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrPrivs", IF(ISNULL(OLD.usrPrivs), NULL, OLD.usrPrivs))); END IF;
  IF ISNULL(OLD.usrPasswordHash) != ISNULL(NEW.usrPasswordHash) OR OLD.usrPasswordHash != NEW.usrPasswordHash THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrPasswordHash", IF(ISNULL(OLD.usrPasswordHash), NULL, OLD.usrPasswordHash))); END IF;
  IF ISNULL(OLD.usrPasswordCreatedAt) != ISNULL(NEW.usrPasswordCreatedAt) OR OLD.usrPasswordCreatedAt != NEW.usrPasswordCreatedAt THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrPasswordCreatedAt", IF(ISNULL(OLD.usrPasswordCreatedAt), NULL, OLD.usrPasswordCreatedAt))); END IF;
  IF ISNULL(OLD.usrStatus) != ISNULL(NEW.usrStatus) OR OLD.usrStatus != NEW.usrStatus THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrStatus", IF(ISNULL(OLD.usrStatus), NULL, OLD.usrStatus))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.usrUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.usrUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_User"
         , atlInfo   = JSON_OBJECT("usrID", OLD.usrID, "old", Changes);
  END IF;
END ;
SQLSTR
    );

    $this->execute(<<<SQLSTR
CREATE TRIGGER `trg_updatelog_tbl_AAA_UserExtraInfo` AFTER UPDATE ON `tbl_AAA_UserExtraInfo` FOR EACH ROW BEGIN
  DECLARE Changes JSON DEFAULT JSON_OBJECT();

  IF ISNULL(OLD.uexBirthDate) != ISNULL(NEW.uexBirthDate) OR OLD.uexBirthDate != NEW.uexBirthDate THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("uexBirthDate", IF(ISNULL(OLD.uexBirthDate), NULL, OLD.uexBirthDate))); END IF;
  IF ISNULL(OLD.uexCountryID) != ISNULL(NEW.uexCountryID) OR OLD.uexCountryID != NEW.uexCountryID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("uexCountryID", IF(ISNULL(OLD.uexCountryID), NULL, OLD.uexCountryID))); END IF;
  IF ISNULL(OLD.uexStateID) != ISNULL(NEW.uexStateID) OR OLD.uexStateID != NEW.uexStateID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("uexStateID", IF(ISNULL(OLD.uexStateID), NULL, OLD.uexStateID))); END IF;
  IF ISNULL(OLD.uexCityOrVillageID) != ISNULL(NEW.uexCityOrVillageID) OR OLD.uexCityOrVillageID != NEW.uexCityOrVillageID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("uexCityOrVillageID", IF(ISNULL(OLD.uexCityOrVillageID), NULL, OLD.uexCityOrVillageID))); END IF;
  IF ISNULL(OLD.uexTownID) != ISNULL(NEW.uexTownID) OR OLD.uexTownID != NEW.uexTownID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("uexTownID", IF(ISNULL(OLD.uexTownID), NULL, OLD.uexTownID))); END IF;
  IF ISNULL(OLD.uexHomeAddress) != ISNULL(NEW.uexHomeAddress) OR OLD.uexHomeAddress != NEW.uexHomeAddress THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("uexHomeAddress", IF(ISNULL(OLD.uexHomeAddress), NULL, OLD.uexHomeAddress))); END IF;
  IF ISNULL(OLD.uexZipCode) != ISNULL(NEW.uexZipCode) OR OLD.uexZipCode != NEW.uexZipCode THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("uexZipCode", IF(ISNULL(OLD.uexZipCode), NULL, OLD.uexZipCode))); END IF;
  IF ISNULL(OLD.uexImage) != ISNULL(NEW.uexImage) OR OLD.uexImage != NEW.uexImage THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("uexImage", IF(ISNULL(OLD.uexImage), NULL, OLD.uexImage))); END IF;

  IF JSON_LENGTH(Changes) > 0 THEN
--    IF ISNULL(NEW.uexUpdatedBy) THEN
--      SIGNAL SQLSTATE "45401"
--         SET MESSAGE_TEXT = "UpdatedBy is not set";
--    END IF;

    INSERT INTO tbl_SYS_ActionLogs
       SET atlBy     = NEW.uexUpdatedBy
         , atlAction = "UPDATE"
         , atlTarget = "tbl_AAA_UserExtraInfo"
         , atlInfo   = JSON_OBJECT("uexUserID", OLD.uexUserID, "old", Changes);
  END IF;
END ;
SQLSTR
    );

    $this->batchInsertIgnore('{{%AAA_Role}}', ['rolID', 'rolName', 'rolParentID', 'rolPrivs'], [
      [ 1, 'Full Access', NULL, [
        "*" => 1,
      ]],
      [10, 'User',        NULL, [
        "aaa" => [
          "auth" => [
            "signup" => 1,
            "login" => 1,
            "logout" => 1,
          ],
        ],
      ]],
    ]);

    $this->execute(<<<SQLSTR
ALTER TABLE {{%AAA_Role}} AUTO_INCREMENT=101;
SQLSTR
		);

    $this->batchInsertIgnore('{{%AAA_User}}', [
      'usrID',
      'usrRoleID',
      'usrEmail',
      'usrMobile',
      'usrGender',
      'usrFirstName',
      'usrLastName',
      'usrStatus',
    ], [
      [ 1, NULL, 'system@site.dom',       NULL,            NULL, NULL,     NULL,    'D'],
			[52, 1,    'kambizzandi@gmail.com', '+989122983610', 'M',  'Kambiz', 'Zandi', 'A'],
		]);

    $this->execute(<<<SQLSTR
ALTER TABLE {{%AAA_User}} AUTO_INCREMENT=101;
SQLSTR
		);

	}

  public function safeDown()
  {
    echo "m221015_160300_aaa_init cannot be reverted.\n";

    return false;
  }

}
