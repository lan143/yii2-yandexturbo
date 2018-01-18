<?php

namespace lan143\yii2_yandexturbo;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class YandexTurboController
 * @package lan143\yii2_yandexturbo
 */
class YandexTurboController extends Controller
{
    /**
     * @return string
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex(): string
    {
        /** @var YandexTurbo $module */
        $module = $this->module;

        Yii::$app->response->format = Response::FORMAT_RAW;

        $headers = Yii::$app->response->headers;
        $headers->add('Content-Type', 'application/xml');

        return $module->getRssFeed();
    }
}