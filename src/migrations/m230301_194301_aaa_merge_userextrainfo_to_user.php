<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230301_194301_aaa_merge_userextrainfo_to_user extends Migration
{
  public function safeUp()
  {
    $this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_User`
	ADD COLUMN `usrBirthDate` DATE NULL DEFAULT NULL AFTER `usrPasswordCreatedAt`,
	ADD COLUMN `usrCountryID` SMALLINT(5) UNSIGNED NULL DEFAULT NULL AFTER `usrBirthDate`,
	ADD COLUMN `usrStateID` MEDIUMINT(7) UNSIGNED NULL DEFAULT NULL AFTER `usrCountryID`,
	ADD COLUMN `usrCityOrVillageID` MEDIUMINT(7) UNSIGNED NULL DEFAULT NULL AFTER `usrStateID`,
	ADD COLUMN `usrTownID` MEDIUMINT(7) UNSIGNED NULL DEFAULT NULL AFTER `usrCityOrVillageID`,
	ADD COLUMN `usrHomeAddress` VARCHAR(2048) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `usrTownID`,
	ADD COLUMN `usrZipCode` VARCHAR(32) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `usrHomeAddress`,
	ADD COLUMN `usrImage` VARCHAR(128) NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `usrZipCode`
  ;
SQLSTR
    );

    $this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_User`
	ADD CONSTRAINT `FK_tbl_AAA_User_tbl_AAA_GeoCountry` FOREIGN KEY (`usrCountryID`) REFERENCES `tbl_AAA_GeoCountry` (`cntrID`) ON UPDATE NO ACTION ON DELETE NO ACTION,
	ADD CONSTRAINT `FK_tbl_AAA_User_tbl_AAA_GeoCityOrVillage` FOREIGN KEY (`usrCityOrVillageID`) REFERENCES `tbl_AAA_GeoCityOrVillage` (`ctvID`) ON UPDATE NO ACTION ON DELETE NO ACTION,
	ADD CONSTRAINT `FK_tbl_AAA_User_tbl_AAA_GeoState` FOREIGN KEY (`usrStateID`) REFERENCES `tbl_AAA_GeoState` (`sttID`) ON UPDATE NO ACTION ON DELETE NO ACTION,
	ADD CONSTRAINT `FK_tbl_AAA_User_tbl_AAA_GeoTown` FOREIGN KEY (`usrTownID`) REFERENCES `tbl_AAA_GeoTown` (`twnID`) ON UPDATE NO ACTION ON DELETE NO ACTION
	;
SQLSTR
    );

    $this->execute(<<<SQLSTR
UPDATE tbl_AAA_User
  INNER JOIN tbl_AAA_UserExtraInfo
          ON tbl_AAA_UserExtraInfo.uexUserID = tbl_AAA_User.usrID
         SET tbl_AAA_User.usrBirthDate       = tbl_AAA_UserExtraInfo.uexBirthDate
           , tbl_AAA_User.usrCountryID       = tbl_AAA_UserExtraInfo.uexCountryID
           , tbl_AAA_User.usrStateID         = tbl_AAA_UserExtraInfo.uexStateID
           , tbl_AAA_User.usrCityOrVillageID = tbl_AAA_UserExtraInfo.uexCityOrVillageID
           , tbl_AAA_User.usrTownID          = tbl_AAA_UserExtraInfo.uexTownID
           , tbl_AAA_User.usrHomeAddress     = tbl_AAA_UserExtraInfo.uexHomeAddress
           , tbl_AAA_User.usrZipCode         = tbl_AAA_UserExtraInfo.uexZipCode
           , tbl_AAA_User.usrImage           = tbl_AAA_UserExtraInfo.uexImage
  ;
SQLSTR
    );

    $this->execute('DROP TRIGGER IF EXISTS trg_updatelog_tbl_AAA_User;');

    $this->execute(<<<SQLSTR
CREATE TRIGGER trg_updatelog_tbl_AAA_User AFTER UPDATE ON tbl_AAA_User FOR EACH ROW BEGIN
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
  IF ISNULL(OLD.usrImage) != ISNULL(NEW.usrImage) OR OLD.usrImage != NEW.usrImage THEN SET Changes = JSON_MERGE_PRESERVE(Changes, JSON_OBJECT("usrImage", IF(ISNULL(OLD.usrImage), NULL, OLD.usrImage))); END IF;
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

    $this->execute('DROP TRIGGER IF EXISTS trg_updatelog_tbl_AAA_UserExtraInfo;');

    $this->execute('DROP PROCEDURE IF EXISTS spUpdateTableTriggerAndCols;');

    $this->execute(<<<SQLSTR
CREATE PROCEDURE `spUpdateTableTriggerAndCols`(
  IN `iSchema` VARCHAR(50),
  IN `iTable` VARCHAR(50),
  IN `iFK` BOOLEAN,
  OUT `oQueryStr` TEXT
)
Proc: BEGIN
  -- DECLARE QueryStr VARCHAR(21000) DEFAULT '';
  DECLARE TempQueryStr TEXT;
  DECLARE TriggerName VARCHAR(100);
  DECLARE vPrefix VARCHAR(50);
  DECLARE vi INTEGER DEFAULT 0;

  SET oQueryStr = '';

  CALL spFindTablePrefix(iTable, vPrefix);
  IF vPrefix IS NULL OR vPrefix = '' THEN
    LEAVE Proc;
  END IF;

  /* CreatedAt */
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

  /* CreatedBy */
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

  /* UpdatedAt */
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

  /* UpdatedBy */
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

  /* RemovedAt */
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

  /* RemovedBy */
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

/*
    IF (iFK = 1) THEN
      SET oQueryStr = CONCAT(oQueryStr, '
        ADD CONSTRAINT FKA_', iTable,'_tbl_AAA_User_creator FOREIGN KEY (', vPrefix, 'CreatedBy) REFERENCES tbl_AAA_User (usrID) ON UPDATE CASCADE ON DELETE RESTRICT,
        ADD CONSTRAINT FKA_', iTable,'_tbl_AAA_User_modifier FOREIGN KEY (', vPrefix, 'UpdatedBy) REFERENCES tbl_AAA_User (usrID) ON UPDATE CASCADE ON DELETE RESTRICT
        ');
    ELSE
      SET oQueryStr = CONCAT(oQueryStr, '
        ADD INDEX ', vPrefix, 'CreatedBy (', vPrefix, 'CreatedBy) ,
        ADD INDEX ', vPrefix, 'UpdatedBy (', vPrefix, 'UpdatedBy)
        ');
    END IF;

    SET @SQL := oQueryStr;
    PREPARE stmt FROM @SQL;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  ELSEIF oQueryStr < 3 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Some essential columns not found';
  END IF;
/**/
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
    INTO TempQueryStr /**/
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
  /*
  SET @SQL := oQueryStr;
  PREPARE stmt FROM @SQL;
  EXECUTE stmt;
  DEALLOCATE PREPARE stmt;
  /**/
END ;
SQLSTR
    );

    $this->execute(<<<SQLSTR
ALTER TABLE `tbl_AAA_UserExtraInfo`
	DROP FOREIGN KEY `FK_tbl_AAA_UserExtraInfo_tbl_AAA_User`;
SQLSTR
    );

    $this->execute('RENAME TABLE `tbl_AAA_UserExtraInfo` TO `DELETED_tbl_AAA_UserExtraInfo`;');
  }

  public function safeDown()
  {
    echo "m230301_194301_aaa_merge_userextrainfo_to_user cannot be reverted.\n";

    return false;
  }

}
