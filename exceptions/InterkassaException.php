<?php
namespace grandmasterx\interkassa\exceptions;

use yii\base\Exception;

class InterkassaException extends Exception
{
    public function getName()
    {
        return 'Interkassa api return error';
    }
}