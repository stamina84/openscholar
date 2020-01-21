<?php

namespace Drupal\cp_import;

use Drupal\Core\Language\LanguageManager;
use Drupal\cp_import\AppImport\BaseInterface;
use Drupal\cp_import\Helper\CpImportHelper;
use Drupal\vsite\Path\VsiteAliasStorage;

/**
 * AppImportFactory class acts as a bridge between events subscriber and apps.
 *
 * To generate respective app instance for invoking methods for those apps.
 */
final class AppImportFactory {

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
   * AppImportFactory constructor.
   *
   * @param \Drupal\cp_import\Helper\CpImportHelper $cpImportHelper
   *   CpImportHelper instance.
   * @param \Drupal\vsite\Path\VsiteAliasStorage $vsiteAliasStorage
   *   Vsite alias storage instance.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language manager instance.
   */
  public function __construct(CpImportHelper $cpImportHelper, VsiteAliasStorage $vsiteAliasStorage, LanguageManager $languageManager) {
    $this->cpImportHelper = $cpImportHelper;
    $this->vsiteAliasStorage = $vsiteAliasStorage;
    $this->languageManager = $languageManager;
  }

  /**
   * Creates a new app importing class.
   *
   * @param string $app_import_type
   *   The app import type.
   *
   * @return \Drupal\cp_import\AppImport\BaseInterface
   *   The AppImport class.
   */
  public function create($app_import_type) : BaseInterface {
    $app_class = "Drupal\\cp_import\\AppImport\\$app_import_type\\AppImport";
    return new $app_class($this->cpImportHelper, $this->vsiteAliasStorage, $this->languageManager);
  }

}
