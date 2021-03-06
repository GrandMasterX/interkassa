[![Build Status](https://travis-ci.org/grandmasterx/interkassa.svg?branch=master)](https://travis-ci.org/grandmasterx/interkassa)

Yii2 Interkassa
===============
Extension for integration Interkassa in yii2 project. WIP.

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist grandmasterx/interkassa "*"
```

or add

```
"grandmasterx/interkassa": "*"
```

to the require section of your `composer.json` file.

Update config file config/web.php
```php
return [
    'components' => [
        'interkassa' => [
            'class' => 'grandmasterx\interkassa\Component',
            'co_id' => '', // Cashbox identifier
            'secret_key' => '', // Cashbox secret key
            'test_key' => '', // Cashbox test secret key
            'sign_algo' => 'md5', // Sign algoritm. Allow: md5, sha1
            'api_user_id' => '', // Api user id
            'api_user_key' => '' // Api user secret key
        ],
    ],
]
```


Usage
-----
Example payment:
```php
class InterkassaController extends Controller
{
    public function actions() {
        return [
            'result' => [
                'class' => 'grandmasterx\interkassa\ResultAction',
                'callback' => [$this, 'resultCallback'],
            ],
            'success' => [
                'class' => 'grandmasterx\interkassa\SuccessAction',
                'callback' => [$this, 'successCallback'],
            ],
            'fail' => [
                'class' => 'grandmasterx\interkassa\FailAction',
                'callback' => [$this, 'failCallback'],
            ],
        ];
    }

    public function actionInvoice()
    {
        $model = new Invoice();

        if ($model->load(Yii::$app->request) && $model->save())
        {
            $params = [
                'ik_pm_no' => $model->id,
                'ik_am' => $model->ammount,
                'ik_desc' => 'Site payment',
            ];

            return Yii::$app->interkassa->payment($params);
        }

        return $this->render('invoice', compact($model));
    }

    public function successCallback($ik_am, $ik_inv_st, $ik_pm_no)
    {
        return $this->render('success');
    }

    public function failCallback($ik_am, $ik_inv_st, $ik_pm_no)
    {
        return $this->render('fail');
    }

    public function resultCallback($ik_am, $ik_inv_st, $ik_pm_no)
    {

        switch ($ik_inv_st)
        {
            case 'new':
                $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_NEW]);
                break;
            case 'waitAccept':
                $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_PENDING]);
                break;
            case 'process':
                $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_PROCESS]);
                break;
            case 'success':
                $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_SUCCESS]);
                break;
            case 'canceled':
                $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_CANCELED]);
                break;
            case 'fail':
                $this->loadModel($ik_pm_no)->updateAttributes(['status' => Invoice::STATUS_FAIL])
                break;
        }
    }

    protected function loadModel($id)
    {
        $model = Invoice::findOne($id);

        if ($model === null)
            throw new BadRequestHttpException;

        return $model;
    }
}
```

Example withdraw:
```php
class Withdraw
{
    protected $purse_name = 'My Purse Name';

    public function process($id)
    {
        $withdraw = Withdraw::findOne($id);
        
        if ($withdraw === null)
            throw new BadRequestHttpException;
    
        try {
            $result = Yii::$app->interkassa->withdraw(
                $withdraw->id,
                $this->purse_name,
                $withdraw->payway_name,
                ['purse' => $withdraw->purse],
                $withdraw->amount,
                'psPayeeAmount',
                'process'
            );
        } catch (WithdrawException $e) {
            return $e->getMessage();
        }
    }
}