<?php

declare(strict_types = 1);

namespace Drupal\os_widgets\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\vsite\Plugin\VsiteContextManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Widget for placing block contents in layout regions.
 *
 * @FieldWidget(
 *   id = "place_block_content_widget",
 *   label = @Translation("Place block content widget"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE,
 * )
 */
class PlaceBlockContentWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * Vsite context manager.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManagerInterface
   */
  protected $vsiteContextManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Block content storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $blockContentStorage;

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
      $container->get('plugin.manager.core.layout'),
      $container->get('vsite.context_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Creates a new PlaceBlockContentWidget object.
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
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   Layout plugin manager.
   * @param \Drupal\vsite\Plugin\VsiteContextManagerInterface $vsite_context_manager
   *   Vsite context manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, LayoutPluginManagerInterface $layout_plugin_manager, VsiteContextManagerInterface $vsite_context_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->vsiteContextManager = $vsite_context_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->blockContentStorage = $entity_type_manager->getStorage('block_content');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $layouts = [];
    $widgets = [];
    $default_layout = NULL;

    /** @var \Drupal\group\Entity\GroupInterface|null $active_vsite */
    $active_vsite = $this->vsiteContextManager->getActiveVsite();

    /** @var \Drupal\Core\Layout\LayoutDefinition[] $layout_definitions */
    $layout_definitions = $this->layoutPluginManager->getDefinitions();
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $current_entity */
    $current_entity = $items->getEntity();
    /** @var int|null $current_entity_id */
    $current_entity_id = $current_entity->id();
    /** @var \Drupal\block_content\Entity\BlockContent[] $block_contents */
    $block_contents = $this->blockContentStorage->loadMultiple();
    if ($active_vsite) {
      /** @var \Drupal\os_widgets\Entity\OsBlockContent[] $block_contents */
      $block_contents = $active_vsite->getContentEntities('group_entity:block_content');
    }

    // Prepare the options.
    foreach ($block_contents as $block_content) {
      $widgets[$block_content->id()] = $block_content->label();
    }
    foreach ($layout_definitions as $layout_definition) {
      $layouts[$layout_definition->id()] = $layout_definition->getLabel();
    }

    // Prepare the default values.
    if ($current_entity_id !== NULL) {
      // Making sure that the parent column itself doesn't appear in options.
      // Otherwise, this could lead to recursion.
      unset($widgets[$current_entity_id]);

      $existing_layout_setting = $current_entity->get(OverridesSectionStorage::FIELD_NAME);
      /** @var array $layout */
      $layout = $existing_layout_setting->getValue();
      /** @var \Drupal\layout_builder\Section $section */
      $section = $layout[0]['section'];
      $default_layout = $section->getLayoutId();
    }

    $default_widgets = array_map(static function ($item) {
      return $item['target_id'];
    }, array_filter($items->getValue()));

    $element['layouts'] = [
      '#type' => 'select',
      '#options' => $layouts,
      '#default_value' => $default_layout,
      '#title' => $this->t('Layout'),
    ];

    $element['widgets'] = [
      '#type' => 'select',
      '#options' => $widgets,
      '#multiple' => TRUE,
      '#title' => $this->t('Widget'),
      '#default_value' => $default_widgets,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $form_values = [];

    foreach ($values['widgets'] as $widget_id) {
      $form_values[] = [
        'target_id' => $widget_id,
      ];
    }

    return $form_values;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_display_settings = \Drupal::entityTypeManager()->getStorage('entity_view_display')->load("{$field_definition->getTargetEntityTypeId()}.{$field_definition->getTargetBundle()}.default");
    return $entity_display_settings instanceof LayoutBuilderEntityViewDisplay;
  }

}
