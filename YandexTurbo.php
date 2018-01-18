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
     * @var array
     */
    public $channels = [];

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
        $channels = [];

        foreach ($this->channels as $channel) {
            if (is_array($channel['model'])) {
                $model = Yii::createObject([
                    'class' => $channel['model']['class']
                ]);

                if (isset($channel['model']['behaviors'])) {
                    $model->attachBehaviors($channel['behaviors']);
                }
            } elseif (is_string($channel['model'])) {
                $model = Yii::createObject([
                    'class' => $channel['model'],
                ]);
            } else {
                throw new InvalidConfigException('You must set model variable or unsupported model type');
            }

            $channels[] = [
                'title' => ArrayHelper::getValue($channel, 'title', Yii::$app->name),
                'link' => ArrayHelper::getValue($channel, 'link', Url::home(true)),
                'description' => ArrayHelper::getValue($channel, 'description'),
                'language' => ArrayHelper::getValue($channel, 'language', Yii::$app->language),
                'lastBuildDate' => (new DateTime())->format(DateTime::RFC822),
                'generator' => 'lan143\yii2-yandexturbo',
                'analytics' => ArrayHelper::getValue($channel, 'analytics'),
                'adNetwork' => ArrayHelper::getValue($channel, 'adNetwork'),
                'items' => $model->generateItems(),
            ];
        }

        $xml = $this->buildRssXml($channels);

        return $xml;
    }

    /**
     * @param array $channels
     * @return string
     */
    protected function buildRssXml(array $channels): string
    {
        $doc = new DOMDocument("1.0", "utf-8");

        $root = $doc->createElement("rss");
        $root->setAttribute('version', '2.0');
        $root->setAttribute('xmlns:yandex', 'http://news.yandex.ru');
        $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $root->setAttribute('xmlns:turbo', 'http://turbo.yandex.ru');
        $doc->appendChild($root);

        foreach ($channels as $channel) {
            $channelNode = $doc->createElement("channel");
            $root->appendChild($channelNode);

            $titleNode = $doc->createElement("title", $channel['title']);
            $channelNode->appendChild($titleNode);

            $linkNode = $doc->createElement("link", $channel['link']);
            $channelNode->appendChild($linkNode);

            $descriptionNode = $doc->createElement("description", $channel['description']);
            $channelNode->appendChild($descriptionNode);

            $languageNode = $doc->createElement("language", $channel['language']);
            $channelNode->appendChild($languageNode);

            $lastBuildDateNode = $doc->createElement("lastBuildDate", $channel['lastBuildDate']);
            $channelNode->appendChild($lastBuildDateNode);

            $generatorNode = $doc->createElement("generator", $channel['generator']);
            $channelNode->appendChild($generatorNode);

            if (!empty($channel['analytics'])) {
                $analyticsNode = $doc->createElement("yandex:analytics", $channel['analytics']);
                $channelNode->appendChild($analyticsNode);
            }

            if (!empty($channel['adNetwork'])) {
                $adNetworkNode = $doc->createElement("yandex:adNetwork", $channel['adNetwork']);
                $channelNode->appendChild($adNetworkNode);
            }

            foreach ($channel['items'] as $item) {
                $itemNode = $doc->createElement("item");
                $itemNode->setAttribute('turbo', 'true');
                $channelNode->appendChild($itemNode);

                $itemTitleNode = $doc->createElement("title", $item['title']);
                $itemNode->appendChild($itemTitleNode);

                $itemLinkNode = $doc->createElement("link", $item['link']);
                $itemNode->appendChild($itemLinkNode);

                $itemDescriptionNode = $doc->createElement("description", $item['description']);
                $itemNode->appendChild($itemDescriptionNode);

                $itemContentNode = $doc->createElement("turbo:content");
                $itemNode->appendChild($itemContentNode);

                $contentWrapper = $doc->createCDATASection($item['content']);
                $itemContentNode->appendChild($contentWrapper);

                $itemPubDateNode = $doc->createElement("pubDate", $item['pubDate']);
                $itemNode->appendChild($itemPubDateNode);
            }
        }

        return $doc->saveXML();
    }
}