<?php

namespace Drupal\os_widgets;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\os_widgets\Entity\LayoutContext;

/**
 * Class OsWidgetsBase.
 *
 * @package Drupal\os_widgets
 */
class OsWidgetsBase extends PluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  protected $entityTypeManager;
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
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
      $container->get('database')
    );
  }

  /**
   * Creates default Widget/block content for the vsite.
   *
   * @param array $data
   *   Csv rows as data array.
   * @param string $bundle
   *   Type of block content to create.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group for which data is created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createWidget(array $data, $bundle, GroupInterface $group): void {}

  /**
   * Save widget layout.
   *
   * @param array $row
   *   Csv rows array.
   * @param string $uuid
   *   Block UUID.
   */
  public function saveWidgetLayout(array $row, $uuid) {
    // @var \Drupal\os_widgets\Entity\LayoutContext $context
    $context = LayoutContext::load($row['Context']);
    $data = $context->getBlockPlacements();
    $data[] = [
      'id' => "block_content|$uuid",
      'region' => $row['Region'],
      'weight' => 0,
    ];
    $context->setBlockPlacements($data);
    $context->save();
  }

}
