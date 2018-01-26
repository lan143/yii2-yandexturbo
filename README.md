Yii2 Yandex Turbo
=================
Yii2 module for automatically generating RSS 2.0 for Yandex Turbo service.

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require "lan143/yii2_yandexturbo" "*"
```

or add

```json
{
  "require": {
    "lan143/yii2_yandexturbo": "*"
  } 
}
```

to the `require` section of your application's `composer.json` file and run `composer update`.

Configuration
-------------

* Configure the `cache` component of your application's configuration file, for example:

```php
'components' => [
    'cache' => [
        'class' => \yii\caching\FileCache::class,
    ],
]
```


* Add a new module in `modules` section of your application's configuration file, for example:

```php
'modules' => [
    'yandexTurbo' => [
        'class' => \lan143\yii2_yandexturbo\YandexTurbo::class,
        'title' => 'Liftoff News', // not required, default Application name 
        'link' => 'http://liftoff.msfc.nasa.gov/', // not required, default Url::home
        'description' => 'Liftoff to Space Exploration.', // default empty
        'language' => 'en-us', // not required, default Application language
        'elements' => [
            // only model class. Need behavior in model
            \app\models\Records::class,
            // or configuration for creating a behavior
            [
                'model' => [
                    'class' => \app\models\Records::class,
                    'behaviors' => [
                        'yandexTurbo' => [
                            'class' => \lan143\yii2_yandexturbo\YandexTurboBehavior::class,
                            'scope' => function (\yii\db\ActiveQuery $query) {
                                $query->orderBy(['created_at' => SORT_DESC]);
                            },
                            'dataClosure' => function (\app\models\Records $model) {
                                return [
                                    'title' => $model->title,
                                    'link' => \yii\helpers\Url::to(['records/view', 'id' => $model->id], true),
                                    'description' => $model->description,
                                    'content' => $model->content,
                                    'pubDate' => (new \DateTime($this->created_at))->format(\DateTime::RFC822),
                                ];
                            }
                        ],
                    ],
                ],
            ],
            // or configure static content
            [
                'title' => 'About page',
                'link' => ['/about'],
                'description' => 'This is about page',
                'content' => 'Some content of about page, will be displayed in Yandex Turbo page. You can use <strong>html<strong> tags.',
                'pubDate' => (new \DateTime('2018-01-26 18:57:00'))->format(\DateTime::RFC822)
            ],
        ],
        'cacheExpire' => 1, // 1 second. Default is 15 minutes
    ],
],
```

* Add behavior in the AR models, for example:

```php
use DateTime;
use lan143\yii2_yandexturbo\YandexTurboBehavior;
use yii\db\ActiveQuery;
use yii\helpers\Url;

public function behaviors(): array
{
    return [
        'yandexTurbo' => [
            'class' => YandexTurboBehavior::class,
            'scope' => function (ActiveQuery $query) {
                $query->orderBy(['created_at' => SORT_DESC]);
            },
            'dataClosure' => function (self $model) {
                return [
                    'title' => $model->title,
                    'link' => Url::to(['records/view', 'id' => $model->id], true),
                    'description' => $model->description,
                    'content' => $model->content,
                    'pubDate' => (new DateTime($this->created_at))->format(DateTime::RFC822),
                ];
            }
        ],
    ];
}
```


* Add a new rule for `urlManager` of your application's configuration file, for example:

```php
'urlManager' => [
    'rules' => [
        ['pattern' => 'yandex_turbo', 'route' => 'yandexTurbo/yandex-turbo/index', 'suffix' => '.xml'],
    ],
],
```

Now you can add http://siteexample.com/yandex_turbo.xml in Yandex Webmaster service.