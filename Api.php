<?php

namespace grandmasterx\interkassa;

use grandmasterx\interkassa\exceptions\HttpException;
use grandmasterx\interkassa\exceptions\InterkassaException;
use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;

/**
 * Class Api
 * @package grandmasterx\interkassa
 */
class Api
{

    /**
     *
     */
    const URL = 'https://api.interkassa.com/v1/';

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
        return $this->request('GET', 'co-invoice/', $this->getLkApiAccountId());
    }

    /**
     * @param $id
     * @return null
     */
    public function getWithdraw($id) {
        return $this->request('GET', 'co-invoice/' . $id, $this->getLkApiAccountId());
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
        if (($data = $cache->get('interkassa.currency')) !== null)
            return $data;
        else {
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
        if (($data = $cache->get('interkassa.output_payways')) !== null)
            return $data;
        else {
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
        if (Yii::$app->interkassa === null)
            throw new InvalidConfigException("Interkassa component not inited.");

        $client = new Client();
        $request = $client->createRequest()
            ->setMethod($http_method)
            ->setUrl(self::URL . $method);

        if (count($data) > 0) {
            $request->setData($data);
        }

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