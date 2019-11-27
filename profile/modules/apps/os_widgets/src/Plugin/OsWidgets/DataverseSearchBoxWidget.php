<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\Core\Database\Connection;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DataverseSearchBoxWidget.
 *
 * @OsWidget(
 *   id = "dataverse_search_box_widget",
 *   title = @Translation("Dataverse Search Box Widget")
 * )
 */
class DataverseSearchBoxWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  // TODO: check with team if we should make this drupal config instead
  // of the os_widgets module instead of a class constant here?
  const DATAVERSE_SEARCH_BASE_URL = "https://dataverse.harvard.edu/dataverse.xhtml";

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $params = [];

    $field_search_placeholder_text = $block_content->get('field_search_placeholder_text')->getValue();
    if (!empty($field_search_placeholder_text)) {
      $params['dataverse_search_box_placeholder'] = $field_search_placeholder_text[0]['value'];
    }

    $field_dataverse_identifier = $block_content->get('field_dataverse_identifier')->getValue();
    if (!empty($field_dataverse_identifier)) {
      $params['dataverse_identifier'] = $field_dataverse_identifier[0]['value'];
    }

    $params['dataverse_search_baseurl'] = $this::DATAVERSE_SEARCH_BASE_URL;

    $form = $this->formBuilder->getForm('Drupal\os_widgets\Form\DataverseSearchBoxForm', $params);
    $build['dataverse_search_box_form'] = $form;
  }

}
