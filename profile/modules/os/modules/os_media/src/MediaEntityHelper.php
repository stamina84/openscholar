<?php

namespace Drupal\os_media;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\media\IFrameUrlHelper;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Class MediaEntityHelper.
 *
 * @package Drupal\os_media
 */
final class MediaEntityHelper implements MediaEntityHelperInterface {

  use StringTranslationTrait;
  use UseCacheBackendTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Iframe Helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Os Media config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * File fields to be used.
   */
  const FILE_FIELDS = [
    'filename',
  ];

  /**
   * Field mappings for media bundles.
   */
  const FIELD_MAPPINGS = [
    'image' => 'field_media_image',
    'document' => 'field_media_file',
    'video' => 'field_media_video_file',
    'oembed' => 'field_media_oembed_content',
    'html' => 'field_media_html',
  ];

  /**
   * Allowed media types for Media browser.
   */
  const ALLOWED_TYPES = [
    'Image' => 'image',
    'Document' => 'document',
    'HTML' => 'html',
    'Executable' => 'executable',
    'Audio' => 'audio',
    'Icon' => 'icon',
    'Embeds' => 'oembed',
  ];

  /**
   * MediaEntityHelper constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Http Client.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   Iframe Helper instance.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Config Factory instance.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Messenger instance.
   */
  public function __construct(ClientInterface $http_client, IFrameUrlHelper $iframe_url_helper, ConfigFactory $config_factory, Messenger $messenger) {
    $this->httpClient = $http_client;
    $this->iFrameUrlHelper = $iframe_url_helper;
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('os_media.settings');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(string $bundle) : string {
    return self::FIELD_MAPPINGS[$bundle];
  }

  /**
   * {@inheritdoc}
   */
  public function getEmbedType($embed) : ?string {
    if (UrlHelper::isValid($embed)) {
      $raw = FALSE;
    }
    else {
      $html_keys = ['iframe', 'src', 'href', 'param', 'script'];
      foreach ($html_keys as $tag) {
        if (strpos($embed, $tag)) {
          $raw = TRUE;
          break;
        }
      }
    }
    return $raw ? 'html' : 'oembed';
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensions($html, $max) : array {
    preg_match('/height="([^\"]*)"/', $html, $fetchHeight);
    preg_match('/width="([^\"]*)"/', $html, $fetchWidth);

    if ($fetchHeight && $fetchWidth) {
      $height = $fetchHeight[1];
      $width = $fetchWidth[1];
    }

    $target['width'] = NULL;
    if ($max['width'] != 0) {
      if (isset($width)) {
        $target['width'] = $width < $max['width'] ? $width : $max['width'];
      }
    }

    $target['height'] = NULL;
    if ($max['height'] != 0) {
      if (isset($height)) {
        $target['height'] = $height < $max['height'] ? $height : $max['height'];
      }
    }
    return $target;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchEmbedlyResource($url) {

    $cache_id = "media:embedly_resource:$url";

    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      return $cached->data;
    }

    $options = [
      'query' => [
        "url" => $url,
        "key" => $this->config->get('embedly_key'),
      ],
    ];

    try {
      $response = $this->httpClient->get($this->config->get('embedly_url'), $options);
    }
    catch (RequestException $e) {
      $this->messenger->addError($this->t('Could not retrieve the Embedly resource.'));
      return FALSE;
    }

    list($format) = $response->getHeader('Content-Type');
    $content = (string) $response->getBody();

    if (strstr($format, 'text/xml') || strstr($format, 'application/xml')) {
      $xml_encoder = new XmlEncoder();
      $data = $xml_encoder->decode($content, 'xml');
    }
    elseif (strstr($format, 'text/javascript') || strstr($format, 'application/json')) {
      $data = Json::decode($content);
    }
    // If the response is neither XML nor JSON, we are in bat country.
    else {
      $this->messenger->addError($this->t('The fetched resource did not have a valid Content-Type header.'));
      return FALSE;
    }

    $this->cacheSet($cache_id, $data);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function iFrameData($value, $max, $resource, $domain) : array {

    $url = Url::fromRoute('os_media.embed_iframe', [], [
      'query' => [
        'url' => $value,
        'max_width' => $max['width'],
        'max_height' => $max['height'],
        'hash' => $this->iFrameUrlHelper->getHash($value, $max['width'], $max['height']),
      ],
    ]);

    if ($domain) {
      $url->setOption('base_url', $domain);
    }
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $url->toString(),
        'frameborder' => 0,
        'scrolling' => FALSE,
        'allowtransparency' => TRUE,
        'width' => $max['width'] ?: $resource['width'],
        'height' => $max['height'] ?: $resource['height'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getThumbnail() : ?string {
    $icon_base = $this->configFactory->get('media.settings')->get('icon_base_uri');
    $thumbnail = $icon_base . '/generic.png';
    if (is_file($thumbnail)) {
      return $thumbnail;
    }
    return NULL;
  }

}
