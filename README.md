Icepay Extension for Yii2
=========================
An extension to the Icepay API for Yii2

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist subdee/yii2-icepay "*"
```

or add

```
"subdee/yii2-icepay": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, initialize it as a component in your config file:

```php
'components' => [
    ...
    'icepay' => [
        'class' => 'subdee\icepay\Icepay',
        'merchantID' => 'merchant id',
        'secretCode' => 'secretCode',
    ],
    ...
],
```

and then use it anywhere in your application as a component:

```php
$methods = \Yii::$app->icepay->getPaymentMethods();
```