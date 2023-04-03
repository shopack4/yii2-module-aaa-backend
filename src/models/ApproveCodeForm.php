<?php
/**
 * @author Kambiz Zandi <kambizzandi@gmail.com>
 */

namespace shopack\aaa\backend\models;

use yii\base\Model;
use shopack\aaa\backend\models\ApprovalRequestModel;

class ApproveCodeForm extends Model
{
  public $input;
  public $code;

  public function rules()
  {
    return [
      ['input', 'required'],
      ['code', 'required'],
    ];
  }

  public function approve()
  {
    if ($this->validate() == false)
      return false;

    // list ($normalizedInput, $inputType) = AuthHelper::checkLoginPhrase($this->input, false);

		return ApprovalRequestModel::acceptCode($this->input, $this->code);
  }

}
