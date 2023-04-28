<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230409_184249_aaa_create_payments extends Migration
{
  public function safeUp()
	{
		//tbl_AAA_Voucher
		//tbl_AAA_OnlinePayment
		//tbl_AAA_OfflinePayment

	$this->execute(<<<SQLSTR
SQLSTR
		);

	$this->execute(<<<SQLSTR
SQLSTR
		);

	$this->execute(<<<SQLSTR
SQLSTR
	);

	$this->execute(<<<SQLSTR
SQLSTR
	);

	// $this->execute('DROP TRIGGER IF EXISTS ;');

	$this->execute(<<<SQLSTR
SQLSTR
	);

  }

	public function safeDown()
	{
		echo "m230409_184249_aaa_create_payments cannot be reverted.\n";
		return false;
	}

}
