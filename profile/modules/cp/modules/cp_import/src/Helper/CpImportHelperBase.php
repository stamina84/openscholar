<?php

namespace Drupal\cp_import\Helper;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\vsite\Plugin\VsiteContextManager;

/**
 * Class CpImportHelperBase.
 *
 * @package Drupal\cp_import\Helper
 */
class CpImportHelperBase implements CpImportHelperBaseInterface {

  use StringTranslationTrait;

  /**
   * Vsite Manager service.
   *
   * @var \Drupal\vsite\Plugin\VsiteContextManager
   */
  protected $vsiteManager;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * CpImportHelperBase constructor.
   *
   * @param \Drupal\vsite\Plugin\VsiteContextManager $vsiteContextManager
   *   VsiteContextManager instance.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity Type Manager instance.
   */
  public function __construct(VsiteContextManager $vsiteContextManager, EntityTypeManager $entityTypeManager) {
    $this->vsiteManager = $vsiteContextManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function addContentToVsite(string $id, $pluginId, $entityType): void {
    $vsite = $this->vsiteManager->getActiveVsite();
    // If in vsite context add content to vsite otherwise do nothing.
    if ($vsite) {
      $entity = $this->entityTypeManager->getStorage($entityType)->load($id);
      $vsite->addContent($entity, $pluginId);
    }
  }

}
