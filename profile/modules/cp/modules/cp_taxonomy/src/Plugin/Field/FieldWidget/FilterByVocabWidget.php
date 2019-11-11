<?php

namespace Drupal\cp_taxonomy\Plugin\Field\FieldWidget;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\cp_taxonomy\CpTaxonomyHelperInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Taxonomy terms widget for reference terms.
 *
 * @FieldWidget(
 *   id = "filter_by_vocab",
 *   label = @Translation("Filter By Vocab widget"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = TRUE
 * )
 */
class FilterByVocabWidget extends WidgetBase implements WidgetInterface, ContainerFactoryPluginInterface {

  /**
   * Cp taxonomy helper.
   *
   * @var \Drupal\cp_taxonomy\CpTaxonomyHelperInterface
   */
  protected $taxonomyHelper;

  /**
   * Instance of selected widget.
   *
   * @var \Drupal\Core\Field\WidgetInterface
   */
  protected $fieldWidgets;
  protected $pluginManager;
  protected $entityTypeManager;
  protected $widgetConfiguration;

  /**
   * TaxonomyTermsWidget constructor.
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
   * @param \Drupal\cp_taxonomy\CpTaxonomyHelperInterface $taxonomy_helper
   *   Config Factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   Plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, CpTaxonomyHelperInterface $taxonomy_helper, PluginManagerInterface $plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->taxonomyHelper = $taxonomy_helper;

    $this->pluginManager = $plugin_manager;
    $this->entityTypeManager = $entity_type_manager;

    $configuration['field_definition'] = $field_definition;
    $configuration['settings'] = $settings;
    $configuration['third_party_settings'] = $third_party_settings;
    $this->widgetConfiguration = $configuration;
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
      $container->get('cp.taxonomy.helper'),
      $container->get('plugin.manager.field.widget'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $main_element = [
      '#tree' => TRUE,
    ];
    // $nodeTypes = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    //    foreach ($nodeTypes as $key => $type) {
    //      $list[$key] = $type->get('name');
    //    }
    //    $list['publication'] = 'Publication';
    //    asort($list);
    //    foreach ($list as $key => $item) {
    //      $vids[$key] = $this->taxonomyHelper->searchAllowedVocabulariesByType("node:$key");
    //    }.
    $title = $element['#title'];
    $description = $element['#description'];
    $vocabularies = Vocabulary::loadMultiple();
    foreach ($vocabularies as $vid => $vocabulary) {
      $list[$vid] = $vocabulary;
    }

    $element['terms_container'] = [
      '#type' => 'details',
      '#title' => $title,
      '#description' => $description,
      '#open' => TRUE,
      '#collapsible' => FALSE,
    ];

    foreach ($list as $vid => $vocab) {
      $element['terms_container'][$vid] = [
        '#type' => 'textfield',
        '#title' => $vocab->get('name'),
      ];
    }

    return $element;

    /** @var \Drupal\group\Entity\GroupInterface $vsite */
    // $vsite = \Drupal::service('vsite.context_manager')->getActiveVsite();
    //    $terms = $vsite->getContentEntities('group_entity:taxonomy_term');
    //    foreach ($terms as $term) {
    //      $list[$term->bundle()] = $term->name->value;
    //    }
    //    ksm($list);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

  }

}
