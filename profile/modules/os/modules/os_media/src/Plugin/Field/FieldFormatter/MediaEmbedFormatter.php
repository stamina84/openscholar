<?php

namespace Drupal\os_media\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\media\Plugin\Field\FieldFormatter\OEmbedFormatter;
use Drupal\os_media\MediaEntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the media embed formatter.
 *
 * @FieldFormatter(
 *   id = "media:embed",
 *   label = @Translation("Embed content"),
 *   field_types = {
 *     "link",
 *     "string",
 *     "string_long",
 *   },
 * )
 */
class MediaEmbedFormatter extends OEmbedFormatter implements ContainerFactoryPluginInterface {

  /**
   * Media Helper Service.
   *
   * @var \Drupal\os_media\MediaEntityHelper
   */
  protected $mediaHelper;

  /**
   * Constructs an OEmbedFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin ID for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   * @param \Drupal\os_media\MediaEntityHelper $media_helper
   *   Media Helper instance.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MessengerInterface $messenger, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, IFrameUrlHelper $iframe_url_helper, MediaEntityHelper $media_helper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $messenger, $resource_fetcher, $url_resolver, $logger_factory, $config_factory, $iframe_url_helper);
    $this->mediaHelper = $media_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('messenger'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('os_media.media_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'max_width' => '100%',
      'max_height' => '701',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\media\OEmbed\ResourceException
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $max['width'] = $this->getSetting('max_width');
    $max['height'] = $this->getSetting('max_height');
    $domain = $this->config->get('iframe_domain');
    foreach ($items as $delta => $item) {
      $main_property = $item->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();
      $value = $item->{$main_property};

      $media_type = $item->getFieldDefinition()->get('bundle');

      if (empty($value)) {
        continue;
      }

      switch ($media_type) {

        case 'oembed':
          // Handle Embedly oEmbed content.
          if (UrlHelper::isValid($value)) {

            $resource = $this->mediaHelper->fetchEmbedlyResource($value);

            if ($resource['type'] === Resource::TYPE_LINK) {
              $element[$delta] = [
                '#title' => $resource['title'],
                '#type' => 'link',
                '#url' => Url::fromUri($value),
              ];
            }
            elseif ($resource['type'] === Resource::TYPE_PHOTO) {
              $element[$delta] = [
                '#theme' => 'image',
                '#uri' => Url::fromUri($resource['url'])->setAbsolute()->toString(),
                '#width' => $max['width'] ?: $resource['width'],
                '#height' => $max['height'] ?: $resource['height'],
              ];
            }
            else {
              $max['width'] = $item->width ?? $max['width'];
              $max['height'] = $item->height ?? $max['height'];
              $max = $this->mediaHelper->getOEmbedDimensions($resource, $max);
              // Display rich content and videos inside an Iframe.
              $element[$delta] = $this->mediaHelper->iFrameData($value, $max, $domain);
            }
            CacheableMetadata::createFromObject($resource)->addCacheTags($this->config->getCacheTags())->applyTo($element[$delta]);
          }
          break;

        case 'html':
          $hasIframe = preg_match('/(?:<iframe[^>]*)(?:(?:\/>)|(?:>.*?<\/iframe>))/', $value);
          $hasScript = preg_match('/<script[\s\S]*?>[\s\S]*?<\/script>/', $value);

          $max['width'] = $item->width ?? $max['width'];
          $max['height'] = $item->height ?? $max['height'];
          // To handle <iframe> content.
          if ($hasIframe && !$hasScript) {
            // Get the source url.
            preg_match('/src="([^"]+)"/', $value, $match);
            $url = $match[1];
            $max = $this->mediaHelper->getHtmlDimensions($value, $max);
            $element[$delta] = $this->mediaHelper->iFrameData($url, $max, $domain);
          }

          // To handle <scripts>.
          elseif (preg_match('/<script[\s\S]*?>[\s\S]*?<\/script>/', $value, $scripts)) {
            $default_tags = Xss::getHtmlTagList();
            array_push($default_tags, 'script', 'img', 'style', 'iframe');

            $max = $this->mediaHelper->getHtmlDimensions($value, $max);
            // Replace height and width with our maximums.
            $patterns[0] = '/height="([^\"]*)"/';
            // Catch width="100%" as well.
            $patterns[1] = '/width="([^\"]*)"/';
            $replacements[1] = 'height="' . $max['height'] . '"';
            $replacements[0] = 'width="' . $max['width'] . '"';

            $value = preg_replace($patterns, $replacements, $value);

            $element[$delta] = [
              '#markup' => $value,
              '#allowed_tags' => $default_tags,
            ];
          }
          break;

        default:
          return [];
      }
    }
    return $element;
  }

}
