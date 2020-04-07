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

}
