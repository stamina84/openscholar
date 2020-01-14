<?php

namespace Drupal\vsite\Helper;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\group\Entity\GroupContentType;
use Drupal\vsite\Plugin\VsiteContextManager;

/**
 * Class VsiteFieldValidateHelper.
 *
 * @package Drupal\vsite\Helper
 */
class VsiteFieldValidateHelper implements VsiteFieldValidateHelperInterface {

  /**
   * Vsite context manager service.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManager
   */
  protected $vsiteContextManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * VsiteFieldValidateHelper constructor.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManager $vsiteContextManager
   *   Vsite context manager instance.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity type manager instance.
   */
  public function __construct(VsiteContextManager $vsiteContextManager, EntityTypeManager $entityTypeManager) {
    $this->vsiteContextManager = $vsiteContextManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function uniqueFieldValueValidator($items, $entity): bool {

    $field_name = $items['field_name'];
    $entity_type_id = $entity->getEntityTypeId();
    $id_key = $entity->getEntityType()->getKey('id');

    $query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery();
    $group = $this->vsiteContextManager->getActiveVsite();

    // Handle vsite things.
    // No join on EntityQuery, so instead we only include entities that are
    // part of the active group.
    /** @var \Drupal\group\Entity\GroupContentType[] $plugins */
    $plugins = GroupContentType::loadByEntityTypeId($entity_type_id);
    $plugin = reset($plugins);
    if ($group) {
      $entities = $group->getContentEntities($plugin->getContentPluginId());
      $include = [];
      foreach ($entities as $et) {
        $include[] = $et->id();
      }
      if (count($include)) {
        $query->condition($entity->getEntityType()->getKey('id'), $include, 'IN');
      }
      else {
        // This vsite has no entities of the given type. It's value is
        // valid by default.
        return FALSE;
      }
    }

    $entity_id = $entity->id();
    // Using isset() instead of !empty() as 0 and '0' are valid ID values for
    // entity types using string IDs.
    if (isset($entity_id)) {
      $query->condition($id_key, $entity_id, '<>');
    }

    return (bool) $query
      ->condition($field_name, $items['item_first']->value)
      ->range(0, 1)
      ->count()
      ->execute();
  }

}
