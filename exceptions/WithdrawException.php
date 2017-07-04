<?php
namespace grandmasterx\interkassa\exceptions;

use yii\base\Exception;

class WithdrawException extends Exception
{
    public function getName()
    {
        return 'Withdraw error';
    }
}