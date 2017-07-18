<?php

namespace grandmasterx\interkassa;

use Yii;
use yii\web\BadRequestHttpException;

/**
 * Class SuccessAction
 * @package grandmasterx\interkassa
 */
class SuccessAction extends BaseAction
{

    /**
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function run() {
        $ik_pm_no = Yii::$app->request->post('ik_pm_no');
        $ik_inv_st = Yii::$app->request->post('ik_inv_st');
        $ik_inv_id = Yii::$app->request->post('ik_inv_id');
        $ik_co_id = Yii::$app->request->post('ik_co_id');

        if (!$ik_pm_no && !$ik_co_id && !$ik_inv_st && !$ik_inv_id) {
            throw new BadRequestHttpException;
        } else {
            return $this->callback($ik_co_id, $ik_inv_st, $ik_pm_no, $ik_inv_id);
        }
    }
}