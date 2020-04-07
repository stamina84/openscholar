<?php

namespace Drupal\os_rest;

use Drupal\Core\Entity\EntityInterface;

/**
 * Entities deleted functions interface.
 */
interface OsRestEntitiesDeletedInterface {

  /**
   * Insert new log with given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Inserted entity.
   */
  public function insertEntity(EntityInterface $entity): void;

  /**
   * Get deleted entities by timestamp.
   *
   * @param string $entity_type
   *   Entity type.
   * @param int $timestamp
   *   Past timestamp filter.
   *
   * @return array
   *   Collected deleted rows.
   */
  public function getEntities(string $entity_type, int $timestamp): array;

}
