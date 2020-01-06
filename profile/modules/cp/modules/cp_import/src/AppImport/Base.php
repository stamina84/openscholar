<?php

namespace Drupal\cp_import\AppImport;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\cp_import\Helper\CpImportHelper;

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
   * Base constructor.
   *
   * @param \Drupal\cp_import\Helper\CpImportHelper $cpImportHelper
   *   Cp import helper instance.
   */
  public function __construct(CpImportHelper $cpImportHelper) {
    $this->cpImportHelper = $cpImportHelper;
  }

}
