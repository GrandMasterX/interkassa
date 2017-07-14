<?php

namespace grandmasterx\interkassa;

use Yii;
use yii\web\BadRequestHttpException;

/**
 * Class FailAction
 * @package grandmasterx\interkassa
 */
class FailAction extends BaseAction
{

    /**
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function run() {
        $ik_pm_no = Yii::$app->request->post('ik_pm_no');
        $ik_am = Yii::$app->request->post('ik_am');
        $ik_inv_st = Yii::$app->request->post('ik_inv_st');
        $ik_inv_id = Yii::$app->request->post('ik_inv_id');

        if (!$ik_pm_no && !$ik_am && !$ik_inv_st) {
            throw new BadRequestHttpException;
        } else {
            return $this->callback($ik_am, $ik_inv_st, $ik_pm_no, $ik_inv_id);
        }
    }
}