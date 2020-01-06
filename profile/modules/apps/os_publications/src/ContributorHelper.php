<?php

namespace Drupal\os_publications;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\bibcite_entity\Entity\Contributor;

/**
 * ContributorHelper to provide contibutor related services.
 */
class ContributorHelper {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ContributorHelper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns list/single Contributor(s) based on properties.
   *
   * @param \Drupal\bibcite_entity\Entity\Contributor $contributor
   *   Contributor object.
   * @param bool $all
   *   If first or all Contributors to be loaded.
   */
  public function getByProps(Contributor $contributor = NULL, bool $all = FALSE) {
    $contributor_storage = $this->entityTypeManager->getStorage('bibcite_contributor');
    $existing_contributors = [];

    $properties = [
      'first_name' => $contributor->getFirstName(),
      'middle_name' => $contributor->getMiddleName(),
      'last_name' => $contributor->getLastName(),
    ];

    $properties = array_filter($properties);

    if ($properties) {
      $existing_contributors = $contributor_storage->loadByProperties($properties);
    }

    if (!$all && $existing_contributors) {
      $existing_contributor = array_pop($existing_contributors);

      return $existing_contributor;
    }

    return $existing_contributors;
  }

}
