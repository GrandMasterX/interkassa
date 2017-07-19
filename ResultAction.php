<?php

namespace grandmasterx\interkassa;

use Yii;
use yii\web\BadRequestHttpException;

/**
 * Class ResultAction
 * @package grandmasterx\interkassa
 */
class ResultAction extends BaseAction
{

    /**
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function run() {
        $params = Yii::$app->request->post();
        $ik_co_id = Yii::$app->request->post('ik_co_id');
        $ik_pm_no = Yii::$app->request->post('ik_pm_no');
        $ik_am = Yii::$app->request->post('ik_am');
        $ik_inv_st = Yii::$app->request->post('ik_inv_st');
        $ik_sign = Yii::$app->request->post('ik_sign');
        $ik_inv_id = Yii::$app->request->post('ik_inv_id');

        if ($ik_co_id == Yii::$app->interkassa->co_id
            && $ik_sign == Yii::$app->interkassa->generateSign(Yii::$app->request->bodyParams)
        ) {
            return $this->callback($ik_am, $ik_inv_st, $ik_pm_no, $ik_inv_id, $params);
        } else {
            throw new BadRequestHttpException;
        }
    }
}