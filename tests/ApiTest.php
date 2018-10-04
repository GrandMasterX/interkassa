<?php
namespace grandmasterx\interkassa\tests;

use Yii;
use grandmasterx\interkassa\Api;
use grandmasterx\interkassa\exceptions\HttpException;

class ApiTest extends TestCase
{
    public function testGetCurrencies()
    {
        $api = new Api();
        $currencies = $api->request('GET', 'currency');
        $this->assertTrue(is_array($currencies));
    }

    public function testIncorrectRequest()
    {
        $this->expectException(HttpException::class);
        $api = new Api();
        $api->request('GET', 'incorrect-request');
    }

    public function testIncorrectParams()
    {
        $this->expectException(HttpException::class);
        $api = new Api();
        $api->request('GET', 'account');
    }
}