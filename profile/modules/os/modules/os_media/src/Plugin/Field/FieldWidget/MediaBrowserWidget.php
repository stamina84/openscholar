<?php

namespace Drupal\os_media\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Environment;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\os\AngularModuleManagerInterface;
use Drupal\os_media\MediaEntityHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaBrowserWidget.
 *
 * @FieldWidget(
 *   id = "media_browser_widget",
 *   label = @Translation("Media Browser"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE,
 * )
 */
class MediaBrowserWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Angular module manager.
   *
   * @var \Drupal\os\AngularModuleManagerInterface
   */
  protected $angularModuleManager;

  /**
   * Media Helper service.
   *
   * @var \Drupal\os_media\MediaEntityHelper
   */
  protected $mediaHelper;

  /**
   * MediaBrowserWidget constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\os\AngularModuleManagerInterface $angular_module_manager
   *   Angular module manager.
   * @param \Drupal\os_media\MediaEntityHelper $media_helper
   *   Media helper instance.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, AngularModuleManagerInterface $angular_module_manager, MediaEntityHelper $media_helper) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->angularModuleManager = $angular_module_manager;
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
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('angular.module_manager'),
      $container->get('os_media.media_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $media = [];
    for ($i = 0, $l = $items->count(); $i < $l; $i++) {
      /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $refItem */
      $refItem = $items->get($i);
      $media[] = $refItem->getValue()['target_id'];
    }
    $settings = $this->getFieldSettings();
    $bundles = $settings['handler_settings']['target_bundles'];

    $allTypes = $this->mediaHelper::ALLOWED_TYPES;
    $types = [];
    /** @var \Drupal\media\Entity\MediaType[] $mediaTypes */
    $mediaTypes = $this->entityTypeManager->getStorage('media_type')->loadMultiple($bundles);
    foreach ($mediaTypes as $type) {
      if (in_array($type->id(), $allTypes)) {
        $types[] = $type->id();
      }
    }

    // Specifically for Media Browser widget inside paragraphs.
    // This might look a bit hackish but yes to maintain the correct form field
    // name-spacing we need this, as form field generated by Angular does not
    // adhere to the proper namespace required by Drupal for reference field
    // inside a paragraph.
    $fieldName = $this->fieldDefinition->getName();
    if ($parents = $form['#parents']) {
      $formFieldName = '';
      foreach ($parents as $key => $parent) {
        $formFieldName .= ($key == 0) ? "$parent" : "[$parent]";
      }
      $formFieldName .= "[$fieldName]";
    }

    $element['#type'] = 'container';
    $element['#input'] = TRUE;
    $element['#default_value'] = [];
    $element['media-browser-field'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'media-browser-field' => '',
        'field-id' => Html::getUniqueId('edit-' . $fieldName),
        'field-name' => $formFieldName ?? $fieldName,
        'files' => implode(',', $media),
        'types' => implode(',', $types),
        'max-filesize' => format_size(Environment::getUploadMaxSize()),
        'upload_text' => 'Upload',
        'droppable_text' => 'Drop here.',
        'cardinality' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
        'title' => $this->fieldDefinition->getLabel(),
        'required_class' => $this->fieldDefinition->isRequired() ? 'form-required' : '',
      ],
      '#markup' => $this->t('Loading the Media Browser. Please wait a moment.'),
      '#attached' => [
        'library' => [
          'os_media/mediaBrowserField',
        ],
      ],
      '#post_render' => [
        [$this, 'addNgModule'],
      ],
    ];

    return $element;
  }

  /**
   * Adds the AngularJS module to the page.
   */
  public function addNgModule() {
    $this->angularModuleManager->addModule('MediaBrowserField');
  }

}
