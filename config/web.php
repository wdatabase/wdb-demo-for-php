<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'sefefs___ees',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/api/reg' => '/api/reg',
                '/api/login' => '/api/login',
                '/text/post' => '/text/post',
                '/text/info' => '/text/info',
                '/text/list' => '/text/list',
                '/text/del' => '/text/del',
                '/search/post' => '/search/post',
                '/search/info' => '/search/info',
                '/search/list' => '/search/list',
                '/search/del' => '/search/del',
                '/img/post' => '/img/post',
                '/img/info' => '/img/info',
                '/img/data' => '/img/data',
                '/img/list' => '/img/list',
                '/img/del' => '/img/del',
                '/video/post' => '/video/post',
                '/video/info' => '/video/info',
                '/video/data' => '/video/data',
                '/video/list' => '/video/list',
                '/video/del' => '/video/del',
                '/file/post' => '/file/post',
                '/file/info' => '/file/info',
                '/file/data' => '/file/data',
                '/file/list' => '/file/list',
                '/file/del' => '/file/del',
                '/shop/balance' => '/shop-to-info/balance',
                '/shop/info' => '/shop-to-info/info',
                '/shop/categorize/post' => '/shop-to-categorize/post',
                '/shop/categorize/list' => '/shop-to-categorize/list',
                '/shop/categorize/info' => '/shop-to-categorize/info',
                '/shop/categorize/del' => '/shop-to-categorize/del',
                '/shop/pro/post' => '/shop-to-pro/post',
                '/shop/pro/info' => '/shop-to-pro/info',
                '/shop/pro/list' => '/shop-to-pro/list',
                '/shop/pro/del' => '/shop-to-pro/del',
                '/shop/pro/img/post' => '/shop-to-pro/img-post',
                '/shop/pro/img/data' => '/shop-to-pro/img-data',
                '/shop/cart/add' => '/shop-to-cart/add',
                '/shop/cart/list' => '/shop-to-cart/list',
                '/shop/cart/del' => '/shop-to-cart/del',
                '/shop/order/create' => '/shop-to-order/create',
                '/shop/order/info' => '/shop-to-order/info',
                '/shop/order/list' => '/shop-to-order/list',
                '/big/file/upload' => 'big-file/upload',
                '/big/file/down' => 'big-file/down',
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
