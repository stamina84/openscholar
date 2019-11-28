<?php

namespace Drupal\os_widgets\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'os widgets slideshow image' formatter.
 *
 * @FieldFormatter(
 *   id = "os_widgets_slideshow_image",
 *   label = @Translation("Slideshow image"),
 *   description = @Translation("Render image with proper alt/title and link if exists."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SlideshowImageFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  protected $imageStylePrefix;

  /**
   * Constructs a EntityReferenceEntityFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'layout_type' => 'wide',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['layout_type'] = [
      '#type' => 'select',
      '#options' => $this->getLayoutTypes(),
      '#title' => $this->t('Layout type'),
      '#default_value' => $this->getSetting('layout_type'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $layout_type = $this->getSetting('layout_type');
    $summary[] = $this->t('Layout type: @type', ['@type' => $this->getLayoutTypes()[$layout_type]]);

    return $summary;
  }

  /**
   * Define layout types.
   */
  public function getLayoutTypes() {
    return [
      'wide' => $this->t('Wide'),
      'standard' => $this->t('Standard'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $this->initImageStylePrefix();

    $paragraph = $items->getEntity();
    $alt_value = $paragraph->get('field_slide_alt_text')->getString();
    $title_value = $paragraph->get('field_slide_title_text')->getString();
    $slide_link_value = $paragraph->get('field_slide_link')->getString();

    foreach ($items as $item) {
      $image_media = $item->entity;
      if (!$image_media) {
        // Referenced media might be deleted.
        continue;
      }
      $image = $this->getImageFromMedia($image_media);
      // Case of user not set alt value in slideshow paragraph.
      if (empty($alt_value) && !empty($image_media->field_media_image->alt)) {
        $alt_value = $image_media->field_media_image->alt;
      }
      // Case of user not set title value in slideshow paragraph.
      if (empty($title_value) && !empty($image_media->field_media_image->title)) {
        $title_value = $image_media->field_media_image->title;
      }
      $data_breakpoint_uri = $this->getImageBreakpointUri($image);
      $element['image'] = [
        '#theme' => 'image_style',
        '#style_name' => $this->imageStylePrefix . 'large',
        '#uri' => $image->getFileUri(),
        '#attributes' => [
          'data-breakpoint_uri' => Json::encode($data_breakpoint_uri),
          'class' => ['slideshow-image'],
          'alt' => Xss::filter($alt_value),
          'title' => Xss::filter($title_value),
        ],
      ];
      $element['image'] = [
        '#theme' => 'os_slideshow_formatter',
        '#image' => [
          '#theme' => 'image_style',
          '#style_name' => $this->imageStylePrefix . 'large',
          '#uri' => $image->getFileUri(),
          '#attributes' => [
            'data-breakpoint_uri' => Json::encode($data_breakpoint_uri),
            'class' => ['slideshow-image'],
            'alt' => Xss::filter($alt_value),
            'title' => Xss::filter($title_value),
          ],
        ],
        '#url' => $slide_link_value,
      ];

      $elements[] = $element;
    }
    $elements['#attached'] = [
      'library' => [
        'os_widgets/slideshowWidget',
      ],
    ];
    return $elements;
  }

  /**
   * Get Image file from given Media entity.
   *
   * @param \Drupal\media\Entity\Media $image_media
   *   Media entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Found slide image File entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getImageFromMedia(Media $image_media) {
    $file_storage = $this->entityTypeManager->getStorage('file');
    /** @var \Drupal\media\MediaSourceInterface $source */
    $source = $image_media->getSource();
    $fid = $source->getSourceFieldValue($image_media);
    $image = $file_storage->load($fid);
    return $image;
  }

  /**
   * Get image breakpoint uri values.
   *
   * @param \Drupal\Core\Entity\EntityInterface $image
   *   File image entity.
   *
   * @return array
   *   Collected image style uri.
   */
  protected function getImageBreakpointUri(EntityInterface $image): array {
    $slick_breakpoints_image_style_map = [
      576 => $this->imageStylePrefix . 'small',
      768 => $this->imageStylePrefix . 'medium',
      'large' => $this->imageStylePrefix . 'large',
    ];
    $data_breakpoint_uri = [];
    foreach ($slick_breakpoints_image_style_map as $breakpoint => $image_style) {
      $data_breakpoint_uri[$breakpoint]['uri'] = ImageStyle::load($image_style)->buildUrl($image->getFileUri());
    }
    return $data_breakpoint_uri;
  }

  /**
   * Init image style prefix value depends on layout settings.
   */
  protected function initImageStylePrefix() {
    $layout_type = $this->getSetting('layout_type');
    $this->imageStylePrefix = 'os_slideshow_standard_';
    if ($layout_type == 'wide') {
      $this->imageStylePrefix = 'os_slideshow_wide_';
    }
  }

}
