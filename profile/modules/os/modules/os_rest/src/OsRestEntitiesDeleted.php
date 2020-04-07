<?php

namespace Drupal\os_rest;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Entities deleted functions to handle .
 */
class OsRestEntitiesDeleted implements OsRestEntitiesDeletedInterface {

  use StringTranslationTrait;

  /**
   * Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   */
  public function __construct(Connection $connection, ModuleHandlerInterface $module_handler, TimeInterface $time) {
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function insertEntity(EntityInterface $entity): void {
    $id = $entity->id();
    $type = $entity->getEntityTypeId();
    $allowed_loggable_types = [
      'node',
      'media',
      'bibcite_reference',
      'taxonomy_term',
      'taxonomy_vocabulary',
    ];

    if (empty($id) || !in_array($type, $allowed_loggable_types)) {
      return;
    }
    $hook = 'os_rest_entity_delete_data';
    $args = [
      'entity' => $entity,
    ];
    $extra = $this->moduleHandler->invokeAll($hook, $args);
    $this->moduleHandler->alter($hook, $extra);

    $this->connection->insert('entities_deleted')
      ->fields([
        'entity_id' => $id,
        'entity_type' => $type,
        'deleted' => $this->time->getRequestTime(),
        'extra' => serialize($extra),
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntities(string $entity_type, int $timestamp): array {
    $result = $this->connection->select('entities_deleted', 'ed')
      ->fields('ed')
      ->condition('entity_type', $entity_type)
      ->condition('deleted', $timestamp, '>')
      ->execute();

    $deleted = [];

    while ($r = $result->fetchAssoc()) {
      $deleted[] = [
        'id' => $r->entity_id,
        'status' => 'deleted',
        'extra' => unserialize($r->extra),
      ];
    }

    // drupal_alter('os_rest_deleted_entities', $deleted, $this);
    // $return = array_merge($return, $deleted);.
    return $deleted;
  }

}
