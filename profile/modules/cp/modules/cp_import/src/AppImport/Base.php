<?php

namespace Drupal\cp_import\AppImport;

use DateTime;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cp_import\Helper\CpImportHelper;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Drupal\vsite\Path\VsiteAliasRepository;

/**
 * Acts as a base for AppImport factory implementation for all apps.
 *
 * @package Drupal\cp_import\AppImport
 */
abstract class Base implements BaseInterface {
  use StringTranslationTrait;

  /**
   * Custom date format.
   */
  public const CUSTOM_DATE_FORMAT = "m/d/Y";

  /**
   * Cp Import helper service.
   *
   * @var \Drupal\cp_import\Helper\CpImportHelper
   */
  protected $cpImportHelper;

  /**
   * Vsite Alias Storage service.
   *
   * @var \Drupal\vsite\Path\VsiteAliasRepository
   */
  protected $vsiteAliasRepository;

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
   * @param \Drupal\vsite\Path\VsiteAliasRepository $vsiteAliasRepository
   *   Vsite Alias storage instance.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language Manager instance.
   */
  public function __construct(CpImportHelper $cpImportHelper, VsiteAliasRepository $vsiteAliasRepository, LanguageManager $languageManager) {
    $this->cpImportHelper = $cpImportHelper;
    $this->vsiteAliasRepository = $vsiteAliasRepository;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRowActions(MigratePrepareRowEvent $event) {
    $source = $event->getRow()->getSource();
    $type = $event->getMigration()->getProcess()['type'][0]['default_value'];
    $createdDate = $source['Created date'];
    if ($alias = $source['Path']) {
      if ($this->vsiteAliasRepository->lookupByAlias("/$type/$alias", $this->languageManager->getDefaultLanguage()->getId())) {
        $event->getRow()->setSourceProperty('NeedsAliasUpdate', TRUE);
      }
    }
    else {
      $event->getRow()->setSourceProperty('NeedsAliasUpdate', TRUE);
    }
    if ($createdDate) {
      $date = DateTime::createFromFormat(Base::CUSTOM_DATE_FORMAT, $createdDate);
      $event->getRow()->setSourceProperty('Created date', $date->format('Y-m-d'));
    }
    else {
      $date = new DateTime();
      $event->getRow()->setSourceProperty('Created date', $date->format('Y-m-d'));
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

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage($rows, $message): array {
    if (strpos($rows, ',') === FALSE) {
      $message = $this->t('Row @rows: @message </br>', ['@rows' => $rows, '@message' => $message]);
      $count = 1;
    }
    else {
      $count = count(explode(',', $rows));
      $message = $this->t('<a data-toggle="tooltip" title="Rows: @rows">@count Rows</a>: @msg </br>',
        [
          '@count' => $count,
          '@rows' => $rows,
          '@msg' => $message,
        ]
      );
    }

    return ['message' => $message, 'count' => $count];
  }

}
