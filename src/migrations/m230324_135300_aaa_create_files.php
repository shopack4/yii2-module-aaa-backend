<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230324_135300_aaa_create_files extends Migration
{
  public function safeUp()
	{
    $this->execute(<<<SQLSTR
CREATE TABLE `tbl_AAA_UploadFile` (
  `uflID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uflOwnerUserID` BIGINT(20) UNSIGNED NOT NULL,
  `uflPath` VARCHAR(256) NOT NULL COLLATE 'utf8mb4_unicode_ci',
  `uflOriginalFileName` VARCHAR(256) NOT NULL COLLATE 'utf8mb4_unicode_ci',
  `uflCounter` MEDIUMINT(7) UNSIGNED NULL DEFAULT NULL,
  `uflStoredFileName` VARCHAR(256) NOT NULL COLLATE 'utf8mb4_unicode_ci',
  `uflSize` BIGINT(20) UNSIGNED NOT NULL,
  `uflFileType` VARCHAR(64) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `uflMimeType` VARCHAR(128) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `uflLocalFullFileName` TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
  `uflStatus` CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'N:New, R:Removed' COLLATE 'utf8mb4_unicode_ci',
  `uflCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uflCreatedBy` BIGINT(20) UNSIGNED NOT NULL,
  `uflUpdatedAt` DATETIME NULL DEFAULT NULL,
  `uflUpdatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `uflRemovedAt` INT(10) NOT NULL DEFAULT '0',
  `uflRemovedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `uflUniqueMD5` CHAR(32) AS (md5(concat_ws('X',ifnull(`uflPath`,''),`uflOriginalFileName`,ifnull(`uflCounter`,0),`uflCreatedBy`))) virtual,
  PRIMARY KEY (`uflID`) USING BTREE,
  UNIQUE INDEX `uflUniqueMD5` (`uflUniqueMD5`) USING BTREE,
  INDEX `FK_tbl_AAA_UploadFile_tbl_AAA_User` (`uflOwnerUserID`) USING BTREE,
  CONSTRAINT `FK_tbl_AAA_UploadFile_tbl_AAA_User` FOREIGN KEY (`uflOwnerUserID`) REFERENCES `tbl_AAA_User` (`usrID`) ON UPDATE NO ACTION ON DELETE NO ACTION
) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;
SQLSTR
		);

    $this->execute(<<<SQLSTR
CREATE TABLE `tbl_AAA_UploadQueue` (
  `uquID` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uquFileID` BIGINT(20) UNSIGNED NOT NULL,
  `uquGatewayID` INT(10) UNSIGNED NOT NULL,
  `uquLockedAt` TIMESTAMP NULL DEFAULT NULL,
  `uquLockedBy` VARCHAR(64) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `uquLastTryAt` TIMESTAMP NULL DEFAULT NULL,
  `uquStoredAt` TIMESTAMP NULL DEFAULT NULL,
  `uquResult` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci',
  `uquStatus` CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'N:New, S:Stored, E:Error, R:Removed' COLLATE 'utf8mb4_unicode_ci',
  `uquCreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uquCreatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `uquUpdatedAt` DATETIME NULL DEFAULT NULL,
  `uquUpdatedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `uquRemovedAt` INT(10) NOT NULL DEFAULT '0',
  `uquRemovedBy` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`uquID`) USING BTREE,
  INDEX `FK_tbl_AAA_UploadQueue_tbl_AAA_UploadFiles` (`uquFileID`) USING BTREE,
  INDEX `FK_tbl_AAA_UploadQueue_tbl_AAA_Gateway` (`uquGatewayID`) USING BTREE,
  CONSTRAINT `FK_tbl_AAA_UploadQueue_tbl_AAA_Gateway` FOREIGN KEY (`uquGatewayID`) REFERENCES `tbl_AAA_Gateway` (`gtwID`) ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT `FK_tbl_AAA_UploadQueue_tbl_AAA_UploadFiles` FOREIGN KEY (`uquFileID`) REFERENCES `tbl_AAA_UploadFile` (`uflID`) ON UPDATE NO ACTION ON DELETE CASCADE
) COLLATE='utf8mb4_unicode_ci' ENGINE=InnoDB;
SQLSTR
		);

    $this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_User`
	ADD COLUMN `usrImageFileID` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `usrImage`,
	ADD CONSTRAINT `FK_tbl_AAA_User_tbl_AAA_UploadFile` FOREIGN KEY (`usrImageFileID`) REFERENCES `tbl_AAA_UploadFile` (`uflID`) ON UPDATE NO ACTION ON DELETE NO ACTION;
SQLSTR
    );

    $this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_User`
	DROP COLUMN `usrImage`;
SQLSTR
    );

    $this->execute('DROP TRIGGER IF EXISTS trg_updatelog_tbl_AAA_User;');

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
  IF ISNULL(OLD.usrBirthDate) != ISNULL(NEW.usrBirthDate) OR OLD.usrBirthDate != NEW.usrBirthDate THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrBirthDate", IF(ISNULL(OLD.usrBirthDate), NULL, OLD.usrBirthDate))); END IF;
  IF ISNULL(OLD.usrCountryID) != ISNULL(NEW.usrCountryID) OR OLD.usrCountryID != NEW.usrCountryID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrCountryID", IF(ISNULL(OLD.usrCountryID), NULL, OLD.usrCountryID))); END IF;
  IF ISNULL(OLD.usrStateID) != ISNULL(NEW.usrStateID) OR OLD.usrStateID != NEW.usrStateID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrStateID", IF(ISNULL(OLD.usrStateID), NULL, OLD.usrStateID))); END IF;
  IF ISNULL(OLD.usrCityOrVillageID) != ISNULL(NEW.usrCityOrVillageID) OR OLD.usrCityOrVillageID != NEW.usrCityOrVillageID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrCityOrVillageID", IF(ISNULL(OLD.usrCityOrVillageID), NULL, OLD.usrCityOrVillageID))); END IF;
  IF ISNULL(OLD.usrTownID) != ISNULL(NEW.usrTownID) OR OLD.usrTownID != NEW.usrTownID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrTownID", IF(ISNULL(OLD.usrTownID), NULL, OLD.usrTownID))); END IF;
  IF ISNULL(OLD.usrHomeAddress) != ISNULL(NEW.usrHomeAddress) OR OLD.usrHomeAddress != NEW.usrHomeAddress THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrHomeAddress", IF(ISNULL(OLD.usrHomeAddress), NULL, OLD.usrHomeAddress))); END IF;
  IF ISNULL(OLD.usrZipCode) != ISNULL(NEW.usrZipCode) OR OLD.usrZipCode != NEW.usrZipCode THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrZipCode", IF(ISNULL(OLD.usrZipCode), NULL, OLD.usrZipCode))); END IF;
  IF ISNULL(OLD.usrImageFileID) != ISNULL(NEW.usrImageFileID) OR OLD.usrImageFileID != NEW.usrImageFileID THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrImageFileID", IF(ISNULL(OLD.usrImageFileID), NULL, OLD.usrImageFileID))); END IF;
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
CREATE FUNCTION `fnApplyCounterToFileName`(
  `iFileName` TEXT,
  `iCounter` INT UNSIGNED
)
RETURNS text CHARSET utf8mb4
BEGIN
  DECLARE vName TEXT;
  DECLARE vExt TEXT;
  DECLARE vResult TEXT;

/*
    ORG             NAME        EXT     RESULT
  -----------------------------------------------------------
  1/  aaa              aaa                 aaa (1)
  2/ .aaa                         aaa     .aaa (2)
  3/  aaa.txt          aaa        txt      aaa (3).txt
  4/ .aaa.txt         .aaa        txt     .aaa (4).txt
  5/  aaa.txt.log      aaa.txt    log      aaa.txt (5).log
  6/ .aaa.txt.log     .aaa.txt    log     .aaa.txt (6).log

  SELECT fnApplyCounterToFileName('aaa', 1)
  UNION
  SELECT fnApplyCounterToFileName('.aaa', 2)
  UNION
  SELECT fnApplyCounterToFileName('aaa.txt', 3)
  UNION
  SELECT fnApplyCounterToFileName('.aaa.txt', 4)
  UNION
  SELECT fnApplyCounterToFileName('aaa.txt.log', 5)
  UNION
  SELECT fnApplyCounterToFileName('.aaa.txt.log', 6)
  ;
*/

  SELECT SUBSTRING_INDEX(iFileName, '.', -1) INTO vExt;

  IF (LOCATE('.', iFileName) = 0) OR (LENGTH(vExt)+1 = LENGTH(iFileName)) THEN -- 1, 2
    SET vResult = CONCAT(iFileName, ' (', iCounter, ')');
  ELSE
    SET vName = LEFT(iFileName, LENGTH(iFileName) - LENGTH(vExt) - 1);
    SET vResult = CONCAT(vName, ' (', iCounter, ')', '.', vExt);
  END IF;

  RETURN vResult;

END ;
SQLSTR
    );

    $this->execute(<<<SQLSTR
CREATE PROCEDURE `spUploadedFile_Create`(
  IN `iPath` VARCHAR(256),
  IN `iOriginalFileName` VARCHAR(256),
  IN `iFullTempPath` VARCHAR(512),
  IN `iSetTempFileNameToMD5` TINYINT,
  IN `iFileSize` BIGINT UNSIGNED,
  IN `iFileType` VARCHAR(64),
  IN `iMimeType` VARCHAR(128),
  IN `iOwnerUserID` BIGINT UNSIGNED,
  IN `iCreatorUserID` BIGINT UNSIGNED,
  IN `iLockedBy` VARCHAR(50),
  OUT `oStoredFileName` VARCHAR(256),
  OUT `oTempFileName` VARCHAR(256),
  OUT `oUploadedFileID` BIGINT UNSIGNED,
  OUT `oQueueRowsCount` INT UNSIGNED
)
BEGIN
  DECLARE vErr VARCHAR(500);
  DECLARE vUploadedFileCounter BIGINT UNSIGNED;
  DECLARE vExt VARCHAR(500);

  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    GET DIAGNOSTICS CONDITION 1 vErr = MESSAGE_TEXT;

    /****************/
    ROLLBACK;
    /****************/

    INSERT INTO tbl_SYS_ActionLogs
        SET atlBy = iCreatorUserID,
            atlTarget = 'Upload.Error',
            atlInfo = JSON_OBJECT(
            "err",                   vErr,
            "iPath",                 iPath,
            "iOriginalFileName",     iOriginalFileName,
            "iFileSize",             iFileSize,
            "iFileType",             iFileType,
            "iMimeType",             iMimeType,
            "iFullTempPath",         iFullTempPath,
      "iOwnerUserID",          iOwnerUserID,
            "iCreatorUserID",        iCreatorUserID,
            "iLockedBy",             iLockedBy,
            "UploadedFileCounter",   vUploadedFileCounter,
            "StoredFileName",        oStoredFileName,
            "oTempFileName",         oTempFileName,
            "FileID",                oUploadedFileID,
            "QueuedCount",           oQueueRowsCount
          )
    ;

    RESIGNAL;
  END;

  /****************/
  START TRANSACTION;
  /****************/

  SET vUploadedFileCounter = NULL;

  SELECT MAX(IFNULL(uflCounter, 0))
    INTO vUploadedFileCounter
    FROM tbl_AAA_UploadFile
    WHERE IFNULL(uflPath, '') = IFNULL(iPath, '')
      AND uflOriginalFileName = iOriginalFileName
      AND uflOwnerUserID = iOwnerUserID
  ;

  IF ISNULL(vUploadedFileCounter) THEN
    SET oStoredFileName = iOriginalFileName;
  ELSE
    SET vUploadedFileCounter = vUploadedFileCounter + 1;
    SET oStoredFileName = fnApplyCounterToFileName(iOriginalFileName, vUploadedFileCounter);
  END IF;

  IF iSetTempFileNameToMD5 = 1 THEN
    SET oTempFileName = MD5(CONCAT(RAND(), UUID())); -- MD5(oStoredFileName);

    SELECT SUBSTRING_INDEX(oStoredFileName, '.', -1) INTO vExt;

    IF (LOCATE('.', oStoredFileName) != 0) AND (LENGTH(vExt)+1 != LENGTH(oStoredFileName)) THEN
      SET oTempFileName = CONCAT(oTempFileName, '.', vExt);
    END IF;
  ELSE
    SET oTempFileName = oStoredFileName;
  END IF;

  INSERT INTO tbl_AAA_UploadFile
      SET uflPath = iPath,
          uflOriginalFileName = iOriginalFileName,
          uflCounter = vUploadedFileCounter,
          uflStoredFileName = oStoredFileName,
          uflSize = iFileSize,
          uflFileType = iFileType,
          uflMimeType = iMimeType,
          uflLocalFullFileName = CONCAT(iFullTempPath, '/', oTempFileName),
      uflOwnerUserID = iOwnerUserID,
          uflCreatedBy = iCreatorUserID
  ;
  SET oUploadedFileID = LAST_INSERT_ID();

  INSERT INTO tbl_AAA_UploadQueue(
          uquFileID
        , uquGatewayID
        , uquLockedAt
        , uquLockedBy
        , uquCreatedBy
          )
  SELECT oUploadedFileID
        , gtwID
        , IF(iLockedBy IS NULL OR iLockedBy='', NULL, NOW())
        , IF(iLockedBy IS NULL OR iLockedBy='', NULL, iLockedBy)
        , iCreatorUserID
    FROM tbl_AAA_Gateway
    WHERE gtwPluginType = 'objectstorage'
      AND gtwStatus = 'A'
      AND (JSON_EXTRACT(gtwRestrictions, '$.AllowedFileTypes') IS NULL
      OR LOWER(JSON_EXTRACT(gtwRestrictions, '$.AllowedFileTypes')) LIKE CONCAT('%', iFileType, '%')
          )
      AND (JSON_EXTRACT(gtwRestrictions, '$.AllowedMimeTypes') IS NULL
      OR LOWER(JSON_EXTRACT(gtwRestrictions, '$.AllowedMimeTypes')) LIKE CONCAT('%', iMimeType, '%')
          )
      AND (JSON_EXTRACT(gtwRestrictions, '$.AllowedMinFileSize') IS NULL
      OR JSON_UNQUOTE(JSON_EXTRACT(gtwRestrictions, '$.AllowedMinFileSize')) <= iFileSize
          )
      AND (JSON_EXTRACT(gtwRestrictions, '$.AllowedMaxFileSize') IS NULL
      OR JSON_UNQUOTE(JSON_EXTRACT(gtwRestrictions, '$.AllowedMaxFileSize')) >= iFileSize
          )
      AND (JSON_EXTRACT(gtwRestrictions, '$.MaxFilesCount') IS NULL
      OR JSON_UNQUOTE(JSON_EXTRACT(gtwRestrictions, '$.MaxFilesCount'))
        > (IFNULL(JSON_EXTRACT(gtwUsages, '$.CreatedFilesCount'), 0)
        - IFNULL(JSON_EXTRACT(gtwUsages, '$.DeletedFilesCount'), 0)
          )
          )
      AND (JSON_EXTRACT(gtwRestrictions, '$.MaxFilesSize') IS NULL
      OR JSON_UNQUOTE(JSON_EXTRACT(gtwRestrictions, '$.MaxFilesSize'))
      >= (IFNULL(JSON_EXTRACT(gtwUsages, '$.CreatedFilesSize'), 0)
        - IFNULL(JSON_EXTRACT(gtwUsages, '$.DeletedFilesSize'), 0)
        + iFileSize
          )
          )
  ;
  SET oQueueRowsCount = ROW_COUNT();

  /* this is for next version
  IF (oQueueRowsCount > 0) THEN
    UPDATE tbl_AAA_UploadFile
        SET uflStatus = 'Q'
      WHERE uflID = oUploadedFileID
    ;
  END IF;
  */

  /****************/
  COMMIT;
  /****************/
END ;
SQLSTR
    );

  }

	public function safeDown()
	{
		echo "m230324_135300_aaa_create_files cannot be reverted.\n";
		return false;
	}

}
