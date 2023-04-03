<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230304_123544_aaa_fixerror_in_spFindTablePrefix extends Migration
{
  public function safeUp()
	{
		$this->execute("DROP PROCEDURE IF EXISTS `spFindTablePrefix`");

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
		LIMIT 0,1
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
		LIMIT 0,1
	;

	SET vMinColumnLen = LEAST(LENGTH(vFirstColumnName), LENGTH(vLastColumnName));

	SET vi = 0;
	WHILE vi < vMinColumnLen AND SUBSTRING(vFirstColumnName, vi+1, 1) = SUBSTRING(vLastColumnName, vi+1, 1) DO
		SET vi = vi + 1;
	END WHILE;

	IF vi > 0 THEN
		SET oPrefix = SUBSTRING(vFirstColumnName, 1, vi);
	END IF;

END
SQLSTR
		);
	}

	public function safeDown()
	{
		echo "m230304_123544_aaa_fixerror_in_spFindTablePrefix cannot be reverted.\n";
		return false;
	}

}
