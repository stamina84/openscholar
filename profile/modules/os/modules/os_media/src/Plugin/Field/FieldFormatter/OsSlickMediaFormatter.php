<?php

namespace Drupal\os_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\slick\SlickFormatterInterface;
use Drupal\slick\SlickManagerInterface;
use Drupal\slick_media\Plugin\Field\FieldFormatter\SlickMediaFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'slick media entity' formatter.
 *
 * @FieldFormatter(
 *   id = "os_slick_media",
 *   label = @Translation("Os Slick Media"),
 *   description = @Translation("Display the referenced entities as a Slick carousel."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class OsSlickMediaFormatter extends SlickMediaFormatter {

  /**
   * Entity Type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

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
      $container->get('logger.factory'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('slick.formatter'),
      $container->get('slick.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Constructor to get this object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LoggerChannelFactoryInterface $logger_factory, EntityStorageInterface $image_style_storage, SlickFormatterInterface $formatter, SlickManagerInterface $manager, EntityTypeManager $entity_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $logger_factory, $image_style_storage, $formatter, $manager);
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entities = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($entities)) {
      return [];
    }

    foreach ($entities as $entity) {
      $type = $entity->bundle();
      if ($type === 'image') {
        $images[] = $entity;
      }
      elseif ($type === 'oembed') {
        $videos[] = Link::fromTextAndUrl($entity->name->value, Url::fromUri($entity->field_media_oembed_content->value, ['attributes' => ['target' => '_blank']]))->toRenderable();
      }
      else {
        $others[] = $this->entityTypeManager->getViewBuilder('media')->view($entity, 'default');
      }
    }
    // Collects specific settings to this formatter.
    $settings = $this->getSettings();

    // Asks for Blazy to deal with iFrames, and mobile-optimized lazy loading.
    $settings['blazy']     = TRUE;
    $settings['plugin_id'] = $this->getPluginId();

    // Sets dimensions once to reduce method ::transformDimensions() calls.
    if ($images) {
      $images = array_values($images);
      if (!empty($settings['image_style'])) {
        $fields = $images[0]->getFields();

        if (isset($fields['thumbnail'])) {
          $item = $fields['thumbnail']->get(0);

          $settings['item'] = $item;
          $settings['uri'] = $item->entity->getFileUri();
        }
      }
      $build = ['settings' => $settings];
      $this->formatter->buildSettings($build, $items);
      // Build the elements.
      $this->buildElements($build, $images, $langcode);
      $build = $this->manager()->build($build);
    }

    $build['#theme'] = 'os_slick_wrapper';
    $build['#videos'] = $videos ?? [];
    $build['#others'] = $others ?? [];

    return $build;
  }

}
