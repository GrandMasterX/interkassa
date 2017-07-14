<?php

namespace grandmasterx\interkassa;

use yii\base\Action;
use yii\base\InvalidConfigException;

/**
 * Class BaseAction
 * @package grandmasterx\interkassa
 */
class BaseAction extends Action
{

    /**
     * @var
     */
    public $callback;

    /**
     * @param $ik_am
     * @param $ik_inv_st
     * @param $ik_pm_no
     * @param $ik_inv_id
     * @return mixed
     * @throws InvalidConfigException
     */
    protected function callback($ik_am, $ik_inv_st, $ik_pm_no, $ik_inv_id) {
        if (!is_callable($this->callback)) {
            throw new InvalidConfigException('"' . get_class($this) . '::callback" should be a valid callback.');
        }

        $response = call_user_func($this->callback, $ik_am, $ik_inv_st, $ik_pm_no, $ik_inv_id);
        return $response;
    }
}