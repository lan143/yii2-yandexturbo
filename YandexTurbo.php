<?php

namespace lan143\yii2_yandexturbo;

use DateTime;
use DOMDocument;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Module;
use yii\caching\Cache;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Class YandexTurbo
 * @package lan143\yii2_yandexturbo
 */
class YandexTurbo extends Module
{
    const GENERATOR_NAME = 'lan143\yii2-yandexturbo';

    /**
     * @var string
     */
    public $controllerNamespace = 'lan143\yii2_yandexturbo';

    /**
     * @var int
     */
    public $cacheExpire = 900;

    /**
     * @var Cache|string
     */
    public $cacheProvider = 'cache';

    /**
     * @var string
     */
    public $cacheKey = 'yandexTurbo';

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $link;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $language;

    /**
     * @var string
     */
    public $analytics;

    /**
     * @var string
     */
    public $adNetwork;

    /**
     * @var array
     */
    public $elements = [];

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        if (is_string($this->cacheProvider)) {
            $this->cacheProvider = Yii::$app->get($this->cacheProvider);
        }

        if (!$this->cacheProvider instanceof Cache) {
            throw new InvalidConfigException('Invalid `cacheKey` parameter was specified.');
        }

        if (empty($this->title)) {
            $this->title = Yii::$app->name;
        }

        if (empty($this->link)) {
            $this->link = Url::home(true);
        }

        if (empty($this->language)) {
            $this->language = Yii::$app->language;
        }
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    public function getRssFeed(): string
    {
        if (!($xml = $this->cacheProvider->get($this->cacheKey))) {
            $xml = $this->buildRssFeed();

            $this->cacheProvider->set($this->cacheKey, $xml, $this->cacheExpire);
        }

        return $xml;
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->cacheProvider->delete($this->cacheKey);
    }

    /**
     * @return string
     * @throws InvalidConfigException
     */
    protected function buildRssFeed(): string
    {
        $items = [];

        foreach ($this->elements as $element) {
            if (is_array($element)) {
                if (isset($element['class'])) {
                    $model = Yii::createObject([
                        'class' => $element['class']
                    ]);

                    if (isset($element['behaviors'])) {
                        $model->attachBehaviors($element['behaviors']);
                    }
                } else {
                    $items[] = [
                        'title' => ArrayHelper::getValue($element, 'title'),
                        'link' => Url::to(ArrayHelper::getValue($element, 'link'), true),
                        'description' => ArrayHelper::getValue($element, 'description'),
                        'content' => ArrayHelper::getValue($element, 'content'),
                        'pubDate' => ArrayHelper::getValue($element, 'pubDate'),
                    ];

                    continue;
                }
            } elseif (is_string($element)) {
                $model = Yii::createObject([
                    'class' => $element,
                ]);
            } else {
                throw new InvalidConfigException('You must set model variable or unsupported model type');
            }

            $items = ArrayHelper::merge($items, $model->generateYandexTurboItems());
        }

        $xml = $this->buildRssXml($items);

        return $xml;
    }

    /**
     * @param array $elements
     * @return string
     */
    protected function buildRssXml(array $elements): string
    {
        $doc = new DOMDocument("1.0", "utf-8");

        $root = $doc->createElement("rss");
        $root->setAttribute('version', '2.0');
        $root->setAttribute('xmlns:yandex', 'http://news.yandex.ru');
        $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $root->setAttribute('xmlns:turbo', 'http://turbo.yandex.ru');
        $doc->appendChild($root);

        $channelNode = $doc->createElement("channel");
        $root->appendChild($channelNode);

        $titleNode = $doc->createElement("title", $this->title);
        $channelNode->appendChild($titleNode);

        $linkNode = $doc->createElement("link", $this->link);
        $channelNode->appendChild($linkNode);

        $descriptionNode = $doc->createElement("description", $this->description);
        $channelNode->appendChild($descriptionNode);

        $languageNode = $doc->createElement("language", $this->language);
        $channelNode->appendChild($languageNode);

        $lastBuildDateNode = $doc->createElement("lastBuildDate", (new DateTime())->format(DateTime::RFC822));
        $channelNode->appendChild($lastBuildDateNode);

        $generatorNode = $doc->createElement("generator", self::GENERATOR_NAME);
        $channelNode->appendChild($generatorNode);

        if (!empty($this->analytics)) {
            $analyticsNode = $doc->createElement("yandex:analytics", $this->analytics);
            $channelNode->appendChild($analyticsNode);
        }

        if (!empty($this->adNetwork)) {
            $adNetworkNode = $doc->createElement("yandex:adNetwork", $this->adNetwork);
            $channelNode->appendChild($adNetworkNode);
        }

        foreach ($elements as $element) {
            $itemNode = $doc->createElement("item");
            $itemNode->setAttribute('turbo', 'true');
            $channelNode->appendChild($itemNode);

            $itemTitleNode = $doc->createElement("title", $element['title']);
            $itemNode->appendChild($itemTitleNode);

            $itemLinkNode = $doc->createElement("link", $element['link']);
            $itemNode->appendChild($itemLinkNode);

            $itemDescriptionNode = $doc->createElement("description", $element['description']);
            $itemNode->appendChild($itemDescriptionNode);

            $itemContentNode = $doc->createElement("turbo:content");
            $itemNode->appendChild($itemContentNode);

            $contentWrapper = $doc->createCDATASection($element['content']);
            $itemContentNode->appendChild($contentWrapper);

            $itemPubDateNode = $doc->createElement("pubDate", $element['pubDate']);
            $itemNode->appendChild($itemPubDateNode);
        }

        return $doc->saveXML();
    }
}