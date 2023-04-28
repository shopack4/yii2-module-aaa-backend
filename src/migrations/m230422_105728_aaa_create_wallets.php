<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

use shopack\base\common\db\Migration;

class m230422_105728_aaa_create_wallets extends Migration
{
  public function safeUp()
	{
		//tbl_AAA_Wallet
		//tbl_AAA_WalletTransaction
		//create default wallet trigger after user inserted

    $this->execute(<<<SQLSTR
SQLSTR
		);

  }

	public function safeDown()
	{
		echo "m230422_105728_aaa_create_wallets cannot be reverted.\n";
		return false;
	}

}
