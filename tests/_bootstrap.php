<?php
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'dev');
    require_once __DIR__.'/../vendor/autoload.php';
    require_once __DIR__.'/../vendor/yiisoft/yii2/Yii.php';
    Yii::setAlias('@tests', __DIR__);
    Yii::setAlias('@data', __DIR__.DIRECTORY_SEPARATOR.'_data');

    $config = require __DIR__.'/../config/test.php';
    (new \yii\web\Application($config));

    spl_autoload_register(function ($class_name) {
        $class_name = trim(str_replace('\\', '/', $class_name), '/');
        if (substr($class_name, 0, 4) == 'app/') {
            /** @noinspection PhpIncludeInspection */
            require_once __DIR__.'/../'.substr($class_name, 4).'.php';
        }
    });
