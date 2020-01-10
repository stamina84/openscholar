<?php

namespace Drupal\cp_import;

use Drupal\cp_import\AppImport\BaseInterface;
use Drupal\cp_import\Helper\CpImportHelper;

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
   * AppImportFactory constructor.
   *
   * @param \Drupal\cp_import\Helper\CpImportHelper $cpImportHelper
   *   CpImportHelper instance.
   */
  public function __construct(CpImportHelper $cpImportHelper) {
    $this->cpImportHelper = $cpImportHelper;
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
    return new $app_class($this->cpImportHelper);
  }

}
