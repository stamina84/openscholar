<?php

namespace Drupal\os_widgets\Plugin\OsWidgets;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\os_widgets\OsWidgetsBase;
use Drupal\os_widgets\OsWidgetsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DataverseDatasetCitationWidget.
 *
 * @OsWidget(
 *   id = "dataverse_dataset_citation_widget",
 *   title = @Translation("Dataverse Dataset Citation")
 * )
 */
class DataverseDatasetCitationWidget extends OsWidgetsBase implements OsWidgetsInterface {

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
    $base_url = $dataverse_config->get('base_url');
    if (empty($base_url)) {
      return;
    }

    $embed_height = $block_content->get('field_embed_height')->getString();
    $persistent_id = $block_content->get('field_dataset_persistent_id')->getString();
    $persistent_type = $block_content->get('field_dataset_persistent_type')->getString();

    $options = [
      'query' => [
        'persistentId' => $persistent_type . ":" . trim($persistent_id),
        'dvUrl' => $base_url,
        'widget' => 'citation',
        'heightPx' => $embed_height,
      ],
      'absolute' => TRUE,
    ];
    $js_url = Url::fromUri($base_url . "resources/js/widgets.js", $options)->toString();
    $build['embed_dataverse'] = [
      '#theme' => 'os_widgets_dataverse_dataset_citation',
      '#js_url' => $js_url,
    ];
  }

}
