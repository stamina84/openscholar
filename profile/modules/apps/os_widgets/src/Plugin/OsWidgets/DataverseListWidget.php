<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DataverseListWidget.
 *
 * @OsWidget(
 *   id = "dataverse_list_widget",
 *   title = @Translation("Dataverse List Widget")
 * )
 */
class DataverseListWidget extends OsWidgetsBase implements OsWidgetsInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $connection);
    $this->configFactory = $config_factory;
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
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildBlock(&$build, $block_content) {
    $dataverse_config = $this->configFactory->get('os_widgets.dataverse');
    $base_url = $dataverse_config->get('listing_base_url');
    if (empty($base_url)) {
      return;
    }

    // Default embed height is set to 500px.
    $embed_height = '500px';

    $field_dataverse_identifier = $block_content->get('field_dataverse_identifier')->getValue();
    $dataverse_identifier = Xss::filter($field_dataverse_identifier[0]['value']);

    $field_embed_height = $block_content->get('field_embed_height')->getValue();
    if (!empty($field_embed_height)) {
      $embed_height = Xss::filter($field_embed_height[0]['value'] . "px");
    }

    // Example of dataverse list iframe embed URL is:
    // https://dataverse.harvard.edu/dataverse/king?widget=dataverse@king
    $options = [
      'query' => [
        'widget' => 'dataverse@' . $dataverse_identifier,
      ],
      'absolute' => TRUE,
    ];
    $embed_url = Url::fromUri($base_url . $dataverse_identifier, $options)->toString();

    $build['dataverse_list_widget'] = [
      '#theme' => 'os_widgets_dataverse_list_widget',
      '#embed_url' => $embed_url,
      '#embed_height' => $embed_height,
    ];
  }

}
