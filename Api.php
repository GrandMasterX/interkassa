<?php

namespace grandmasterx\interkassa;

use Yii;
use yii\base\Exception;
use yii\httpclient\Client;
use yii\base\InvalidConfigException;
use grandmasterx\interkassa\exceptions\HttpException;
use grandmasterx\interkassa\exceptions\InterkassaException;

/**
 * Class Api
 * @package grandmasterx\interkassa
 */
class Api
{

    /**
     *
     */
    const NEW_PAYMENT = 0;

    /**
     *
     */
    const PENDING_PAYMENT = 2;

    /**
     *
     */
    const PROCESSED_BY_PAYMENT_SYSTEM = 3;

    /**
     *
     */
    const IN_REFUND_PROCESS_BY_PAYMENT_SYSTEM = 4;

    /**
     *
     */
    const OVERDUE = 6;

    /**
     *
     */
    const RETURNED_ALREADY = 7;

    /**
     *
     */
    const ENROLLED = 8;

    /**
     *
     */
    const RETURNED_BY_PAYMENT_SYSTEM = 9;

    /**
     *
     */
    const WAITING_MODERATION_APPROVE = 1;

    /**
     *
     */
    const VERIFIED_BY_MODERATOR = 2;

    /**
     *
     */
    const RETURNED_BY_MODERATORS = 3;

    /**
     *
     */
    const FROZEN = 4;

    /**
     *
     */
    const DEFROSTED = 5;

    /**
     *
     */
    const PROCESSING_BY_PAYMENT_SYSTEM = 6;

    /**
     *
     */
    const ENROLLMENT = 7;

    /**
     *
     */
    const SUCCESS = 8;

    /**
     *
     */
    const DECLINED = 9;

    /**
     *
     */
    const RETURNED = 11;

    /**
     *
     */
    const CREATED_BUT_NOT_PROCESSED = 12;

    /**
     *
     */
    const URL = 'https://api.interkassa.com/v1/';

    /**
     * @var
     */
    public $purse;

    /**
     * @var
     */
    public $payway;

    /**
     * @var bool
     */
    public $testTransaction = true;

    /**
     * @var
     */
    public $lk_api_account_id;

    /**
     * @var
     */
    public $checkoutId;

    /**
     * @var array
     */
    public static $temporaryStatuses = [
        self::WAITING_MODERATION_APPROVE   => 'WAITING MODERATION APPROVE',
        self::VERIFIED_BY_MODERATOR        => 'VERIFIED BY MODERATOR',
        self::FROZEN                       => 'FROZEN',
        self::DEFROSTED                    => 'DEFROSTED',
        self::PROCESSING_BY_PAYMENT_SYSTEM => 'PROCESSING BY PAYMENT SYSTEM',
        self::ENROLLMENT                   => 'ENROLLMENT',
        self::CREATED_BUT_NOT_PROCESSED    => 'CREATED BUT NOT PROCESSED',
    ];

    /**
     * @var array
     */
    public static $finalStatuses = [
        self::RETURNED_BY_MODERATORS => 'RETURNED BY MODERATOR',
        self::SUCCESS                => 'SUCCESS',
        self::DECLINED               => 'DECLINED',
        self::RETURNED               => 'RETURNED',
    ];

    /**
     * @return null
     */
    public function getAccounts() {
        return $this->request('GET', 'account');
    }

    /**
     * @return null
     */
    public function getCheckout() {
        return self::request('GET', 'checkout', $this->getLkApiAccountId());
    }

    /**
     * @return null
     */
    public function getPurses() {
        return $this->request('GET', 'purse', $this->getLkApiAccountId());
    }

    /**
     * @return null
     */
    public function getCoInvoices() {
        return $this->request('GET', 'co-invoice', $this->getLkApiAccountId());
    }

    /**
     * @return null
     */
    public function getWithdraws() {
        return $this->request('GET', 'withdraw/', $this->getLkApiAccountId());
    }

    /**
     * @param $id
     * @return null
     */
    public function getWithdraw($id) {
        return $this->request('GET', 'withdraw/' . $id, $this->getLkApiAccountId());
    }

