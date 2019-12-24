<?php

namespace Drupal\os_media;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
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
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

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
    'audio' => 'field_media_file',
    'executable' => 'field_media_file',
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
    'Embeds' => 'oembed',
    'Presentation' => 'presentation',
  ];

  /**
   * The directory where thumbnails are stored.
   *
   * @var string
   */
  protected $thumbsDirectory = '://video_thumbnails';

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
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   Logger instance.
   */
  public function __construct(ClientInterface $http_client, IFrameUrlHelper $iframe_url_helper, ConfigFactory $config_factory, Messenger $messenger, LoggerChannelFactory $logger) {
    $this->httpClient = $http_client;
    $this->iFrameUrlHelper = $iframe_url_helper;
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('os_media.settings');
    $this->messenger = $messenger;
    $this->logger = $logger;
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
  public function getHtmlDimensions($html, $max) : array {
    preg_match('/height="([^\"]*)"/', $html, $fetchHeight);
    preg_match('/width="([^\"]*)"/', $html, $fetchWidth);

    if ($fetchHeight && $fetchWidth) {
      $height = $fetchHeight[1];
      $width = $fetchWidth[1];
    }

    $target['width'] = $max['width'];
    $target['height'] = $max['height'];

    if ($max['width'] === 'default') {
      $target['width'] = $width ?? '100%';
    }

    if ($max['height'] === 'default') {
      $target['height'] = $height ?? '100%';
    }

    return $target;
  }

  /**
   * {@inheritdoc}
   */
  public function getOembedDimensions($resource, $max): array {
    $target['width'] = $max['width'];
    $target['height'] = $max['height'];

    if ($max['width'] === 'default') {
      $target['width'] = $resource['width'];
    }
    if ($max['height'] === 'default') {
      $target['height'] = $resource['height'];
    }
    return $target;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchEmbedlyResource($url, $width = NULL, $height = NULL) {

    $cache_id = "media:embedly_resource:$url";

    $cached = $this->cacheGet($cache_id);
    if ($cached) {
      return $cached->data;
    }

    $options = [
      'query' => [
        "url" => $url,
        "key" => $this->config->get('embedly_key'),
        "width" => $width,
        "height" => $height,
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
  public function iFrameData($value, $max, $domain) : array {

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
        'scrolling' => "no",
        'allowtransparency' => TRUE,
        'width' => $max['width'],
        'height' => $max['height'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function downloadThumbnail(array $resource): void {
    $local_uri = $this->getLocalThumbnailUri($resource);
    if (!file_exists($local_uri)) {
      $dir = file_default_scheme() . $this->thumbsDirectory;
      file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
      try {
        $thumbnail = $this->httpClient->request('GET', $resource['thumbnail_url']);
        file_unmanaged_save_data((string) $thumbnail->getBody(), $local_uri);
      }
      catch (RequestException $e) {
        $this->logger->error($e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalThumbnailUri(array $resource) : string {
    $name = preg_replace('/\s+/', '', $resource['title']);
    $dir = file_default_scheme() . $this->thumbsDirectory;
    return $dir . '/' . $name . '.jpg';
  }

}
