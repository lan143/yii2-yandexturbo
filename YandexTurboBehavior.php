<?php

namespace lan143\yii2_yandexturbo;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * Class YandexTurboBehavior
 * @package lan143\yii2_yandexturbo
 */
class YandexTurboBehavior extends Behavior
{
    const BATCH_MAX_SIZE = 100;

    /**
     * @var callable
     */
    public $dataClosure;

    /**
     * @var callable
     */
    public $scope;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        if (!is_callable($this->dataClosure) && !is_array($this->dataClosure)) {
            throw new InvalidConfigException('dataClosure isn\'t callable or array.');
        }
    }

    public function generateYandexTurboItems(): array
    {
        $result = [];

        /** @var \yii\db\ActiveRecord $owner */
        $owner = $this->owner;
        $query = $owner::find();

        if (is_array($this->scope)) {
            if (is_callable($this->owner->{$this->scope[1]}())) {
                call_user_func($this->owner->{$this->scope[1]}(), $query);
            }
        } else {
            if (is_callable($this->scope)) {
                call_user_func($this->scope, $query);
            }
        }

        foreach ($query->each(self::BATCH_MAX_SIZE) as $model) {
            if (is_array($this->dataClosure)) {
                $data = call_user_func($this->owner->{$this->dataClosure[1]}(), $model);
            } else {
                $data = call_user_func($this->dataClosure, $model);
            }

            if (empty($data)) {
                continue;
            }

            $result[] = [
                'title' => ArrayHelper::getValue($data, 'title'),
                'link' => ArrayHelper::getValue($data, 'link'),
                'description' => ArrayHelper::getValue($data, 'description'),
                'content' => ArrayHelper::getValue($data, 'content'),
                'backgroundImage' => ArrayHelper::getValue($data, 'backgroundImage'),
                'menuLinks' => ArrayHelper::getValue($data, 'menuLinks'),
                'pubDate' => ArrayHelper::getValue($data, 'pubDate'),
            ];
        }

        return $result;
    }
}