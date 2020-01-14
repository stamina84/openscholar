<?php

namespace Drupal\vsite\Helper;

use Drupal\Core\Entity\EntityInterface;

/**
 * Contract for Vsite field validator helper service.
 */
interface VsiteFieldValidateHelperInterface {

  /**
   * Validates that a field value is unique within the active vsite.
   *
   * @param array $items
   *   The value that should be validated.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to run validation for.
   *
   * @return bool
   *   If the value already exists or not.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function uniqueFieldValueValidator(array $items, EntityInterface $entity): bool;

}
