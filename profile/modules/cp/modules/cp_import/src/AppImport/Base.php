<?php

namespace Drupal\cp_import\AppImport;

use Drupal\Core\Language\LanguageManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cp_import\Helper\CpImportHelper;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Drupal\vsite\Path\VsiteAliasStorage;

/**
 * Acts as a base for AppImport factory implementation for all apps.
 *
 * @package Drupal\cp_import\AppImport
 */
abstract class Base implements BaseInterface {
  use StringTranslationTrait;

  /**
   * Cp Import helper service.
   *
   * @var \Drupal\cp_import\Helper\CpImportHelper
   */
  protected $cpImportHelper;

  /**
   * Vsite Alias Storage service.
   *
   * @var \Drupal\vsite\Path\VsiteAliasStorage
   */
  protected $vsiteAliasStorage;

  /**
   * Language Manager service.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Base constructor.
   *
   * @param \Drupal\cp_import\Helper\CpImportHelper $cpImportHelper
   *   Cp import helper instance.
   * @param \Drupal\vsite\Path\VsiteAliasStorage $vsiteAliasStorage
   *   Vsite Alias storage instance.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language Manager instance.
   */
  public function __construct(CpImportHelper $cpImportHelper, VsiteAliasStorage $vsiteAliasStorage, LanguageManager $languageManager) {
    $this->cpImportHelper = $cpImportHelper;
    $this->vsiteAliasStorage = $vsiteAliasStorage;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRowActions(MigratePrepareRowEvent $event) {
    $source = $event->getRow()->getSource();
    $type = $event->getMigration()->getProcess()['type'][0]['default_value'];
    if ($alias = $source['Path']) {
      if ($this->vsiteAliasStorage->aliasExists("/$type/$alias", $this->languageManager->getDefaultLanguage()->getId())) {
        $event->getRow()->setSourceProperty('NeedsAliasUpdate', TRUE);
      }
    }
    else {
      $event->getRow()->setSourceProperty('NeedsAliasUpdate', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function migratePostRowSaveActions(MigratePostRowSaveEvent $event) {
    $ids = $event->getDestinationIdValues();
    foreach ($ids as $id) {
      if ($event->getRow()->getSourceProperty('NeedsAliasUpdate')) {
        $dest_plugin = $event->getMigration()->getDestinationConfiguration()['plugin'];
        $entityType = str_replace('entity:', '', $dest_plugin);
        $this->cpImportHelper->handleContentPath($entityType, $id);
      }
    }
  }

}
