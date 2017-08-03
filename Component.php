<?php

namespace grandmasterx\interkassa;

use Yii;
use grandmasterx\interkassa\exceptions\HttpException;
use grandmasterx\interkassa\exceptions\WithdrawException;
use grandmasterx\interkassa\exceptions\InterkassaException;

/**
 * Class Component
 * @package grandmasterx\interkassa
 */
class Component extends \yii\base\Component
{
    /**
     * @var
     */
    public $co_id;
    /**
     * @var
     */
    public $secret_key;
    /**
     * @var
     */
    public $test_key;
    /**
     * @var string
     */
    public $sign_algo = 'md5';
    /**
     * @var
     */
    public $api_user_id;
    /**
     * @var
     */
    public $api_user_key;
    /**
     * @var
     */
    public $api;

    /**
     *
     */
    const URL = 'https://sci.interkassa.com/';

    /**
     *
     */
    public function init() {
        parent::init();
        $this->api = new Api();
    }

    /**
     * @param array $params
     * @return string
     */
    public function generateSign(array $params) {
        $pairs = [];

        foreach ($params as $key => $val) {
            if (strpos($key, 'ik_') === 0 && $key !== 'ik_sign')
                $pairs[$key] = $val;
        }

        uksort($pairs, function ($a, $b) use ($pairs) {
            $result = strcmp($a, $b);

            if ($result === 0)
                $result = strcmp($pairs[$a], $pairs[$b]);

            return $result;
        });

        array_push(
            $pairs,
            (strpos(YII_ENV, 'dev') > -1)
                ? $this->test_key
                : $this->secret_key
        );

        return base64_encode(hash($this->sign_algo, implode(":", $pairs), true));
    }

    /**
     * @param array $params
     * @return string
     */
    public function payment(array $params) {
        if (!is_array($params)) {
            throw new \InvalidArgumentException('Params must be array');
        }

        $params['ik_co_id'] = $this->co_id;
        return self::URL . '?' . http_build_query($params);
    }

    /**
     * @param $id
     * @param $purse_name
     * @param $payway_name
     * @param array $details
     * @param $amount
     * @param string $calcKey
     * @param string $action
     * @return mixed
     * @throws WithdrawException
     */
    public function withdraw(
        $id,
        $purse_name,
        $payway_name,
        array $details,
        $amount,
        $calcKey = 'ikPayerPrice',
        $action = 'calc'
    ) {
        $purses = $this->api->getPurses();
        $purse = null;

        foreach ($purses as $_purse) {
            if ($_purse['name'] == $purse_name || strpos($_purse['name'], $purse_name) !== false) {
                $purse = $_purse;
                $this->api->purse = $purse;
                break;
            }
        }

        if (!$purse) {
            throw new WithdrawException("Purse not found");
        }

        if (!$this->api->testTransaction) {
            if ($purse->balance < $amount) {
                throw new WithdrawException("Balance in purse ({$purse->balance}) less withdraw amount ({$amount}).");
            }
        }

        $payways = $this->api->getOutputPayways();
        $payway = null;

        foreach ($payways as $_payway) {
            if ($_payway['als'] == $payway_name) {
                $payway = $_payway;
                $this->api->payway = $payway;
                break;
            }
        }

        if (!$payway) {
            throw new WithdrawException("Payway not found");
        }

        try {
            $result = $this->api->createWithdraw(
                $amount,
                $payway['_id'],
                $details,
                $purse['id'],
                $calcKey,
                $action,
                $id
            );

            if ($result['@resultCode'] == 0) {
                return $result['transaction'];
            } else {
                throw new WithdrawException($result->{'@resultMessage'});
            }
        } catch (HttpException $e) {
            throw new WithdrawException('Http exception: ' . $e->getMessage());
        } catch (InterkassaException $e) {
            throw new WithdrawException('Interkassa exception: ' . $e->getMessage());
        }
    }
}