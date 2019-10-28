<?php

namespace Drupal\os_widgets;

/**
 * Class OsWidgetsContext to handle determine for me option.
 *
 * @package Drupal\os_widgets
 */
class OsWidgetsContext implements OsWidgetsContextInterface {

  private $bundles = [];

  /**
   * {@inheritdoc}
   */
  public function addBundle($bundle): void {
    if (in_array($bundle, $this->bundles)) {
      return;
    }
    $this->bundles[] = $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles(): array {
    return $this->bundles;
  }

}