    /**
     * @param $amount
     * @param $paywayId
     * @param $details
     * @param $purseId
     * @param $calcKey
     * @param $action
     * @param $paymentNo
     * @return null
     */
    public function createWithdraw($amount, $paywayId, $details, $purseId, $calcKey, $action, $paymentNo) {
        return $this->request('POST', 'withdraw', $this->getLkApiAccountId(), [
            'amount'    => $amount,
            'paywayId'  => $paywayId,
            'details'   => $details,
            'purseId'   => $purseId,
            'calcKey'   => $calcKey,
            'action'    => $action,
            'paymentNo' => $paymentNo,
        ]);
    }

    /**
     * @return mixed|null
     */
    public function getCurrencies() {
        $cache = Yii::$app->cache;
        $data = $cache->get('interkassa.currency');
        if ($data) {
            return $data;
        } else {
            $response = $this->request('GET', 'currency');
            $cache->set('interkassa.currency', $response, 86400);
            return $response;
        }
    }

    /**
     * @return mixed|null
     */
    public function getInputPayways() {
        $cache = Yii::$app->cache;
        $data = $cache->get('interkassa.input_payways');
        if ($data) {
            return $data;
        } else {
            $response = $this->request('GET', 'paysystem-input-payway');
            $cache->set('interkassa.input_payways', $response, 86400);
            return $response;
        }
    }

    /**
     * @return mixed|null
     */
    public function getOutputPayways() {
        $cache = Yii::$app->cache;
        $data = $cache->get('interkassa.output_payways');
        if ($data) {
            return $data;
        } else {
            $response = $this->request('GET', 'paysystem-output-payway', null);
            $cache->set('interkassa.output_payways', $response, 86400);
            return $response;
        }
    }

    /**
     * @param $http_method
     * @param $method
     * @param null $lk_api_account_id
     * @param array $data
     * @return null
     * @throws HttpException
     * @throws InterkassaException
     * @throws InvalidConfigException
     */
    public function request($http_method, $method, $lk_api_account_id = null, $data = []) {
        if (Yii::$app->interkassa === null) {
            throw new InvalidConfigException("Interkassa component not inited.");
        }

        $client = new Client();
        $request = $client->createRequest()
            ->setMethod($http_method)
            ->setUrl(self::URL . $method);

        if (!$data) {
            $data['checkoutId'] = Yii::$app->interkassa->co_id;
        }
        if ($method == 'paysystem-output-payway') {
            $data['purseId'] = $this->purse['id'];
        }

        $request->setData($data);

        if ($lk_api_account_id !== null) {
            $request->addHeaders(['Ik-Api-Account-Id' => $lk_api_account_id]);
        }

        $request->addHeaders(['Authorization' => 'Basic ' . base64_encode(Yii::$app->interkassa->api_user_id . ':' . Yii::$app->interkassa->api_user_key)]);

        $response = $request->send();

        if ($response->isOk) {
            if ($response->data['code'] == 0) {
                return $response->data['data'] ? $response->data['data'] : null;
            } else {
                throw new InterkassaException($response->data['code'] . ': ' . $response->data['message']);
            }
        } else {
            throw new HttpException($response->statusCode);
        }
    }

    /**
     * @return mixed|null
     * @throws Exception
     */
    private function getLkApiAccountId() {
        $cache = Yii::$app->cache;
        $lk_api_account_id = $cache->get('interkassa.lk_api_account_id');
        if ($lk_api_account_id) {
            return $lk_api_account_id;
        } else {
            $accounts_info = $this->getAccounts();
            $lk_api_account_id = null;

            foreach ($accounts_info as $account_info) {
                if ($account_info['tp'] == 'b') {
                    $lk_api_account_id = $account_info['_id'];
                }
            }

            if ($lk_api_account_id === null) {
                throw new Exception("Business id not found");
            }

            $cache->set('interkassa.lk_api_account_id', $lk_api_account_id, 86400);
            return $lk_api_account_id;
        }
    }
}